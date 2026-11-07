<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'registration' => Setting::enabled('registration_enabled'),
            'eventCreation' => Setting::enabled('event_creation_enabled'),
            'announcement' => (string) Setting::get('announcement'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'announcement' => ['nullable', 'string', 'max:200'],
        ], [
            'announcement.max' => 'نص الإعلان طويل (الحد الأقصى 200 حرف).',
        ]);

        $new = [
            'registration_enabled' => $request->boolean('registration_enabled') ? '1' : '0',
            'event_creation_enabled' => $request->boolean('event_creation_enabled') ? '1' : '0',
            'announcement' => trim((string) ($data['announcement'] ?? '')),
        ];

        $changes = [];
        foreach ($new as $key => $value) {
            if ((string) Setting::get($key) !== $value) {
                $changes[] = match ($key) {
                    'registration_enabled' => 'التسجيل: '.($value === '1' ? 'مفتوح' : 'مغلق'),
                    'event_creation_enabled' => 'إنشاء الأحداث: '.($value === '1' ? 'مفتوح' : 'مغلق'),
                    'announcement' => $value === '' ? 'إزالة الإعلان' : 'تحديث الإعلان',
                };
            }
            Setting::put($key, $value);
        }

        if ($changes) {
            AdminLog::record($request->user(), 'settings.updated', null, null, null, implode('، ', $changes));
        }

        return back()->with('success', 'تم حفظ إعدادات المنصة.');
    }
}
