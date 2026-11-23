<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_attendances', function (Blueprint $table) {
            $table->string('method', 10)->nullable(); // room | qr | manual
        });
    }

    public function down(): void
    {
        Schema::table('event_attendances', fn (Blueprint $table) => $table->dropColumn('method'));
    }
};
