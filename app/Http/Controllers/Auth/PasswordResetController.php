<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /** شاشة "نسيت كلمة السر" */
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    /** إرسال رابط الاستعادة على الإيميل */
    public function email(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink($request->only('email'));

        // نفس الرد دايماً، عشان محدش يعرف مين مسجّل عندنا ومين لأ
        return back()->with('status', 'إذا كان البريد الإلكتروني مسجّلًا لدينا، فسيصلك رابط الاستعادة خلال دقائق. إن لم تجده فتفقد مجلد الرسائل غير المرغوب فيها (Spam).');
    }

    /** شاشة تعيين كلمة سر جديدة (من الرابط) */
    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /** حفظ كلمة السر الجديدة */
    public function update(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // وصول المستخدم لرابط الاستعادة يثبت ملكيته للبريد
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === 'passwords.reset') {
            return redirect()->route('login')->with('status', 'تم تغيير كلمة السر. يمكنك تسجيل الدخول بكلمة السر الجديدة.');
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->onlyInput('email');
    }
}
