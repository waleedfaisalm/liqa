<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRoomEntry;
use App\Models\User;
use App\Support\LiveKit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventRoomController extends Controller
{
    /** صفحة الغرفة المباشرة للمنظّم والمشاركين المقبولين فقط */
    public function show(Request $request, Event $event): View|RedirectResponse
    {
        $user = $request->user();
        $back = redirect()->route('events.show', $event);

        if ($error = $this->gate($event, $user)) {
            return $back->with('error', $error);
        }

        $isHost = $event->isHostedBy($user);

        $entry = $isHost ? null : $event->roomEntries()->where('user_id', $user->id)->first();
        if ($entry && $entry->status === EventRoomEntry::REMOVED) {
            return $back->with('error', 'أخرجك المنظّم من الغرفة المباشرة لهذا الحدث.');
        }

        return view('events.room', [
            'event' => $event,
            'isHost' => $isHost,
            'token' => $isHost ? $this->tokenFor($event, $user, true) : null,
            'serverUrl' => LiveKit::url(),
            'lobbyOn' => (bool) $event->room_lobby,
        ]);
    }

    /** العضو يطلب الدخول: ينتظر موافقة المنظّم إن كانت غرفة الانتظار مفعّلة */
    public function knock(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($error = $this->gate($event, $user)) {
            return response()->json(['status' => 'error', 'message' => $error], 403);
        }

        if ($event->isHostedBy($user)) {
            return response()->json(['status' => 'admitted']);
        }

        $entry = $event->roomEntries()->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => $event->room_lobby ? EventRoomEntry::WAITING : EventRoomEntry::ADMITTED],
        );

        return response()->json(['status' => $this->effectiveStatus($event, $entry)]);
    }

    public function status(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($this->gate($event, $user)) {
            return response()->json(['status' => 'error'], 403);
        }

        $entry = $event->roomEntries()->where('user_id', $user->id)->first();

        return response()->json(['status' => $entry ? $this->effectiveStatus($event, $entry) : 'none']);
    }

    /** رمز الدخول بعد القبول فقط */
    public function token(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($this->gate($event, $user)) {
            return response()->json(['message' => 'غير مسموح.'], 403);
        }

        if (! $event->isHostedBy($user)) {
            $entry = $event->roomEntries()->where('user_id', $user->id)->first();

            if (! $entry || $this->effectiveStatus($event, $entry) !== EventRoomEntry::ADMITTED) {
                return response()->json(['message' => 'لم يُقبل دخولك بعد.'], 403);
            }
        }

        return response()->json(['token' => $this->tokenFor($event, $user, $event->isHostedBy($user)), 'url' => LiveKit::url()]);
    }

    /** المنظّم: قائمة المنتظرين */
    public function waiting(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $list = $event->roomEntries()->where('status', EventRoomEntry::WAITING)->with('user')->oldest()->get()
            ->map(fn ($e) => [
                'id' => $e->user_id,
                'name' => $e->user->name,
                'avatar' => $e->user->avatarUrl(),
                'initial' => $e->user->initial(),
            ])->values();

        return response()->json(['waiting' => $list, 'lobby' => (bool) $event->room_lobby]);
    }

    /** المنظّم: قبول أو رفض (أو قبول الجميع) */
    public function decide(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $data = $request->validate([
            'action' => ['required', 'in:admit,deny,admit_all'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $query = $event->roomEntries()->where('status', EventRoomEntry::WAITING);

        if ($data['action'] !== 'admit_all') {
            $query->where('user_id', (int) ($data['user_id'] ?? 0));
        }

        $query->update(['status' => $data['action'] === 'deny' ? EventRoomEntry::DENIED : EventRoomEntry::ADMITTED]);

        return response()->json(['ok' => true]);
    }

    /** المنظّم: تفعيل أو إيقاف غرفة الانتظار */
    public function lobby(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $on = $request->boolean('enabled');
        $event->forceFill(['room_lobby' => $on])->save();

        return response()->json(['lobby' => $on]);
    }

    /** المنظّم يُخرج مشاركًا من الغرفة ويمنعه من العودة */
    public function remove(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $data = $request->validate(['identity' => ['required', 'string', 'regex:/^\d+$/']]);

        if ($data['identity'] === (string) $request->user()->id) {
            return response()->json(['ok' => false, 'message' => 'لا يمكنك إخراج نفسك.'], 422);
        }

        $event->roomEntries()->updateOrCreate(['user_id' => (int) $data['identity']], ['status' => EventRoomEntry::REMOVED]);

        $ok = LiveKit::configured() && LiveKit::remove($event->roomName(), $data['identity']);

        return response()->json(['ok' => $ok], $ok ? 200 : 502);
    }

    // ---------------------------------------------------------

    /** سبب المنع (أو null إن كان الدخول مسموحًا) */
    private function gate(Event $event, ?User $user): ?string
    {
        if (! $user || ! $event->isParticipant($user)) {
            return 'الغرفة المباشرة متاحة للمنظّم والمشاركين المقبولين فقط.';
        }

        if ($event->isCancelled()) {
            return 'هذا الحدث ملغى.';
        }

        if (! LiveKit::configured()) {
            return 'الغرفة المباشرة غير مفعّلة بعد من إدارة المنصة.';
        }

        if (! $event->roomIsOpen()) {
            return $event->hasEnded()
                ? 'انتهى وقت الغرفة المباشرة لهذا الحدث.'
                : 'تفتح الغرفة قبل الموعد بـ '.Event::ROOM_OPENS_BEFORE_MINUTES.' دقيقة، أي في '.$event->roomOpensAt()->translatedFormat('l j F، g:i a').'.';
        }

        return null;
    }

    /** إن أوقف المنظّم غرفة الانتظار يُعدّ المنتظرون مقبولين */
    private function effectiveStatus(Event $event, EventRoomEntry $entry): string
    {
        if ($entry->status === EventRoomEntry::WAITING && ! $event->room_lobby) {
            return EventRoomEntry::ADMITTED;
        }

        return $entry->status;
    }

    private function tokenFor(Event $event, User $user, bool $admin): string
    {
        return LiveKit::token(
            $event->roomName(),
            (string) $user->id,
            $user->name,
            ['avatar' => $user->avatarUrl(), 'host' => $admin, 'initial' => $user->initial()],
            admin: $admin,
        );
    }
}
