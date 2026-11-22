<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('speaker_name', 120)->nullable();
            $table->string('speaker_bio', 300)->nullable();
            $table->text('agenda')->nullable(); // JSON: [{title, minutes}]
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['speaker_name', 'speaker_bio', 'agenda']);
        });
    }
};
