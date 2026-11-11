<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\LiveKit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class EventRoomController extends Controller
{
    /** صفحة الغرفة المباشرة للمنظّم والمشاركين المقبولين فقط */
    public function show(Request $request, Event $event): View|RedirectResponse
    {
        $user = $request->user();
        $back = redirect()->route('events.show', $event);

        if (! $event->isParticipant($user)) {
            return $back->with('error', 'الغرفة المباشرة متاحة للمنظّم والمشاركين المقبولين فقط.');
        }

        if ($event->isCancelled()) {
            return $back->with('error', 'هذا الحدث ملغى.');
        }

        if (! LiveKit::configured()) {
            return $back->with('error', 'الغرفة المباشرة غير مفعّلة بعد من إدارة المنصة.');
        }

        if (! $event->roomIsOpen()) {
            return $back->with('error', $event->hasEnded()
                ? 'انتهى وقت الغرفة المباشرة لهذا الحدث.'
                : 'تفتح الغرفة قبل الموعد بـ '.Event::ROOM_OPENS_BEFORE_MINUTES.' دقيقة، أي في '.$event->roomOpensAt()->translatedFormat('l j F، g:i a').'.');
        }

        $isHost = $event->isHostedBy($user);

        if (! $isHost && Cache::has('room-removed:'.$event->id.':'.$user->id)) {
            return $back->with('error', 'أخرجك المنظّم من الغرفة المباشرة لهذا الحدث.');
        }

        $token = LiveKit::token(
            $event->roomName(),
            (string) $user->id,
            $user->name,
            ['avatar' => $user->avatarUrl(), 'host' => $isHost, 'initial' => $user->initial()],
            admin: $isHost,
        );

        return view('events.room', [
            'event' => $event,
            'isHost' => $isHost,
            'token' => $token,
            'serverUrl' => LiveKit::url(),
        ]);
    }

    /** المنظّم يُخرج مشاركًا من الغرفة */
    public function remove(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403);

        $data = $request->validate(['identity' => ['required', 'string', 'regex:/^\d+$/']]);

        if ($data['identity'] === (string) $request->user()->id) {
            return response()->json(['ok' => false, 'message' => 'لا يمكنك إخراج نفسك.'], 422);
        }

        Cache::put('room-removed:'.$event->id.':'.$data['identity'], 1, now()->addHours(8));

        $ok = LiveKit::configured() && LiveKit::remove($event->roomName(), $data['identity']);

        return response()->json(['ok' => $ok], $ok ? 200 : 502);
    }
}
