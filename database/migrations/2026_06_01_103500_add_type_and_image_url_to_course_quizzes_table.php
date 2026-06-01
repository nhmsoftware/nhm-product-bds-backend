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
        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->string('type', 50)->default('multiple_choice')->after('lesson_id');
            $table->integer('order')->default(0)->after('type');
            $table->string('title', 255)->nullable()->after('order');
            $table->string('image_url')->nullable()->after('question');
            $table->string('placeholder', 255)->nullable()->after('options');
            // Change correct_option to nullable
            $table->integer('correct_option')->nullable()->change();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->text('essay_answer')->nullable()->after('selected_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('essay_answer');
        });

        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->dropColumn(['type', 'order', 'title', 'image_url', 'placeholder']);
            // NOTE: changing back to nullable(false) might fail if there are existing null values,
            // so we handle with care. We assume safe down migrations.
            $table->integer('correct_option')->nullable(false)->change();
        });
    }
};
