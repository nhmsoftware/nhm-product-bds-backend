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
        Schema::create('lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('area_id');
            $table->foreign('area_id', 'fk_lots_area_id')
                  ->references('id')->on('areas')
                  ->onDelete('cascade');
            
            $table->string('code');
            $table->integer('status'); // integer-backed enum (1: available, 2: sold, 3: reserved, 4: unavailable)
            $table->decimal('area_size', 10, 2)->nullable();
            $table->string('direction')->nullable();
            $table->bigInteger('price')->nullable();
            $table->bigInteger('unit_price')->nullable();
            
            // Map/Layout coordinates
            $table->integer('coordinate_x')->nullable();
            $table->integer('coordinate_y')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['area_id', 'code'], 'uq_lots_area_code');
            
            $table->index('area_id', 'idx_lots_area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
