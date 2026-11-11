<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Event;
use App\Models\EventRequest;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['upcoming', 'past', 'cancelled', 'all'], true) ? $request->query('tab') : 'upcoming';
        $search = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->with('host')
            ->withCount(['acceptedRequests', 'requests'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$search.'%')
                ->orWhereHas('host', fn ($h) => $h->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))))
            ->when($tab === 'upcoming', fn ($q) => $q->where('status', Event::PUBLISHED)->where('starts_at', '>=', now())->orderBy('starts_at'))
            ->when($tab === 'past', fn ($q) => $q->where('status', Event::PUBLISHED)->where('starts_at', '<', now())->orderByDesc('starts_at'))
            ->when($tab === 'cancelled', fn ($q) => $q->where('status', Event::CANCELLED)->orderByDesc('starts_at'))
            ->when($tab === 'all', fn ($q) => $q->orderByDesc('starts_at'))
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'upcoming' => Event::upcoming()->count(),
            'past' => Event::where('status', Event::PUBLISHED)->where('starts_at', '<', now())->count(),
            'cancelled' => Event::where('status', Event::CANCELLED)->count(),
            'all' => Event::count(),
        ];

        return view('admin.events', compact('events', 'tab', 'search', 'counts'));
    }

    /** إلغاء حدث قادم من قِبل الإدارة مع إشعار المنظّم والمشاركين */
    public function cancel(Request $request, Event $event): RedirectResponse
    {
        if ($event->isCancelled()) {
            return back()->with('error', 'هذا الحدث ملغى بالفعل.');
        }

        if ($event->hasEnded()) {
            return back()->with('error', 'لا يمكن إلغاء حدث منتهٍ، حفاظًا على التقييمات.');
        }

        $event->status = Event::CANCELLED;
        $event->save();

        AdminLog::record($request->user(), 'event.cancelled', 'event', $event->id, $event->title, 'المنظّم: '.$event->host->name);

        $affected = $event->requests()
            ->whereIn('status', [EventRequest::PENDING, EventRequest::ACCEPTED, EventRequest::WAITLISTED])
            ->with('user')->get()->pluck('user');

        $affected->push($event->host)->unique('id')->each(fn ($user) => UserNotification::send(
            $user,
            'event_cancelled',
            'تم إلغاء حدث «'.$event->title.'» من قِبل إدارة المنصة.',
            route('events.show', $event),
        ));

        return back()->with('success', 'تم إلغاء الحدث وإشعار المعنيين.');
    }

    /** حذف حدث نهائيًا (مع طلباته وتعليقاته وتقييماته) دون المساس بحساب المنظّم */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $title = $event->title;
        $host = $event->host;

        // إبلاغ المعنيين إن كان الحدث قادمًا ولم يُلغَ
        if (! $event->isCancelled() && ! $event->hasEnded()) {
            $event->requests()
                ->whereIn('status', [EventRequest::PENDING, EventRequest::ACCEPTED, EventRequest::WAITLISTED])
                ->with('user')->get()->pluck('user')->push($host)->filter()->unique('id')
                ->each(fn ($user) => UserNotification::send(
                    $user,
                    'event_cancelled',
                    'تم حذف حدث «'.$title.'» من قِبل إدارة المنصة.',
                    route('events.index'),
                ));
        }

        AdminLog::record($request->user(), 'event.deleted', 'event', $event->id, $title, 'المنظّم: '.($host?->name ?? '—'));

        $event->delete();

        return redirect()->route('admin.events', array_filter(['tab' => $request->input('tab'), 'q' => $request->input('q')]))
            ->with('success', 'تم حذف الحدث «'.$title.'» نهائيًا.');
    }
}
