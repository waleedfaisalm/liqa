<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_room_entries', function (Blueprint $table) {
            $table->boolean('is_cohost')->default(false);  // منظّم مساعد
            $table->boolean('via_link')->default(false);   // دخل بالرابط وهو ليس مشاركًا مقبولًا في الحدث
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('room_link')->default(true);   // السماح بالدخول بالرابط (بموافقة المنظّم)
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('room_link'));
        Schema::table('event_room_entries', fn (Blueprint $table) => $table->dropColumn(['is_cohost', 'via_link']));
    }
};
