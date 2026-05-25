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
        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sales_board_image')->nullable();
            $table->integer('total_lots')->default(0);
            $table->integer('remaining_lots')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('area_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('area_id');
            $table->foreign('area_id', 'fk_area_assignments_area_id')
                  ->references('id')->on('areas')
                  ->onDelete('cascade');

            $table->uuid('user_id');
            $table->foreign('user_id', 'fk_area_assignments_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'area_id'], 'uq_area_assignments_user_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_assignments');
        Schema::dropIfExists('areas');
    }
};
