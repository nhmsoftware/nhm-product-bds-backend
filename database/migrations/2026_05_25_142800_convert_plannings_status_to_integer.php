<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Planning\Models\Enums\PlanningStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Chuyển đổi cột 'status' của bảng plannings từ string sang integer
     * để sử dụng PHP Enum (int-backed) theo chuẩn Project Constitution luật #16.
     * 
     * Map:
     *   'draft'                  -> PlanningStatus::DRAFT   (1)
     *   'public' hoặc 'published' -> PlanningStatus::PUBLIC  (2)
     *   Các giá trị khác         -> PlanningStatus::ARCHIVED (3)
     */
    public function up(): void
    {
        // Bước 1: Thêm cột tạm thời để giữ giá trị integer mới
        Schema::table('plannings', function (Blueprint $table) {
            $table->integer('status_int')->default(PlanningStatus::DRAFT->value)->after('status');
        });

        // Bước 2: Chuyển đổi dữ liệu cũ (string) sang integer
        DB::table('plannings')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $intValue = match (strtolower((string) $row->status)) {
                    'public', 'published' => PlanningStatus::PUBLIC->value,
                    'draft'               => PlanningStatus::DRAFT->value,
                    default               => PlanningStatus::ARCHIVED->value,
                };

                DB::table('plannings')
                    ->where('id', $row->id)
                    ->update(['status_int' => $intValue]);
            }
        });

        // Bước 3: Xóa cột string cũ và đổi tên cột mới
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('plannings', function (Blueprint $table) {
            $table->renameColumn('status_int', 'status');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Chuyển ngược về string (nếu cần rollback).
     */
    public function down(): void
    {
        // Bước 1: Thêm cột tạm thời string
        Schema::table('plannings', function (Blueprint $table) {
            $table->string('status_str')->default('draft')->after('status');
        });

        // Bước 2: Chuyển dữ liệu ngược lại
        DB::table('plannings')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $strValue = match ((int) $row->status) {
                    PlanningStatus::PUBLIC->value   => 'public',
                    PlanningStatus::ARCHIVED->value => 'archived',
                    default                         => 'draft',
                };

                DB::table('plannings')
                    ->where('id', $row->id)
                    ->update(['status_str' => $strValue]);
            }
        });

        // Bước 3: Xóa cột integer và đổi tên về string
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('plannings', function (Blueprint $table) {
            $table->renameColumn('status_str', 'status');
        });
    }
};
