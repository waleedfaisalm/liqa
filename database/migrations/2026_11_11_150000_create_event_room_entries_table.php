<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_room_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 12)->default('waiting'); // waiting | admitted | denied | removed
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'status']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('room_lobby')->default(true); // غرفة الانتظار مفعّلة افتراضيًا
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('room_lobby'));
        Schema::dropIfExists('event_room_entries');
    }
};
