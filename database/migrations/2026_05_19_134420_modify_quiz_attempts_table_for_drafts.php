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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('is_correct');
            $table->boolean('is_correct')->nullable()->change();
            $table->integer('selected_option')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('is_draft');
            $table->boolean('is_correct')->nullable(false)->change();
            $table->integer('selected_option')->nullable(false)->change();
        });
    }
};
