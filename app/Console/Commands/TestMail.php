<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMail extends Command
{
    protected $signature = 'liqa:test-mail {email : البريد الذي ستصله رسالة التجربة}';

    protected $description = 'يرسل رسالة تجريبية للتأكد من إعدادات البريد في ملف .env';

    public function handle(): int
    {
        $this->line('طريقة الإرسال الحالية: '.config('mail.default').' — '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log: الرسائل تُكتب في ملف laravel.log ولا تُرسل فعليًا. غيّرها إلى smtp في ملف .env ثم نفّذ php artisan config:clear');

            return self::FAILURE;
        }

        try {
            Mail::raw('هذه رسالة تجريبية من منصة لقاء. إذا وصلتك فإعدادات البريد صحيحة.', function ($m) {
                $m->to($this->argument('email'))->subject('تجربة البريد — لقاء');
            });
        } catch (Throwable $e) {
            $this->error('فشل الإرسال: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('تم الإرسال. راجع الوارد والرسائل غير المرغوب فيها.');

        return self::SUCCESS;
    }
}
