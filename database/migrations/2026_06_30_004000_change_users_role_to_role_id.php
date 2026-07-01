<?php

use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: đổi cột `role` (tinyInt) sang `role_id` (UUID FK → roles).
 *
 * Các bước:
 * 1. Thêm cột role_id (UUID, nullable)
 * 2. Map dữ liệu cũ: role tinyInt → role_id từ bảng roles đã seed
 * 3. Xóa cột role cũ
 */
return new class extends Migration
{
    /**
     * Mapping giá trị tinyInt cũ → role name mới.
     */
    private const ROLE_MAP = [
        1 => 'employee',     // EMPLOYEE → Chuyên viên kinh doanh
        2 => 'tp_kd',        // MANAGER → Trưởng phòng kinh doanh
        3 => 'gdkd',         // DIRECTOR → Giám đốc kinh doanh
        4 => 'ceo',          // CEO → Tổng giám đốc
        5 => 'super_admin',  // SUPER_ADMIN → Super Admin
        6 => 'buyer',        // BUYER → Khách hàng
    ];

    public function up(): void
    {
        // 1. Thêm cột role_id
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('role_id')->nullable()->after('role');
        });

        // 2. Map dữ liệu cũ sang role_id
        $roleNameToId = DB::table('roles')->pluck('id', 'name')->toArray();

        foreach (self::ROLE_MAP as $oldValue => $newRoleName) {
            if (isset($roleNameToId[$newRoleName])) {
                DB::table('users')
                    ->where('role', $oldValue)
                    ->update(['role_id' => $roleNameToId[$newRoleName]]);
            }
        }

        // 3. Xóa cột role cũ
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // 4. Thêm foreign key
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 1. Thêm lại cột role (tinyInt)
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('role')->default(4)->after('role_id');
        });

        // 2. Map ngược role_id → role tinyInt
        $roleIdToName = DB::table('roles')->pluck('name', 'id')->toArray();
        $roleFlipMap = array_flip(self::ROLE_MAP);

        foreach ($roleIdToName as $roleId => $roleName) {
            if (isset($roleFlipMap[$roleName])) {
                DB::table('users')
                    ->where('role_id', $roleId)
                    ->update(['role' => $roleFlipMap[$roleName]]);
            }
        }

        // 3. Drop FK + cột role_id
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
