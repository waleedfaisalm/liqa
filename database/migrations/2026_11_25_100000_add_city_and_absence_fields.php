<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city', 60)->nullable()->index();
            }
            if (! Schema::hasColumn('users', 'join_restricted_until')) {
                $table->timestamp('join_restricted_until')->nullable();
            }
            if (! Schema::hasColumn('users', 'join_restriction_reason')) {
                $table->string('join_restriction_reason', 200)->nullable();
            }
        });

        Schema::table('events', function (Blueprint $table) {
            foreach (['absence_processed_at', 'low_reg_alerted_at', 'seats_hint_sent_at'] as $col) {
                if (! Schema::hasColumn('events', $col)) {
                    $table->timestamp($col)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['city', 'join_restricted_until', 'join_restriction_reason']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['absence_processed_at', 'low_reg_alerted_at', 'seats_hint_sent_at']);
        });
    }
};
