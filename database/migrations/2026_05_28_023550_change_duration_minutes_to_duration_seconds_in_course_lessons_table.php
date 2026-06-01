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
            if (Schema::hasColumn('course_lessons', 'duration_minutes') && !Schema::hasColumn('course_lessons', 'duration_seconds')) {
                $table->renameColumn('duration_minutes', 'duration_seconds');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            // Because the initial migration was modified to directly create duration_seconds,
            // we don't necessarily want to rename it back to duration_minutes unless it's strictly needed.
            // Leaving this empty or checking avoids errors.
            if (Schema::hasColumn('course_lessons', 'duration_seconds') && !Schema::hasColumn('course_lessons', 'duration_minutes')) {
                // $table->renameColumn('duration_seconds', 'duration_minutes');
            }
        });
    }
};
