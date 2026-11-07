<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحمي لوحة الإدارة: يعمل بحارس «admin» المستقل، ويحوّل غير المسجّلين إلى شاشة دخول الإدارة.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // كل ما بعده (request->user و auth) يستخدم حارس الإدارة
        Auth::shouldUse('admin');

        $user = Auth::guard('admin')->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->isAdmin() || $user->isSuspended()) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')->withErrors(['email' => 'ليست لديك صلاحية الدخول إلى لوحة الإدارة.']);
        }

        return $next($request);
    }
}
