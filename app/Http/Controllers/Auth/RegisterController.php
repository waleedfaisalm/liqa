<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! Setting::enabled('registration_enabled')) {
            return redirect()->route('login')->with('error', 'التسجيل في المنصة مغلق مؤقتًا. يرجى المحاولة لاحقًا.');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Setting::enabled('registration_enabled')) {
            return redirect()->route('login')->with('error', 'التسجيل في المنصة مغلق مؤقتًا. يرجى المحاولة لاحقًا.');
        }

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $user = User::create($data);

        // وضع التجربة: تفعيل الحساب مباشرة دون بريد (للاستضافة التجريبية التي لا ترسل بريدًا)
        if (config('liqa.auto_verify')) {
            $user->forceFill(['email_verified_at' => now()])->save();
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('home')->with('success', 'مرحبًا '.$user->name.'، تم إنشاء حسابك.');
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', 'مرحبًا '.$user->name.'، تم إنشاء حسابك. أرسلنا إليك رابط تفعيل على بريدك الإلكتروني.');
    }
}
