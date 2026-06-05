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
        Schema::table('areas', function (Blueprint $table) {
            $table->json('sales_board_images')->nullable()->after('planning_check_url');
        });

        Schema::table('lots', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn('images');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('sales_board_images');
        });
    }
};
