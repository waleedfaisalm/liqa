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
    /** صفحة الغرفة المباشرة: للمنظّم والمشاركين المقبولين، ولغيرهم عبر الرابط بموافقة المنظّم */
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
            'isCohost' => ! $isHost && $this->isCohost($event, $user),
            'viaLink' => ! $isHost && ! $event->isParticipant($user),
            'token' => $isHost ? $this->tokenFor($event, $user, true) : null,
            'serverUrl' => LiveKit::url(),
            'lobbyOn' => (bool) $event->room_lobby,
            'linkOn' => (bool) $event->room_link,
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

        $viaLink = ! $event->isParticipant($user);

        // من يدخل بالرابط ينتظر موافقة المنظّم دائمًا، حتى لو أُوقفت غرفة الانتظار
        $entry = $event->roomEntries()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => ($event->room_lobby || $viaLink) ? EventRoomEntry::WAITING : EventRoomEntry::ADMITTED,
                'via_link' => $viaLink,
            ],
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

    /** المنظّم أو المساعد: قائمة المنتظرين */
    public function waiting(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->canManage($event, $request->user()), 403);

        $list = $event->roomEntries()->where('status', EventRoomEntry::WAITING)->with('user')->oldest()->get()
            ->map(fn ($e) => [
                'id' => $e->user_id,
                'name' => $e->user->name,
                'avatar' => $e->user->avatarUrl(),
                'initial' => $e->user->initial(),
                'link' => (bool) $e->via_link,
            ])->values();

        return response()->json(['waiting' => $list, 'lobby' => (bool) $event->room_lobby]);
    }

    /** المنظّم أو المساعد: قبول أو رفض (أو قبول الجميع) */
    public function decide(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->canManage($event, $request->user()), 403);

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

    /** المنظّم: السماح بالدخول بالرابط أو إيقافه */
    public function link(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $on = $request->boolean('enabled');
        $event->forceFill(['room_link' => $on])->save();

        return response()->json(['link' => $on]);
    }

    /** المنظّم: ترقية مشارك إلى منظّم مساعد أو إلغاء ذلك */
    public function cohost(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $data = $request->validate(['identity' => ['required', 'string', 'regex:/^\d+$/'], 'on' => ['required', 'boolean']]);

        $target = User::find((int) $data['identity']);
        if (! $target || $event->isHostedBy($target)) {
            return response()->json(['ok' => false, 'message' => 'لا يمكن تعديل هذا العضو.'], 422);
        }

        $entry = $event->roomEntries()->where('user_id', $target->id)->first();
        if (! $entry || $entry->status !== EventRoomEntry::ADMITTED) {
            return response()->json(['ok' => false, 'message' => 'يجب أن يكون العضو داخل الغرفة.'], 422);
        }

        $on = (bool) $data['on'];
        $entry->forceFill(['is_cohost' => $on])->save();

        // تحديث فوري لمن هو متصل الآن؛ وإلا يُطبَّق عند دخوله القادم
        if (LiveKit::configured()) {
            LiveKit::updateMetadata($event->roomName(), (string) $target->id, $this->metaFor($target, false, $on));
        }

        return response()->json(['ok' => true, 'cohost' => $on]);
    }

    /** كتم ميكروفونات من الخادم (منظّم أو مساعد) */
    public function mute(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->canManage($event, $user), 403);

        $data = $request->validate([
            'targets' => ['required', 'array', 'max:100'],
            'targets.*.identity' => ['required', 'string', 'regex:/^\d+$/'],
            'targets.*.sid' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_\-]+$/'],
        ]);

        $isOwner = $event->isHostedBy($user);
        $cohosts = $event->roomEntries()->where('is_cohost', true)->pluck('user_id')->map(fn ($id) => (string) $id)->all();
        $done = 0;

        foreach ($data['targets'] as $t) {
            $id = $t['identity'];

            if ($id === (string) $event->user_id || $id === (string) $user->id || (! $isOwner && in_array($id, $cohosts, true))) {
                continue;
            }

            if (LiveKit::configured() && LiveKit::mute($event->roomName(), $id, $t['sid'])) {
                $done++;
            }
        }

        return response()->json(['muted' => $done]);
    }

    /** المنظّم أو المساعد يُخرج مشاركًا من الغرفة ويمنعه من العودة */
    public function remove(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->canManage($event, $user), 403);

        $data = $request->validate(['identity' => ['required', 'string', 'regex:/^\d+$/']]);
        $id = (int) $data['identity'];

        if ($id === $user->id) {
            return response()->json(['ok' => false, 'message' => 'لا يمكنك إخراج نفسك.'], 422);
        }

        if ($id === $event->user_id) {
            return response()->json(['ok' => false, 'message' => 'لا يمكن إخراج المنظّم.'], 422);
        }

        if (! $event->isHostedBy($user) && $this->isCohost($event, User::find($id))) {
            return response()->json(['ok' => false, 'message' => 'وحده المنظّم يخرج منظّمًا مساعدًا.'], 422);
        }

        $event->roomEntries()->updateOrCreate(['user_id' => $id], ['status' => EventRoomEntry::REMOVED, 'is_cohost' => false]);

        $ok = LiveKit::configured() && LiveKit::remove($event->roomName(), $data['identity']);

        return response()->json(['ok' => $ok], $ok ? 200 : 502);
    }

    // ---------------------------------------------------------

    /** سبب المنع (أو null إن كان الدخول مسموحًا) */
    private function gate(Event $event, ?User $user): ?string
    {
        if (! $user) {
            return 'سجّل الدخول للانضمام إلى الغرفة.';
        }

        if (! $event->isParticipant($user) && ! $event->room_link) {
            return 'الدخول إلى هذه الغرفة متاح للمشاركين المقبولين فقط.';
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

    /** إن أوقف المنظّم غرفة الانتظار يُعدّ المنتظرون مقبولين، إلا من دخل بالرابط */
    private function effectiveStatus(Event $event, EventRoomEntry $entry): string
    {
        if ($entry->status === EventRoomEntry::WAITING && ! $event->room_lobby && ! $entry->via_link) {
            return EventRoomEntry::ADMITTED;
        }

        return $entry->status;
    }

    private function isCohost(Event $event, ?User $user): bool
    {
        return $user !== null && $event->roomEntries()
            ->where('user_id', $user->id)->where('is_cohost', true)->where('status', EventRoomEntry::ADMITTED)->exists();
    }

    private function canManage(Event $event, ?User $user): bool
    {
        return $event->isHostedBy($user) || $this->isCohost($event, $user);
    }

    private function metaFor(User $user, bool $host, bool $cohost): array
    {
        return ['avatar' => $user->avatarUrl(), 'host' => $host, 'cohost' => $cohost, 'initial' => $user->initial()];
    }

    private function tokenFor(Event $event, User $user, bool $admin): string
    {
        return LiveKit::token(
            $event->roomName(),
            (string) $user->id,
            $user->name,
            $this->metaFor($user, $admin, ! $admin && $this->isCohost($event, $user)),
            admin: $admin,
        );
    }
}
