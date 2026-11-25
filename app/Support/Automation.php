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
        'auto_absence' => '1',
        'auto_absence_strikes' => '3',
        'auto_low_reg' => '1',
        'auto_low_reg_percent' => '30',
        'auto_low_reg_days' => '3',
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
