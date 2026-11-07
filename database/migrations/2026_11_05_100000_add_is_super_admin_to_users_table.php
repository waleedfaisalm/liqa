<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('is_admin');
        });

        // أقدم مدير موجود يصبح «المالك» تلقائيًا (يمكن تغييره بأمر liqa:make-admin --owner)
        $first = DB::table('users')->where('is_admin', true)->orderBy('id')->first();

        if ($first) {
            DB::table('users')->where('id', $first->id)->update(['is_super_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
