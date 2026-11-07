<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /** شاشة "يرجى تفعيل بريدك" */
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email');
    }

    /** الرابط الموقّع القادم من البريد */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()->route('home')->with('success', 'تم تفعيل بريدك الإلكتروني بنجاح. أهلًا بك في لقاء.');
    }

    /** إعادة إرسال رابط التفعيل */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'أُرسل رابط تفعيل جديد إلى بريدك الإلكتروني.');
    }
}
