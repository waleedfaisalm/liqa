<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // بيانات تجريبية في بيئة التطوير المحلي بس
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
