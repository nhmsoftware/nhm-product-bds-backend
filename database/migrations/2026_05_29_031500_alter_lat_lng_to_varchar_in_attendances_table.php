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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_lat')->nullable()->change();
            $table->string('check_in_lng')->nullable()->change();
            $table->string('check_out_lat')->nullable()->change();
            $table->string('check_out_lng')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('check_in_lat', 10, 7)->nullable()->change();
            $table->decimal('check_in_lng', 10, 7)->nullable()->change();
            $table->decimal('check_out_lat', 10, 7)->nullable()->change();
            $table->decimal('check_out_lng', 10, 7)->nullable()->change();
        });
    }
};
