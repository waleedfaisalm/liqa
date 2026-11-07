<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- الأحداث ----------
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // المنظّم (الهوست)
            $table->string('title', 120);
            $table->string('type', 20);                 // networking | knowledge | coworking
            $table->string('mode', 20);                 // in_person | online
            $table->string('location', 191);            // مكان أو رابط
            $table->dateTime('starts_at')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(120);
            $table->unsignedSmallInteger('capacity');   // عدد المقاعد (من غير المنظّم)
            $table->text('description')->nullable();
            $table->string('status', 20)->default('published'); // published | cancelled
            $table->timestamps();
        });

        // ---------- طلبات الانضمام ----------
        Schema::create('event_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | accepted | rejected
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        // ---------- التعليقات ----------
        Schema::create('event_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // ---------- التقييمات (متبادلة بين المشاركين) ----------
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');       // 1..5
            $table->json('tags')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'rater_id', 'ratee_id']);
        });

        // ---------- الإشعارات ----------
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);                 // join_request | accepted | rejected | event_updated | event_cancelled | rating
            $table->string('message', 255);
            $table->string('url', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        // ---------- البلاغات ----------
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type', 10);          // user | event
            $table->unsignedBigInteger('target_id');
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open'); // open | reviewed
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('event_comments');
        Schema::dropIfExists('event_requests');
        Schema::dropIfExists('events');
    }
};
