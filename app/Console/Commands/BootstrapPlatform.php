<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * يجهّز المنصة عند أول تشغيل على الاستضافة: ينشئ حساب المالك من المتغيرات
 * OWNER_EMAIL و OWNER_PASSWORD و OWNER_NAME (آمن للتكرار).
 */
class BootstrapPlatform extends Command
{
    protected $signature = 'liqa:bootstrap';

    protected $description = 'إنشاء حساب المالك من متغيرات البيئة (OWNER_EMAIL / OWNER_PASSWORD / OWNER_NAME)';

    public function handle(): int
    {
        $email = strtolower(trim((string) env('OWNER_EMAIL')));
        $password = (string) env('OWNER_PASSWORD');

        if ($email === '' || strlen($password) < 8) {
            $this->warn('لم يُنشأ حساب المالك: اضبط OWNER_EMAIL و OWNER_PASSWORD (8 أحرف على الأقل).');

            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first() ?? new User(['email' => $email]);
        $isNew = ! $user->exists;

        $user->forceFill([
            'name' => $user->name ?: (env('OWNER_NAME') ?: 'مالك المنصة'),
            'email' => $email,
            'password' => $password,
            'specialty' => $user->specialty ?: 'مطوّر',
            'onboarded_at' => $user->onboarded_at ?: now(),
            'email_verified_at' => $user->email_verified_at ?: now(),
            'is_admin' => true,
            'is_super_admin' => true,
        ])->save();

        User::where('id', '!=', $user->id)->update(['is_super_admin' => false]);

        $this->info(($isNew ? 'أُنشئ' : 'حُدّث').' حساب المالك: '.$email);

        return self::SUCCESS;
    }
}
