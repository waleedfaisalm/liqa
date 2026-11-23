<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** تسجيل الحضور بـ QR للأحداث الحضورية: المنظّم يعرض رمزًا متجددًا والعضو المقبول يمسحه */
class EventCheckinController extends Controller
{
    /** صفحة المنظّم: الرمز + قائمة المقبولين */
    public function show(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorizeHost($request, $event);

        if ($event->mode !== 'in_person' || $event->isCancelled()) {
            return redirect()->route('events.show', $event)->with('error', __('تسجيل الحضور بالرمز متاح للأحداث الحضورية فقط.'));
        }

        $accepted = $event->acceptedRequests()->with('user')->get()->pluck('user')->filter();
        $checked = $event->attendances()->whereIn('user_id', $accepted->pluck('id'))->where('seconds', '>=', EventAttendance::THRESHOLD)->pluck('user_id')->all();

        return view('events.checkin', ['event' => $event, 'accepted' => $accepted, 'checked' => $checked]);
    }

    /** رابط الرمز الحالي (يتجدد كل دقيقة) */
    public function qr(Request $request, Event $event): JsonResponse
    {
        $this->authorizeHost($request, $event);

        if (! $event->checkinOpen()) {
            return response()->json(['open' => false, 'message' => $this->closedMessage($event)]);
        }

        return response()->json(['open' => true, 'url' => route('events.checkin.scan', [$event, $event->checkinToken()])]);
    }

    /** حالة الحضور الحالية للمنظّم */
    public function status(Request $request, Event $event): JsonResponse
    {
        $this->authorizeHost($request, $event);

        $ids = $event->attendances()->where('seconds', '>=', EventAttendance::THRESHOLD)->pluck('user_id')->all();

        return response()->json(['ids' => $ids, 'count' => count($ids)]);
    }

    /** العضو يمسح الرمز فيُسجَّل حضوره */
    public function scan(Request $request, Event $event, string $token): RedirectResponse
    {
        $user = $request->user();
        $back = redirect()->route('events.show', $event);

        if ($event->mode !== 'in_person' || ! $event->checkinOpen()) {
            return $back->with('error', $this->closedMessage($event));
        }

        if ($event->isHostedBy($user)) {
            return $back->with('error', __('أنت المنظّم. اعرض الرمز على الحاضرين ليمسحوه.'));
        }

        if (! $event->checkinTokenValid($token)) {
            return $back->with('error', __('انتهت صلاحية الرمز. اطلب من المنظّم تحديث الشاشة وامسح الرمز الجديد.'));
        }

        if (! $event->acceptedRequests()->where('user_id', $user->id)->exists()) {
            return $back->with('error', __('تسجيل الحضور متاح للمشاركين المقبولين فقط.'));
        }

        $this->record($event, $user, 'qr');

        return $back->with('success', __('تم تسجيل حضورك. أهلًا بك!'));
    }

    /** تسجيل يدوي من المنظّم (لمن تعذّر عليه المسح) */
    public function manual(Request $request, Event $event, User $user): RedirectResponse
    {
        $this->authorizeHost($request, $event);

        abort_unless($event->mode === 'in_person' && $event->acceptedRequests()->where('user_id', $user->id)->exists(), 404);

        if (! $event->checkinOpen()) {
            return back()->with('error', $this->closedMessage($event));
        }

        $this->record($event, $user, 'manual');

        return back()->with('success', __('تم تسجيل حضور :n.', ['n' => $user->name]));
    }

    private function record(Event $event, User $user, string $method): void
    {
        $a = EventAttendance::firstOrNew(['event_id' => $event->id, 'user_id' => $user->id]);

        if ($a->attended()) {
            return; // مسجّل مسبقًا
        }

        $a->seconds = max(EventAttendance::THRESHOLD, $event->duration_minutes * 60);
        $a->first_seen_at ??= now();
        $a->last_seen_at = now();
        $a->method = $method;
        $a->save();
    }

    private function closedMessage(Event $event): string
    {
        if ($event->isCancelled()) {
            return __('هذا الحدث ملغى.');
        }

        return now()->lt($event->starts_at)
            ? __('يفتح تسجيل الحضور قبل الموعد بساعة، أي في :v1.', ['v1' => $event->starts_at->copy()->subMinutes(Event::CHECKIN_OPENS_BEFORE_MINUTES)->translatedFormat('l j F، g:i a')])
            : __('انتهى وقت تسجيل الحضور لهذا الحدث.');
    }

    private function authorizeHost(Request $request, Event $event): void
    {
        abort_unless($event->isHostedBy($request->user()), 403, __('ليس لديك صلاحية على هذا الحدث.'));
    }
}
