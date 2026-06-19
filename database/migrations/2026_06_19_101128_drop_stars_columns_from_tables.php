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
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn('kpi_stars');
        });

        Schema::table('reward_point_histories', function (Blueprint $table) {
            $table->dropColumn('stars_changed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->integer('kpi_stars')->default(0)->after('reward_points');
        });

        Schema::table('reward_point_histories', function (Blueprint $table) {
            $table->integer('stars_changed')->default(0);
        });
    }
};
