<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * جدولة بديلة للاستضافات التي لا تشغّل المهام المجدولة:
 * بعد إرسال الاستجابة، وكل 10 دقائق كحد أقصى، تُشغَّل تذكيرات الأحداث.
 */
class RunDueReminders
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->is('up', 'avatar/*', 'build/*')) {
            return;
        }

        try {
            if (! Cache::add('liqa:reminders-lock', 1, 600)) {
                return;
            }

            Artisan::call('liqa:send-reminders');
        } catch (\Throwable $e) {
            Log::warning('reminders failed: '.$e->getMessage());
        }
    }
}
