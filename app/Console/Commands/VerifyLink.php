<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class VerifyLink extends Command
{
    protected $signature = 'liqa:links {email : بريد المستخدم}';

    protected $description = 'يطبع رابط التفعيل ورابط استعادة كلمة السر لمستخدم (للتجربة المحلية بدون بريد فعلي)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('لا يوجد مستخدم بهذا البريد.');

            return self::FAILURE;
        }

        $this->line('رابط التفعيل:');
        $this->info(URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]));

        $token = Password::createToken($user);
        $this->line('');
        $this->line('رابط استعادة كلمة السر:');
        $this->info(url(route('password.reset', ['token' => $token, 'email' => $user->email], false)));

        return self::SUCCESS;
    }
}
