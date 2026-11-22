<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('link_linkedin', 255)->nullable();
            $table->string('link_github', 255)->nullable();
            $table->string('link_behance', 255)->nullable();
            $table->string('link_website', 255)->nullable();
            $table->timestamp('verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['link_linkedin', 'link_github', 'link_behance', 'link_website', 'verified_at']);
        });
    }
};
