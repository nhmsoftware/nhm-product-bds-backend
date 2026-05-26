<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_commission_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // 1: Recruitment, 2: Customer
            $table->integer('referral_type')->unique()->comment('1: QR Tuyển dụng, 2: QR Giới thiệu khách hàng');
            
            // Lưu trữ dưới dạng chuỗi hoặc bigint, ở đây theo cấu trúc database-rules, BigInt có thể dùng string trong Model
            $table->unsignedBigInteger('amount')->default(0)->comment('Số tiền hoa hồng');
            
            $table->timestamps();
        });

        // Insert default configuration
        DB::table('referral_commission_configs')->insert([
            [
                'id' => Str::uuid()->toString(),
                'referral_type' => 1, // Recruitment
                'amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'referral_type' => 2, // Customer
                'amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_commission_configs');
    }
};
