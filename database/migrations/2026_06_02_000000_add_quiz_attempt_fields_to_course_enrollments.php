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
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->uuid('quiz_attempt_id')->nullable()->after('completed_at');
            $table->string('quiz_status')->nullable()->after('quiz_attempt_id');
            $table->timestamp('quiz_started_at')->nullable()->after('quiz_status');
            $table->timestamp('quiz_expires_at')->nullable()->after('quiz_started_at');
            $table->integer('quiz_remaining_seconds')->nullable()->after('quiz_expires_at');
            $table->timestamp('quiz_last_saved_at')->nullable()->after('quiz_remaining_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'quiz_attempt_id',
                'quiz_status',
                'quiz_started_at',
                'quiz_expires_at',
                'quiz_remaining_seconds',
                'quiz_last_saved_at'
            ]);
        });
    }
};
