<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** يتحقق من صلاحية المدير في قسم معيّن من لوحة الإدارة */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'ليست لديك صلاحية الوصول إلى هذا القسم.');
        }

        return $next($request);
    }
}
