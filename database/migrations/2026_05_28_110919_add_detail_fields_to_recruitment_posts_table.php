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
        Schema::table('recruitment_posts', function (Blueprint $table) {
            $table->text('job_description')->nullable()->after('department');
            $table->text('candidate_requirements')->nullable()->after('job_description');
            $table->text('benefits')->nullable()->after('candidate_requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_posts', function (Blueprint $table) {
            $table->dropColumn(['job_description', 'candidate_requirements', 'benefits']);
        });
    }
};
