<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('admin_permissions')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admin_permissions', 'two_factor_secret', 'two_factor_confirmed_at', 'two_factor_recovery_codes']);
        });
        Schema::dropIfExists('settings');
    }
};
