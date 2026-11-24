<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** مركز تنبيهات الإدارة: بلاغات عاجلة، حسابات عالية الخطورة */
class AdminAlertsController extends Controller
{
    public function index(Request $request): View
    {
        $alerts = $request->user()->userNotifications()->whereIn('type', UserNotification::ADMIN_TYPES)->latest()->paginate(20);

        return view('admin.alerts', ['alerts' => $alerts]);
    }

    /** فتح تنبيه: يُعلَّم مقروءًا ثم يُحوَّل إلى صفحة المراجعة داخل لوحة الإدارة */
    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->isUnread()) {
            $notification->update(['read_at' => now()]);
        }

        $path = $notification->url ? parse_url($notification->url, PHP_URL_PATH) : null;

        return $path && str_starts_with($path, '/admin')
            ? redirect($request->getSchemeAndHttpHost().$path)
            : redirect()->route('admin.alerts');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->userNotifications()->whereIn('type', UserNotification::ADMIN_TYPES)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'تم تعليم كل التنبيهات كمقروءة.');
    }
}
