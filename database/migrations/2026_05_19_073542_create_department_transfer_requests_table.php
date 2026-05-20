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
        Schema::create('department_transfer_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('current_department')->comment('Mocked: Phòng ban hiện tại');
            $table->string('target_department')->comment('Phòng ban muốn chuyển đến');
            $table->text('reason')->comment('Lý do chuyển phòng ban');
            $table->date('desired_transfer_date')->comment('Ngày mong muốn chuyển');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Trạng thái yêu cầu');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_transfer_requests');
    }
};
