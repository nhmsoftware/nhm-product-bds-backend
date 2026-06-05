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
            $table->float('area_size')->nullable()->after('remaining_lots');
            $table->string('direction')->nullable()->after('area_size');
            $table->bigInteger('price')->nullable()->after('direction');
            $table->bigInteger('unit_price')->nullable()->after('price');
            $table->integer('status')->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['area_size', 'direction', 'price', 'unit_price', 'status']);
        });
    }
};
