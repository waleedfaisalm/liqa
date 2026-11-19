<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->userNotifications()
            ->latest()
            ->paginate(15);

        return view('notifications', [
            'notifications' => $notifications,
            'unread' => $request->user()->unreadNotificationsCount(),
        ]);
    }

    /** فتح إشعار: يُعلَّم كمقروء ثم يُحوَّل إلى الرابط المرتبط به */
    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->isUnread()) {
            $notification->update(['read_at' => now()]);
        }

        // نستخدم المسار فقط (بدون النطاق) حتى يعمل الرابط سواء فتحت الموقع عبر
        // localhost أو 127.0.0.1 أو نطاق الاستضافة، ولا يمكن التحويل إلى موقع خارجي.
        $parts = $notification->url ? parse_url($notification->url) : false;
        if ($parts && isset($parts['path']) && str_starts_with($parts['path'], '/')) {
            $target = $parts['path']
                .(isset($parts['query']) ? '?'.$parts['query'] : '')
                .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');

            return redirect($request->getSchemeAndHttpHost().$target);
        }

        return redirect()->route('notifications');
    }

    /** حذف نهائي لإشعار واحد (للمالك فقط) */
    public function destroy(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return redirect()->route('notifications')->with('success', __('تم حذف الإشعار.'));
    }

    /** حذف نهائي لإشعارات المستخدم: المقروءة فقط (scope=read) أو جميعها */
    public function clear(Request $request): RedirectResponse
    {
        $query = $request->user()->userNotifications();

        if ($request->input('scope') === 'read') {
            $query->whereNotNull('read_at');
            $message = __('تم حذف الإشعارات المقروءة.');
        } else {
            $message = __('تم حذف جميع الإشعارات.');
        }

        $query->delete();

        return redirect()->route('notifications')->with('success', $message);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return redirect()->route('notifications')->with('success', __('تم تعليم جميع الإشعارات كمقروءة.'));
    }
}
