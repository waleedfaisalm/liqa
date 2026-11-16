<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** الدخول بحساب Google (OAuth 2.0 — تدفّق رمز التفويض) دون حزم إضافية */
class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function redirectUri(): string
    {
        return config('services.google.redirect') ? (string) config('services.google.redirect') : route('google.callback');
    }

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $state = Str::random(40);
        $request->session()->put('google_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $fail = fn (string $msg) => redirect()->route('login')->with('error', $msg);

        $expected = $request->session()->pull('google_state');
        if ($request->filled('error') || ! $request->filled('code') || ! $expected || ! hash_equals((string) $expected, (string) $request->query('state'))) {
            return $fail(__('تعذّر الدخول بحساب Google. حاول مرة أخرى.'));
        }

        try {
            $token = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->query('code'),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ]);

            if (! $token->ok() || ! $token->json('access_token')) {
                return $fail(__('تعذّر الدخول بحساب Google. حاول مرة أخرى.'));
            }

            $info = Http::withToken($token->json('access_token'))->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (\Throwable $e) {
            report($e);

            return $fail(__('تعذّر الدخول بحساب Google. حاول مرة أخرى.'));
        }

        $sub = (string) $info->json('sub');
        $email = Str::lower(trim((string) $info->json('email')));

        if (! $info->ok() || $sub === '' || $email === '' || ! $info->json('email_verified')) {
            return $fail(__('بريد حساب Google غير مفعّل أو غير متاح.'));
        }

        $user = User::where('google_id', $sub)->first() ?? User::where('email', $email)->first();

        if ($user) {
            if ($user->google_id !== $sub) {
                $user->forceFill(['google_id' => $sub])->save();
            }
            if (! $user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        } else {
            if (! Setting::enabled('registration_enabled')) {
                return $fail(__('التسجيل في المنصة مغلق مؤقتًا. يرجى المحاولة لاحقًا.'));
            }

            $name = Str::limit(trim((string) $info->json('name')) ?: Str::before($email, '@'), 80, '');
            $user = new User(['name' => $name, 'email' => $email, 'password' => Str::random(40)]);
            $user->forceFill(['google_id' => $sub, 'email_verified_at' => now()])->save();
        }

        if ($user->isSuspended()) {
            return $fail($user->suspensionMessage());
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('landing'))->with('success', __('مرحبًا بعودتك، تم تسجيل دخولك.'));
    }
}
