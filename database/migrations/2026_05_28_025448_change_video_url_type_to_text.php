<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->text('video_url')->nullable()->change();
        });
        Schema::table('legal_videos', function (Blueprint $table) {
            $table->text('video_url')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->string('video_url', 255)->nullable()->change();
        });
        Schema::table('legal_videos', function (Blueprint $table) {
            $table->string('video_url', 255)->change();
        });
    }
};
