<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reward_point_histories', 'stars_changed')) {
            Schema::table('reward_point_histories', function (Blueprint $table) {
                $table->dropColumn('stars_changed');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reward_point_histories', function (Blueprint $table) {
            $table->integer('stars_changed')->default(0)->after('points_changed');
        });
    }
};
