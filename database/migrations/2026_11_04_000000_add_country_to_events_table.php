<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('country', 60)->nullable()->after('mode');
        });

        // تعبئة الدولة للأحداث الحالية عندما تكون المدينة تخص دولة واحدة فقط في القائمة
        foreach (DB::table('events')->whereNotNull('city')->select('id', 'city')->get() as $event) {
            $countries = array_keys(array_filter(config('places'), fn ($cities) => in_array($event->city, $cities, true)));

            if (count($countries) === 1) {
                DB::table('events')->where('id', $event->id)->update(['country' => $countries[0]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
