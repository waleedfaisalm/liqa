<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // رسالة واحدة لكل الحالات حتى لا يُعرف من هو مدير ومن ليس كذلك
        $valid = $user
            && Hash::check($credentials['password'], $user->password)
            && $user->isAdmin()
            && ! $user->isSuspended();

        if (! $valid) {
            AdminLog::record(null, 'admin.login_failed', null, null, null, 'البريد المُدخل: '.$credentials['email']);

            return back()
                ->withErrors(['email' => 'بيانات الدخول غير صحيحة، أو لا تملك صلاحية الإدارة.'])
                ->onlyInput('email');
        }

        if ($user->hasTwoFactor()) {
            $request->session()->regenerate();
            $request->session()->put('admin_2fa', ['id' => $user->id, 'remember' => $request->boolean('remember'), 'at' => time()]);

            return redirect()->route('admin.two-factor');
        }

        Auth::guard('admin')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        AdminLog::record($user, 'admin.login');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminLog::record(Auth::guard('admin')->user(), 'admin.logout');
        Auth::guard('admin')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'تم تسجيل الخروج من لوحة الإدارة.');
    }
}
