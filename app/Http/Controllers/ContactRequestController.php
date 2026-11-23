<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactRequestController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user();

        return view('contacts', [
            'incoming' => ContactRequest::with(['from', 'event'])->where('to_user_id', $me->id)->where('status', ContactRequest::PENDING)->latest()->get(),
            'outgoing' => ContactRequest::with(['to', 'event'])->where('from_user_id', $me->id)->where('status', ContactRequest::PENDING)->latest()->get(),
            'connections' => ContactRequest::with(['from', 'to', 'event'])->where('status', ContactRequest::ACCEPTED)
                ->where(fn ($q) => $q->where('from_user_id', $me->id)->orWhere('to_user_id', $me->id))->latest()->get()
                ->map(fn ($c) => ['user' => $c->from_user_id === $me->id ? $c->to : $c->from, 'event' => $c->event]),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();

        if ($user->is($me) || $user->suspended_at || $user->is_admin) {
            return back()->with('error', __('لا يمكن إرسال طلب لهذا العضو.'));
        }

        $event = ContactRequest::sharedEvent($me, $user);
        if (! $event) {
            return back()->with('error', __('التواصل متاح فقط بين من حضروا نفس الحدث فعليًا.'));
        }

        if (ContactRequest::between($me, $user)) {
            return back()->with('error', __('يوجد طلب تواصل قائم بينكما بالفعل.'));
        }

        ContactRequest::create(['from_user_id' => $me->id, 'to_user_id' => $user->id, 'event_id' => $event->id]);

        UserNotification::send($user, 'contact_request',
            $me->name.' يرغب في التواصل معك بعد لقاء «'.$event->title.'».', route('contacts.index'));

        return back()->with('success', __('تم إرسال طلب التواصل.'));
    }

    public function decide(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        $me = $request->user();
        abort_unless($contactRequest->to_user_id === $me->id && $contactRequest->status === ContactRequest::PENDING, 403);

        $accept = $request->input('decision') === 'accept';
        $contactRequest->update(['status' => $accept ? ContactRequest::ACCEPTED : ContactRequest::DECLINED]);

        if ($accept) {
            UserNotification::send($contactRequest->from, 'contact_accepted',
                $me->name.' قبل طلب التواصل. يمكنك الآن رؤية بيانات التواصل.', route('contacts.index'));
        }

        return back()->with('success', $accept ? __('تم قبول الطلب. تظهر بيانات التواصل لكليكما.') : __('تم رفض الطلب.'));
    }

    public function cancel(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        abort_unless($contactRequest->from_user_id === $request->user()->id && $contactRequest->status === ContactRequest::PENDING, 403);
        $contactRequest->delete();

        return back()->with('success', __('تم إلغاء الطلب.'));
    }
}
