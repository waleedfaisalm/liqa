<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signup_ip_hash', 64)->nullable()->index(); // بصمة مشفّرة للـIP، لا يمكن استرجاع العنوان منها
            $table->unsignedTinyInteger('risk_score')->nullable()->index();
            $table->json('risk_flags')->nullable();
            $table->timestamp('risk_cleared_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['signup_ip_hash', 'risk_score', 'risk_flags', 'risk_cleared_at']));
    }
};
