<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** تحويل الأنواع القديمة إلى التصنيفات الجديدة */
    public function up(): void
    {
        DB::table('events')->where('type', 'networking')->update(['type' => 'business']);
        DB::table('events')->whereIn('type', ['knowledge', 'coworking'])->update(['type' => 'other']);
    }

    public function down(): void
    {
        // لا رجوع: التصنيفات القديمة لم تعد مستخدمة
    }
};
