<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (Auth::user()->isSuspended()) {
                $suspendedMessage = Auth::user()->suspensionMessage();
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => $suspendedMessage])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('landing'))
                ->with('success', 'مرحبًا بعودتك، تم تسجيل دخولك.');
        }

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            DB::table('users')->where('id', $request->user()->id)->update(['last_seen_at' => null]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('status', 'تم تسجيل الخروج.');
    }
}
