<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** الخطوة الثانية من دخول الإدارة */
class AdminTwoFactorController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return $this->pending($request) ? view('admin.two-factor') : redirect()->route('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('admin.login')->withErrors(['email' => 'انتهت مهلة التحقق، أعد تسجيل الدخول.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'أدخل رمز التحقق.']);

        $user = User::find($pending['id']);

        if (! $user || ! $user->isAdmin() || $user->isSuspended() || ! $user->hasTwoFactor()) {
            $request->session()->forget('admin_2fa');

            return redirect()->route('admin.login')->withErrors(['email' => 'تعذّر إكمال تسجيل الدخول.']);
        }

        $code = trim((string) $request->input('code'));
        $valid = Totp::verify($user->two_factor_secret, $code) || $this->useRecoveryCode($user, $code);

        if (! $valid) {
            AdminLog::record($user, 'admin.2fa_failed');

            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        $request->session()->forget('admin_2fa');
        Auth::guard('admin')->login($user, $pending['remember']);
        $request->session()->regenerate();
        AdminLog::record($user, 'admin.login', null, null, null, 'بالتحقق بخطوتين');

        return redirect()->intended(route('admin.dashboard'));
    }

    private function pending(Request $request): ?array
    {
        $p = $request->session()->get('admin_2fa');

        if (! is_array($p) || ($p['at'] ?? 0) < time() - 300) {
            $request->session()->forget('admin_2fa');

            return null;
        }

        return $p;
    }

    private function useRecoveryCode(User $user, string $code): bool
    {
        $hash = hash('sha256', strtolower($code));
        $codes = $user->two_factor_recovery_codes ?? [];
        $index = array_search($hash, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }
}
