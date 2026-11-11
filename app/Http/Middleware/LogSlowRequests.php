<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** يسجّل في السجل أي طلب أبطأ من 700 مللي ثانية: زمنه الكلي، وعدد استعلامات القاعدة وزمنها. */
class LogSlowRequests
{
    private static float $start = 0;
    private static int $queries = 0;
    private static float $dbMs = 0;
    private static float $slowestMs = 0;
    private static string $slowestSql = '';

    public function handle(Request $request, Closure $next): Response
    {
        self::$start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        self::$queries = 0;
        self::$dbMs = 0;
        self::$slowestMs = 0;
        self::$slowestSql = '';

        DB::listen(function ($q) {
            self::$queries++;
            self::$dbMs += $q->time;
            if ($q->time > self::$slowestMs) {
                self::$slowestMs = $q->time;
                self::$slowestSql = mb_substr($q->sql, 0, 120);
            }
        });

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->is('up', 'build/*', 'brand/*', 'avatar/*')) {
            return;
        }

        $totalMs = (microtime(true) - self::$start) * 1000;

        if ($totalMs < 700) {
            return;
        }

        Log::warning('SLOW '.$request->method().' /'.$request->path().' total='.round($totalMs).'ms'
            .' db='.round(self::$dbMs).'ms queries='.self::$queries
            .' slowest='.round(self::$slowestMs).'ms ['.self::$slowestSql.']');
    }
}
