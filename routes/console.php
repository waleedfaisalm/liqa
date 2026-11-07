<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تذكيرات الأحداث وإشعارات التقييم (تشغيل محلي: php artisan schedule:work)
Schedule::command('liqa:send-reminders')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('liqa:backup')->dailyAt('03:00');
