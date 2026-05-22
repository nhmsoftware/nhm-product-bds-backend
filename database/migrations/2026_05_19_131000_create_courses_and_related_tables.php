<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table: courses
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('department', 100)->nullable();
            $table->string('job_position', 100)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active', 'idx_courses_is_active');
            $table->index('is_required', 'idx_courses_is_required');
            $table->index('department', 'idx_courses_department');
            $table->index('job_position', 'idx_courses_job_position');
            $table->index('order', 'idx_courses_order');
        });

        // 2. Table: course_lessons
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('course_id', 'fk_course_lessons_course_id')
                  ->references('id')->on('courses')->onDelete('cascade');
            
            $table->index('course_id', 'idx_course_lessons_course_id');
            $table->index('order', 'idx_course_lessons_order');
        });

        // 3. Table: course_quizzes
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id');
            $table->text('question');
            $table->jsonb('options');
            $table->integer('correct_option');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lesson_id', 'fk_course_quizzes_lesson_id')
                  ->references('id')->on('course_lessons')->onDelete('cascade');

            $table->index('lesson_id', 'idx_course_quizzes_lesson_id');
        });

        // 4. Table: course_enrollments
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('course_id');
            $table->tinyInteger('status')->default(1);
            $table->decimal('progress_percent', 5, 2)->default(0.00);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'fk_course_enrollments_user_id')
                  ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id', 'fk_course_enrollments_course_id')
                  ->references('id')->on('courses')->onDelete('cascade');

            $table->unique(['user_id', 'course_id'], 'uq_course_enrollments_user_course');
            $table->index('user_id', 'idx_course_enrollments_user_id');
            $table->index('course_id', 'idx_course_enrollments_course_id');
            $table->index('status', 'idx_course_enrollments_status');
        });

        // 5. Table: lesson_progress
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('enrollment_id');
            $table->uuid('lesson_id');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id', 'fk_lesson_progress_enrollment_id')
                  ->references('id')->on('course_enrollments')->onDelete('cascade');
            $table->foreign('lesson_id', 'fk_lesson_progress_lesson_id')
                  ->references('id')->on('course_lessons')->onDelete('cascade');

            $table->unique(['enrollment_id', 'lesson_id'], 'uq_lesson_progress_enrollment_lesson');
            $table->index('enrollment_id', 'idx_lesson_progress_enrollment_id');
            $table->index('lesson_id', 'idx_lesson_progress_lesson_id');
        });

        // 6. Table: quiz_attempts
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('quiz_id');
            $table->integer('selected_option');
            $table->boolean('is_correct');
            $table->timestamps();

            $table->foreign('user_id', 'fk_quiz_attempts_user_id')
                  ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('quiz_id', 'fk_quiz_attempts_quiz_id')
                  ->references('id')->on('course_quizzes')->onDelete('cascade');

            $table->index('user_id', 'idx_quiz_attempts_user_id');
            $table->index('quiz_id', 'idx_quiz_attempts_quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_quizzes');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('courses');
    }
};
