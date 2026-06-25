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
        Schema::table('plannings', function (Blueprint $table) {
            if (!Schema::hasColumn('plannings', 'check_planning_link')) {
                $table->string('check_planning_link')->nullable()->after('pdf_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            if (Schema::hasColumn('plannings', 'check_planning_link')) {
                $table->dropColumn('check_planning_link');
            }
        });
    }
};
