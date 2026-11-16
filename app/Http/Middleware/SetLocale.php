<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SetLocale
{
    public const LOCALES = ['ar', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = 'ar';

        // لوحة الإدارة تبقى بالعربية
        if (! $request->is('admin', 'admin/*')) {
            $c = $request->cookie('liqa_locale');
            if (in_array($c, self::LOCALES, true)) {
                $locale = $c;
            }
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
