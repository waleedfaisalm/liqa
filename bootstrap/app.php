<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureNotSuspended;
use App\Http\Middleware\EnsureOnboarded;
use App\Http\Middleware\TrackLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // اللي مش مسجّل دخول يتحوّل لصفحة الدخول، واللي مسجّل يتحوّل للوحة التحكم
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('landing'));

        // خلف وسيط الاستضافة (HTTPS يُنهى عنده): الثقة بترويسات الوسيط ليُبنى الرابط والكوكيز بشكل صحيح
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [\App\Http\Middleware\LogSlowRequests::class, EnsureNotSuspended::class, TrackLastSeen::class, \App\Http\Middleware\RunDueReminders::class]);

        $middleware->alias([
            'onboarded' => EnsureOnboarded::class,
            'admin' => EnsureAdmin::class,
            'admin.can' => EnsureAdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
