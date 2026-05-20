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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Khóa ngoại liên kết với bảng users (Nhân viên xin nghỉ)
            $table->uuid('user_id');
            $table->foreign('user_id', 'fk_leave_requests_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            
            // Loại nghỉ phép (annual, unpaid, personal, maternity, business, compensatory)
            $table->string('leave_type'); 
            
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            
            // Trạng thái đơn xin nghỉ phép: pending, approved, rejected
            $table->string('status')->default('pending'); 
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes để tối ưu hóa truy vấn tìm kiếm và kiểm tra chồng lấp thời gian
            $table->index('user_id', 'idx_leave_requests_user_id');
            $table->index(['user_id', 'start_date', 'end_date'], 'idx_leave_requests_user_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
