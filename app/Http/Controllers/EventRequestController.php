<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventRequestController extends Controller
{
    /** طلب انضمام */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();

        if ($event->isHostedBy($user)) {
            return $this->back($event, 'error', 'أنت منظّم هذا الحدث.');
        }

        if ($event->isCancelled()) {
            return $this->back($event, 'error', 'هذا الحدث ملغى.');
        }

        if ($event->starts_at->isPast()) {
            return $this->back($event, 'error', 'بدأ الحدث بالفعل، ولا يمكن تقديم طلب انضمام.');
        }

        if ($event->requestOf($user)) {
            return $this->back($event, 'error', 'لقد قدّمت طلب انضمام لهذا الحدث مسبقًا.');
        }

        if ($event->isFull()) {
            EventRequest::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => EventRequest::WAITLISTED,
            ]);

            return $this->back($event, 'success', 'اكتمل العدد، فأُضيفت إلى قائمة الانتظار. سنُعلمك فور توفّر مقعد.');
        }

        EventRequest::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => EventRequest::PENDING,
        ]);

        UserNotification::send(
            $event->host,
            'request_new',
            $user->name.' طلب الانضمام لحدث «'.$event->title.'».',
            route('events.show', $event),
        );

        return $this->back($event, 'success', 'تم إرسال طلبك. سيصلك إشعار عند ردّ المنظّم.');
    }

    /** سحب الطلب أو الانسحاب من الحدث */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();
        $joinRequest = $event->requestOf($user);

        if (! $joinRequest) {
            return $this->back($event, 'error', 'لا يوجد طلب انضمام باسمك لهذا الحدث.');
        }

        if ($event->starts_at->isPast()) {
            return $this->back($event, 'error', 'بدأ الحدث، ولا يمكن الانسحاب حاليًا.');
        }

        $wasAccepted = $joinRequest->status === EventRequest::ACCEPTED;
        $wasRejected = $joinRequest->status === EventRequest::REJECTED;
        $wasWaitlisted = $joinRequest->status === EventRequest::WAITLISTED;

        if ($wasRejected) {
            // الرفض بيفضل مسجّل، مش بيتمسح
            return $this->back($event, 'error', 'طلبك مرفوض، فلا يوجد ما يمكن سحبه.');
        }

        $joinRequest->delete();

        if ($wasWaitlisted) {
            return $this->back($event, 'success', 'تم خروجك من قائمة الانتظار.');
        }

        if ($wasAccepted) {
            $event->promoteWaitlist();
        }

        UserNotification::send(
            $event->host,
            'request_withdrawn',
            $user->name.($wasAccepted ? ' انسحب من' : ' سحب طلبه للانضمام إلى').' حدث «'.$event->title.'».',
            route('events.show', $event),
        );

        return $this->back($event, 'success', $wasAccepted ? 'تم انسحابك من الحدث.' : 'تم سحب طلبك.');
    }

    /** المنظّم يقبل أو يرفض طلب */
    public function decide(Request $request, Event $event, EventRequest $eventRequest): RedirectResponse
    {
        abort_unless($event->isHostedBy($request->user()), 403, 'ليس لديك صلاحية على هذا الحدث.');
        abort_unless($eventRequest->event_id === $event->id, 404);

        $decision = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
        ])['decision'];

        if ($event->isCancelled() || $event->hasEnded()) {
            return $this->back($event, 'error', 'الحدث ملغى أو منتهٍ، ولا يمكن تعديل الطلبات.');
        }

        $applicant = $eventRequest->user;

        if ($decision === 'accept') {
            if ($eventRequest->status === EventRequest::WAITLISTED) {
                return $this->back($event, 'error', 'هذا الطلب في قائمة الانتظار، وسيُنقل تلقائيًا عند توفّر مقعد.');
            }

            $error = DB::transaction(function () use ($event, $eventRequest) {
                if ($eventRequest->status === EventRequest::ACCEPTED) {
                    return 'هذا الطلب مقبول بالفعل.';
                }

                $taken = $event->requests()->where('status', EventRequest::ACCEPTED)->count();

                if ($taken >= $event->capacity) {
                    return 'اكتمل العدد، ولا يمكن قبول مشاركين آخرين.';
                }

                $eventRequest->update(['status' => EventRequest::ACCEPTED]);

                return null;
            });

            if ($error) {
                return $this->back($event, 'error', $error);
            }

            UserNotification::send(
                $applicant,
                'request_accepted',
                'تم قبولك في حدث «'.$event->title.'». بانتظارك في '.$event->whenLabel().'.',
                route('events.show', $event),
            );

            return $this->back($event, 'success', 'تم قبول '.$applicant->name.'.');
        }

        if ($eventRequest->status === EventRequest::REJECTED) {
            return $this->back($event, 'error', 'هذا الطلب مرفوض بالفعل.');
        }

        $wasAccepted = $eventRequest->status === EventRequest::ACCEPTED;
        $eventRequest->update(['status' => EventRequest::REJECTED]);

        if ($wasAccepted) {
            $event->promoteWaitlist();
        }

        UserNotification::send(
            $applicant,
            'request_rejected',
            'نأسف، لم يقبل المنظّم طلبك لحدث «'.$event->title.'».',
            route('events.show', $event),
        );

        return $this->back($event, 'success', 'تم رفض طلب '.$applicant->name.'.');
    }

    private function back(Event $event, string $type, string $message): RedirectResponse
    {
        return redirect()->route('events.show', $event)->with($type, $message);
    }
}
