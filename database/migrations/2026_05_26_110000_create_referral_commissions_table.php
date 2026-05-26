<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // --- Foreign Keys ---
            $table->uuid('referrer_id');
            $table->foreign('referrer_id', 'fk_ref_com_referrer_id')
                  ->references('id')->on('users')->onDelete('cascade');

            $table->uuid('referral_history_id');
            $table->foreign('referral_history_id', 'fk_ref_com_history_id')
                  ->references('id')->on('referral_histories')->onDelete('cascade');

            // --- Các cột nghiệp vụ ---
            $table->bigInteger('amount')->default(0); // Số tiền hoa hồng
            $table->smallInteger('status')->default(1); // Trạng thái (1: Chờ thanh toán, 2: Đã thanh toán)

            // --- Indexes ---
            $table->index('status', 'idx_referral_commissions_status');
            $table->index('amount', 'idx_referral_commissions_amount');

            // --- Timestamps (BẮT BUỘC) ---
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
