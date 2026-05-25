<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Leave\Models\Enums\RequestStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm cột tạm thời
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->integer('status_int')->default(RequestStatus::PENDING->value)->after('status');
        });

        // 2. Data migration
        DB::table('leave_requests')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $statusVal = strtolower((string) $row->status);
                $intValue = match ($statusVal) {
                    'pending', '1'   => RequestStatus::PENDING->value,
                    'approved', '2'  => RequestStatus::APPROVED->value,
                    'rejected', '3'  => RequestStatus::REJECTED->value,
                    'cancelled', '4' => RequestStatus::CANCELLED->value,
                    default          => RequestStatus::PENDING->value,
                };

                DB::table('leave_requests')
                    ->where('id', $row->id)
                    ->update(['status_int' => $intValue]);
            }
        });

        // 3. Xóa cột cũ và đổi tên cột mới
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->renameColumn('status_int', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('status_str')->default('pending')->after('status');
        });

        DB::table('leave_requests')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $strValue = match ((int) $row->status) {
                    RequestStatus::PENDING->value   => 'pending',
                    RequestStatus::APPROVED->value  => 'approved',
                    RequestStatus::REJECTED->value  => 'rejected',
                    RequestStatus::CANCELLED->value => 'cancelled',
                    default                         => 'pending',
                };

                DB::table('leave_requests')
                    ->where('id', $row->id)
                    ->update(['status_str' => $strValue]);
            }
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->renameColumn('status_str', 'status');
        });
    }
};
