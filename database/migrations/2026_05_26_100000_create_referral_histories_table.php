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
        Schema::create('referral_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // --- Các cột nghiệp vụ ---
            $table->string('name'); // Họ tên người quét QR
            $table->string('phone'); // Số điện thoại
            $table->smallInteger('referral_type'); // Loại QR (1: Tuyển dụng, 2: Giới thiệu khách hàng)
            $table->smallInteger('status')->default(1); // Trạng thái (1: Chưa hoàn tất, 2: Đã đăng ký)
            $table->timestamp('scanned_at'); // Thời gian quét
            $table->timestamp('registered_at')->nullable(); // Thời gian hoàn tất đăng ký

            // --- Foreign Keys ---
            $table->uuid('referrer_id');
            $table->foreign('referrer_id', 'fk_referral_histories_referrer_id')
                  ->references('id')->on('users')->onDelete('cascade');

            $table->uuid('referee_id')->nullable();
            $table->foreign('referee_id', 'fk_referral_histories_referee_id')
                  ->references('id')->on('users')->onDelete('cascade');

            // --- Indexes ---
            $table->index('phone', 'idx_referral_histories_phone');
            $table->index('referral_type', 'idx_referral_histories_referral_type');
            $table->index('status', 'idx_referral_histories_status');

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
        Schema::dropIfExists('referral_histories');
    }
};
