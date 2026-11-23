<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventRoomEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventAttendanceController extends Controller
{
    /** نبضة من الغرفة المباشرة كل ~30 ثانية: تُراكم مدة البقاء الفعلية */
    public function ping(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if (! $event->roomIsOpen()) {
            return response()->json(['ok' => false]);
        }

        if (! $event->isHostedBy($user)) {
            $entry = $event->roomEntries()->where('user_id', $user->id)->first();
            $admitted = $entry && ($entry->status === EventRoomEntry::ADMITTED
                || ($entry->status === EventRoomEntry::WAITING && ! $event->room_lobby && ! $entry->via_link));

            if (! $admitted) {
                return response()->json(['ok' => false]);
            }
        }

        $a = EventAttendance::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['seconds' => 0, 'first_seen_at' => now(), 'last_seen_at' => now()],
        );

        $gap = max(0, now()->timestamp - ($a->last_seen_at?->timestamp ?? now()->timestamp));

        // تجاهل النبضات المتقاربة (تبويبات متعددة)، ولا يُحتسب الغياب الطويل
        if ($gap >= 10) {
            if ($gap <= 90) {
                $a->seconds += min($gap, 60);
            }
            $a->last_seen_at = now();
            $a->save();
        }

        return response()->json(['ok' => true, 'minutes' => $a->minutes()]);
    }

    /** شهادة الحضور القابلة للطباعة: لصاحبها فقط وبعد انتهاء الحدث */
    public function certificate(Request $request, Event $event): View
    {
        $a = EventAttendance::where('event_id', $event->id)->where('user_id', $request->user()->id)->first();

        abort_unless(
            $a && $a->attended() && ! $event->isCancelled() && $event->hasEnded(),
            404,
            __('لا توجد شهادة متاحة لهذا الحدث.')
        );

        $code = $a->ensureCode();

        return view('events.certificate', [
            'event' => $event->load('host'),
            'user' => $request->user(),
            'attendance' => $a,
            'code' => $code,
            'verifyUrl' => route('certificate.verify', $code),
        ]);
    }

    /** صفحة عامة للتحقق من صحة شهادة برمزها */
    public function verify(string $code): View
    {
        $a = EventAttendance::with(['user', 'event.host'])->where('certificate_code', strtoupper($code))->first();

        return view('events.verify', ['a' => $a, 'code' => strtoupper($code)]);
    }
}
