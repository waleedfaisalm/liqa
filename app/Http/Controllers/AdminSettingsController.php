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
            'auto' => collect(\App\Support\Automation::DEFAULTS)->map(fn ($d, $k) => \App\Support\Automation::value($k))->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'announcement' => ['nullable', 'string', 'max:200'],
            'auto_reactivation_days' => ['required', 'integer', 'min:7', 'max:180'],
            'auto_report_threshold' => ['required', 'integer', 'min:2', 'max:10'],
            'auto_rating_delay' => ['required', 'integer', 'min:0', 'max:1440'],
            'auto_absence_strikes' => ['required', 'integer', 'min:2', 'max:10'],
            'auto_low_reg_percent' => ['required', 'integer', 'min:5', 'max:90'],
            'auto_low_reg_days' => ['required', 'integer', 'min:1', 'max:14'],
            'auto_risk_threshold' => ['required', 'integer', 'min:30', 'max:95'],
        ], [
            'announcement.max' => 'نص الإعلان طويل (الحد الأقصى 200 حرف).',
        ]);

        $new = [
            'registration_enabled' => $request->boolean('registration_enabled') ? '1' : '0',
            'event_creation_enabled' => $request->boolean('event_creation_enabled') ? '1' : '0',
            'announcement' => trim((string) ($data['announcement'] ?? '')),
            'auto_reactivation' => $request->boolean('auto_reactivation') ? '1' : '0',
            'auto_reactivation_days' => (string) $data['auto_reactivation_days'],
            'auto_report_alert' => $request->boolean('auto_report_alert') ? '1' : '0',
            'auto_report_threshold' => (string) $data['auto_report_threshold'],
            'auto_rating_delay' => (string) $data['auto_rating_delay'],
            'auto_absence' => $request->boolean('auto_absence') ? '1' : '0',
            'auto_absence_strikes' => (string) $data['auto_absence_strikes'],
            'auto_low_reg' => $request->boolean('auto_low_reg') ? '1' : '0',
            'auto_low_reg_percent' => (string) $data['auto_low_reg_percent'],
            'auto_low_reg_days' => (string) $data['auto_low_reg_days'],
            'auto_fake_detection' => $request->boolean('auto_fake_detection') ? '1' : '0',
            'auto_risk_threshold' => (string) $data['auto_risk_threshold'],
        ];

        $changes = [];
        foreach ($new as $key => $value) {
            if ((string) (Setting::get($key) ?? ($key === 'announcement' ? '' : (\App\Support\Automation::DEFAULTS[$key] ?? ''))) !== $value) {
                $changes[] = match ($key) {
                    'registration_enabled' => 'التسجيل: '.($value === '1' ? 'مفتوح' : 'مغلق'),
                    'event_creation_enabled' => 'إنشاء الأحداث: '.($value === '1' ? 'مفتوح' : 'مغلق'),
                    'announcement' => $value === '' ? 'إزالة الإعلان' : 'تحديث الإعلان',
                    'auto_reactivation' => 'رسالة إعادة التنشيط: '.($value === '1' ? 'مفعّلة' : 'معطّلة'),
                    'auto_fake_detection' => 'كشف الحسابات الوهمية: '.($value === '1' ? 'مفعّل' : 'معطّل'),
                    'auto_report_alert' => 'تنبيه البلاغات المتكررة: '.($value === '1' ? 'مفعّل' : 'معطّل'),
                    default => 'تعديل إعداد الأتمتة: '.$key.' = '.$value,
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
