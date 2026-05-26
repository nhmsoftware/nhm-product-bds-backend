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
        Schema::create('lot_deposit_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lot_id')->index();
            $table->uuid('user_id')->index();
            $table->integer('status')->default(1)->comment('1: PENDING, 2: APPROVED, 3: REJECTED');
            $table->text('reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_deposit_requests');
    }
};
