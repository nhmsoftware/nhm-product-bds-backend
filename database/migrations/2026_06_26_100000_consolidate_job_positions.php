<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $targetPositions = [
            ['id' => 1, 'name' => 'Cộng tác viên', 'code' => 'COLLABORATOR'],
            ['id' => 2, 'name' => 'Chuyên viên kinh doanh', 'code' => 'BUSINESS_SPECIALIST'],
            ['id' => 3, 'name' => 'Trưởng phòng kinh doanh', 'code' => 'BUSINESS_MANAGER'],
            ['id' => 4, 'name' => 'Giám đốc kinh doanh', 'code' => 'BUSINESS_DIRECTOR'],
            ['id' => 5, 'name' => 'Tổng giám đốc', 'code' => 'CEO'],
        ];

        // Map old names/codes → new ID for data migration
        $remap = [
            // Old "Nhân viên kinh doanh" / BUSINESS_STAFF (id 1) → Chuyên viên kinh doanh (2)
            1 => 2,
            // Trưởng nhóm (2) → Trưởng phòng kinh doanh (3)
            2 => 3,
            // Trưởng phòng (3) stays at 3
            3 => 3,
            // GĐ kinh doanh (4) stays at 4
            4 => 4,
            // GĐ khu vực (5) → GĐ kinh doanh (4)
            5 => 4,
            // Tổng GĐ (6) → Tổng giám đốc (5)
            6 => 5,
            // Quản trị hệ thống (7) → Tổng giám đốc (5)
            7 => 5,
            // Khách hàng (8) → null
            8 => null,
            // Chuyên viên kinh doanh (9) stays at 2
            9 => 2,
            // Cộng tác viên (10) → 1
            10 => 1,
        ];

        // Migrate users referencing old positions
        foreach ($remap as $oldId => $newId) {
            if ($newId === null) {
                DB::table('users')->where('job_position_id', $oldId)->update(['job_position_id' => null]);
            } else {
                DB::table('users')->where('job_position_id', $oldId)->update(['job_position_id' => $newId]);
            }
        }

        // Replace job_positions table contents
        DB::table('job_positions')->truncate();
        foreach ($targetPositions as $pos) {
            DB::table('job_positions')->insert([
                ...$pos,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Restore original 8 positions from the initial migration
        $now = now();
        DB::table('job_positions')->truncate();
        DB::table('job_positions')->insert([
            ['id' => 1, 'name' => 'Nhân viên kinh doanh', 'code' => 'BUSINESS_STAFF', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Trưởng nhóm kinh doanh', 'code' => 'BUSINESS_LEADER', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Trưởng phòng kinh doanh', 'code' => 'BUSINESS_MANAGER', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Giám đốc kinh doanh', 'code' => 'BUSINESS_DIRECTOR', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Giám đốc khu vực', 'code' => 'AREA_DIRECTOR', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Tổng giám đốc', 'code' => 'CEO', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => 'Quản trị hệ thống', 'code' => 'SUPER_ADMIN', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => 'Khách hàng', 'code' => 'CUSTOMER', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
};
