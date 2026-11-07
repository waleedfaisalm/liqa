<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * أي عضو لسه ما كمّلش شاشة Onboarding بيتحوّل ليها الأول.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isOnboarded()) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
