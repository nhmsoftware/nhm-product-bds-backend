<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cập nhật chức danh ID = 1 thành Cộng tác viên
        DB::table('job_positions')
            ->where('id', 1)
            ->update([
                'name' => 'Cộng tác viên',
                'code' => 'COLLABORATOR',
                'updated_at' => now(),
            ]);

        // 2. Thêm chức danh ID = 9 là Chuyên viên kinh doanh nếu chưa có
        $exists = DB::table('job_positions')->where('id', 9)->exists();
        if (!$exists) {
            DB::table('job_positions')->insert([
                'id' => 9,
                'name' => 'Chuyên viên kinh doanh',
                'code' => 'BUSINESS_SPECIALIST',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // 1. Chuyển bất kỳ user nào có chức danh ID = 9 về ID = 1
        DB::table('users')
            ->where('job_position_id', 9)
            ->update(['job_position_id' => 1]);

        // 2. Xóa chức danh ID = 9
        DB::table('job_positions')->where('id', 9)->delete();

        // 3. Khôi phục chức danh ID = 1 về Nhân viên kinh doanh
        DB::table('job_positions')
            ->where('id', 1)
            ->update([
                'name' => 'Nhân viên kinh doanh',
                'code' => 'BUSINESS_STAFF',
                'updated_at' => now(),
            ]);
    }
};
