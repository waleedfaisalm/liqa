<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** أمان حساب المدير: التحقق بخطوتين */
class AdminSecurityController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $secret = null;

        if (! $user->hasTwoFactor()) {
            $secret = $request->session()->get('admin_2fa_setup');
            if (! $secret) {
                $secret = Totp::generateSecret();
                $request->session()->put('admin_2fa_setup', $secret);
            }
        }

        return view('admin.security', [
            'enabled' => $user->hasTwoFactor(),
            'secret' => $secret,
            'uri' => $secret ? Totp::uri($secret, $user->email) : null,
            'recoveryCodes' => $request->session()->get('recovery_codes'),
            'remaining' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get('admin_2fa_setup');

        $request->validate(['code' => ['required', 'string', 'max:10']], ['code.required' => 'أدخل الرمز المكوّن من 6 أرقام.']);

        if ($user->hasTwoFactor() || ! $secret || ! Totp::verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'الرمز غير صحيح. تأكد من الساعة في هاتفك وحاول مرة أخرى.']);
        }

        [$plain, $hashed] = $this->makeRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $hashed,
        ])->save();

        $request->session()->forget('admin_2fa_setup');
        AdminLog::record($user, 'admin.2fa_enabled');

        return redirect()->route('admin.security')->with('recovery_codes', $plain)->with('success', 'تم تفعيل التحقق بخطوتين.');
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactor() || ! $this->passwordOk($request)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        [$plain, $hashed] = $this->makeRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $hashed])->save();

        return redirect()->route('admin.security')->with('recovery_codes', $plain)->with('success', 'تم إنشاء رموز استرداد جديدة، وأُلغيت القديمة.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->passwordOk($request)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        AdminLog::record($user, 'admin.2fa_disabled');

        return redirect()->route('admin.security')->with('success', 'تم إيقاف التحقق بخطوتين.');
    }

    private function passwordOk(Request $request): bool
    {
        return Hash::check((string) $request->input('password'), $request->user()->password);
    }

    /** @return array{0: array<int,string>, 1: array<int,string>} */
    private function makeRecoveryCodes(): array
    {
        $plain = collect(range(1, 8))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();

        return [$plain, array_map(fn ($c) => hash('sha256', $c), $plain)];
    }
}
