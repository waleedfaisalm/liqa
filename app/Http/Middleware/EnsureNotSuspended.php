<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** أي حساب موقوف يُسجَّل خروجه فورًا ولا يستطيع المتابعة. */
class EnsureNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuspended()) {
            $message = $user->suspensionMessage();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
