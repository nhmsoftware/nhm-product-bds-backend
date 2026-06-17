<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng job_positions
        Schema::create('job_positions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->timestamps();
        });

        // Seed các chức danh mặc định
        DB::table('job_positions')->insert([
            ['id' => 1, 'name' => 'Nhân viên kinh doanh', 'code' => 'BUSINESS_STAFF', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Trưởng nhóm kinh doanh', 'code' => 'BUSINESS_LEADER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Trưởng phòng kinh doanh', 'code' => 'BUSINESS_MANAGER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Giám đốc kinh doanh', 'code' => 'BUSINESS_DIRECTOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Giám đốc khu vực', 'code' => 'AREA_DIRECTOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Tổng giám đốc', 'code' => 'CEO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Quản trị hệ thống', 'code' => 'SUPER_ADMIN', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Khách hàng', 'code' => 'CUSTOMER', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Thêm cột branch_id vào bảng departments
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignUuid('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
        });

        // 3. Thêm cột department_id và job_position_id vào users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->unsignedInteger('job_position_id')
                ->nullable()
                ->constrained('job_positions')
                ->nullOnDelete();
        });

        // 4. Di chuyển dữ liệu phòng ban từ chuỗi cũ sang foreign key
        if (Schema::hasColumn('users', 'department')) {
            $departments = DB::table('departments')->get(['id', 'name']);
            foreach ($departments as $dept) {
                DB::table('users')
                    ->where('department', $dept->name)
                    ->update(['department_id' => $dept->id]);
            }
        }

        // 5. Di chuyển dữ liệu chức danh từ chuỗi cũ sang foreign key
        if (Schema::hasColumn('users', 'job_position')) {
            $positionMapping = [
                'Nhân viên kinh doanh' => 1,
                'Trưởng nhóm kinh doanh' => 2,
                'Trưởng phòng kinh doanh' => 3,
                'Giám đốc kinh doanh' => 4,
                'Giám đốc khu vực' => 5,
                'Tổng giám đốc' => 6,
                'Quản trị hệ thống' => 7,
                'Khách hàng' => 8,
            ];
            foreach ($positionMapping as $name => $id) {
                DB::table('users')
                    ->where('job_position', $name)
                    ->update(['job_position_id' => $id]);
            }
        }

        // 6. Cập nhật chức danh và mã nhân viên cho Khách hàng (UserRole::BUYER = 6)
        DB::table('users')
            ->where('role', 6)
            ->update([
                'job_position_id' => 8, // CUSTOMER
                'staff_code' => null,   // Khách hàng không có mã nhân viên
            ]);

        // 7. Xóa các cột chuỗi cũ trong bảng users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['department', 'job_position']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('job_position')->nullable();
        });

        // Khôi phục lại dữ liệu chuỗi từ các quan hệ
        $departments = DB::table('departments')->get(['id', 'name']);
        foreach ($departments as $dept) {
            DB::table('users')
                ->where('department_id', $dept->id)
                ->update(['department' => $dept->name]);
        }

        $positions = DB::table('job_positions')->get(['id', 'name']);
        foreach ($positions as $pos) {
            DB::table('users')
                ->where('job_position_id', $pos->id)
                ->update(['job_position' => $pos->name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->dropForeign(['job_position_id']);
            $table->dropColumn('job_position_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::dropIfExists('job_positions');
    }
};
