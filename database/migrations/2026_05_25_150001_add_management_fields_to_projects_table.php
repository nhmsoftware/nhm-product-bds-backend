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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('branch')->nullable()->comment('Tên chi nhánh quản lý');
            $table->integer('total_lots')->default(0)->comment('Tổng số lô');
            $table->integer('remaining_lots')->default(0)->comment('Số lô còn lại');
            $table->boolean('is_featured')->default(false)->comment('Trạng thái nổi bật');
            $table->boolean('is_locked')->default(false)->comment('Trạng thái khóa (không cho phép giao dịch)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['branch', 'total_lots', 'remaining_lots', 'is_featured', 'is_locked']);
        });
    }
};
