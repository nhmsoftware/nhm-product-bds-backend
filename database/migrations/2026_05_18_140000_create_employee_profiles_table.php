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
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('employee_title')->nullable(); // Danh hiệu nhân viên (vd: Nhân viên xuất sắc)
            $table->string('identity_card')->nullable(); // Số CCCD
            $table->date('dob')->nullable(); // Ngày sinh
            $table->string('bank_account_name')->nullable(); // Chủ tài khoản
            $table->string('bank_account_number')->nullable(); // Số tài khoản
            $table->string('bank_name')->nullable(); // Ngân hàng
            $table->text('education')->nullable(); // Học vấn
            $table->string('major')->nullable(); // Chuyên ngành
            $table->text('experience')->nullable(); // Kinh nghiệm làm việc
            $table->jsonb('attachments')->nullable(); // Danh sách tài liệu đính kèm [{type, name, url}]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
