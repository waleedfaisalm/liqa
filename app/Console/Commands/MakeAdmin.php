<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'liqa:make-admin {email} {--revoke : سحب صلاحية الإدارة} {--owner : جعله المالك (الحساب الأساسي المحمي)}';

    protected $description = 'منح أو سحب صلاحية الإدارة لمستخدم عبر بريده الإلكتروني';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('لا يوجد مستخدم بهذا البريد.');

            return self::FAILURE;
        }

        if ($this->option('owner') && ! $this->option('revoke')) {
            User::where('id', '!=', $user->id)->update(['is_super_admin' => false]);
            $user->forceFill(['is_admin' => true, 'is_super_admin' => true])->save();
            $this->info("تم تعيين {$user->name} مالكًا للمنصة (حساب محمي).");

            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        if ($this->option('revoke')) {
            $user->forceFill(['is_super_admin' => false])->save();
        }
        $this->info($user->is_admin ? "تم تعيين {$user->name} مديرًا." : "تم سحب صلاحية الإدارة من {$user->name}.");

        return self::SUCCESS;
    }
}
