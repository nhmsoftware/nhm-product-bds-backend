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
        Schema::create('lot_lock_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lot_id');
            $table->uuid('user_id');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lot_id', 'fk_lot_lock_requests_lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'fk_lot_lock_requests_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->index('lot_id', 'idx_lot_lock_requests_lot_id');
            $table->index('user_id', 'idx_lot_lock_requests_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_lock_requests');
    }
};
