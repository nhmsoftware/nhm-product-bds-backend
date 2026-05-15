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
            $table->string('sub_area')->nullable()->after('district');
            $table->string('symbol')->nullable()->after('sub_area');
            $table->string('density')->nullable()->after('symbol');
            $table->string('max_height')->nullable()->after('density');
            $table->string('land_use_ratio')->nullable()->after('max_height');
            $table->string('setback')->nullable()->after('land_use_ratio');
            $table->text('land_type_notes')->nullable()->after('setback');
            $table->string('pdf_url')->nullable()->after('land_type_notes');
            $table->decimal('latitude', 10, 7)->nullable()->after('pdf_url');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropColumn([
                'sub_area', 'symbol', 'density', 'max_height', 
                'land_use_ratio', 'setback', 'land_type_notes', 
                'pdf_url', 'latitude', 'longitude'
            ]);
        });
    }
};
