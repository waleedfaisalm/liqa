<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique();
            $table->unsignedTinyInteger('score');
            $table->json('parts')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_snapshots');
    }
};
