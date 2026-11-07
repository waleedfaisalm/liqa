<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBroadcastController extends Controller
{
    /** وجهات جاهزة: المفتاح => [الوصف, المسار] */
    public const TARGETS = [
        'events' => ['تصفّح الأحداث', '/events'],
        'create' => ['إنشاء حدث جديد', '/events/create'],
        'mine' => ['أحداثي', '/events/mine'],
        'dashboard' => ['لوحة التحكم', '/dashboard'],
        'saved' => ['الأحداث المحفوظة', '/saved'],
        'profile' => ['الملف الشخصي', '/profile'],
    ];

    public function create(): View
    {
        return view('admin.broadcast', [
            'targets' => self::TARGETS,
            'audience' => User::where('notifications_enabled', true)->whereNull('suspended_at')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:200'],
            'target' => ['nullable', 'in:'.implode(',', array_keys(self::TARGETS))],
        ], [
            'message.required' => 'يرجى كتابة نص الإشعار.',
            'message.min' => 'نص الإشعار قصير جدًا.',
            'message.max' => 'نص الإشعار طويل (الحد الأقصى 200 حرف).',
            'target.in' => 'الوجهة المختارة غير صحيحة.',
        ]);

        $count = 0;
        $now = now();

        User::where('notifications_enabled', true)->whereNull('suspended_at')->select('id')->chunkById(500, function ($users) use ($data, $now, &$count) {
            $rows = $users->map(fn ($u) => [
                'user_id' => $u->id,
                'type' => 'announcement',
                'message' => $data['message'],
                'url' => ! empty($data['target']) ? url(self::TARGETS[$data['target']][1]) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            UserNotification::insert($rows);
            $count += count($rows);
        });

        AdminLog::record($request->user(), 'broadcast.sent', null, null, null, "إلى {$count} عضوًا: ".$data['message']);

        return redirect()->route('admin.broadcast')->with('success', "أُرسل الإشعار إلى {$count} عضوًا.");
    }
}
