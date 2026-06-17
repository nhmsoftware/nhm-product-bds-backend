<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['price', 'unit_price']);
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->bigInteger('price')->nullable()->after('direction');
            $table->bigInteger('unit_price')->nullable()->after('price');
        });
    }
};
