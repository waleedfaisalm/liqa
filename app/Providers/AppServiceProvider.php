<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // رسائل البريد بالعربية (واجهة من اليمين لليسار)
        VerifyEmail::toMailUsing(fn ($notifiable, string $url) => (new MailMessage)
            ->subject('فعّل بريدك الإلكتروني في لقاء')
            ->view('emails.action', [
                'heading' => 'مرحبًا '.$notifiable->name,
                'lines' => ['شكرًا لانضمامك إلى لقاء. يرجى الضغط على الزر أدناه لتفعيل بريدك الإلكتروني ومتابعة استخدام المنصة.'],
                'button' => 'تفعيل البريد الإلكتروني',
                'url' => $url,
                'footnote' => 'إذا لم تُنشئ حسابًا في لقاء، يمكنك تجاهل هذه الرسالة.',
            ]));

        ResetPassword::toMailUsing(fn ($notifiable, string $token) => (new MailMessage)
            ->subject('استعادة كلمة السر في لقاء')
            ->view('emails.action', [
                'heading' => 'استعادة كلمة السر',
                'lines' => [
                    'وصلنا طلب لإعادة تعيين كلمة السر لحسابك. اضغط على الزر أدناه لاختيار كلمة سر جديدة.',
                    'هذا الرابط صالح لمدة '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' دقيقة.',
                ],
                'button' => 'تعيين كلمة سر جديدة',
                'url' => url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)),
                'footnote' => 'إذا لم تطلب ذلك، يمكنك تجاهل هذه الرسالة ولن يتغير شيء في حسابك.',
            ]));
    }
}
