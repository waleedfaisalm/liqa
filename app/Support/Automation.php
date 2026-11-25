<?php

namespace App\Support;

use App\Models\Setting;

/** إعدادات الأتمتة الآلية (تُضبط من إعدادات المنصة) مع قيم افتراضية */
class Automation
{
    public const DEFAULTS = [
        'auto_reactivation' => '1',
        'auto_reactivation_days' => '30',
        'auto_report_alert' => '1',
        'auto_report_threshold' => '2',
        'auto_rating_delay' => '60',
        'auto_fake_detection' => '1',
        'auto_risk_threshold' => '60',
    ];

    public static function value(string $key): string
    {
        return Setting::get($key) ?? self::DEFAULTS[$key];
    }

    public static function on(string $key): bool
    {
        return self::value($key) === '1';
    }

    public static function int(string $key): int
    {
        return (int) self::value($key);
    }
}
