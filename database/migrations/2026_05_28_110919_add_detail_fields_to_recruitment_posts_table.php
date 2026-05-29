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
        if (!Schema::hasTable('recruitment_posts')) {
            return;
        }

        Schema::table('recruitment_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_posts', 'job_description')) {
                $table->text('job_description')->nullable();
            }
            if (!Schema::hasColumn('recruitment_posts', 'candidate_requirements')) {
                $table->text('candidate_requirements')->nullable();
            }
            if (!Schema::hasColumn('recruitment_posts', 'benefits')) {
                $table->text('benefits')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('recruitment_posts')) {
            return;
        }

        Schema::table('recruitment_posts', function (Blueprint $table) {
            $columns = array_filter(
                ['job_description', 'candidate_requirements', 'benefits'],
                fn (string $column): bool => Schema::hasColumn('recruitment_posts', $column)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
