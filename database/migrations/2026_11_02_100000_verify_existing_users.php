<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** الحسابات الموجودة قبل تفعيل نظام التوثيق تُعتبر موثّقة حتى لا تُقفل. */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
