<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed default roles, permissions, and role-permission mappings.
     */
    public function run(): void
    {
        // ─── 1. Seed Permissions ─────────────────────────────────
        $permissions = [
            // Hệ thống
            ['name' => 'manage_all', 'label' => 'Quản lý toàn hệ thống', 'module' => 'system'],

            // Chi nhánh
            ['name' => 'view_branch', 'label' => 'Xem chi nhánh', 'module' => 'branch'],
            ['name' => 'create_branch', 'label' => 'Tạo chi nhánh', 'module' => 'branch'],
            ['name' => 'edit_branch', 'label' => 'Sửa chi nhánh', 'module' => 'branch'],
            ['name' => 'delete_branch', 'label' => 'Xóa chi nhánh', 'module' => 'branch'],

            // Phòng ban
            ['name' => 'view_department', 'label' => 'Xem phòng ban', 'module' => 'department'],
            ['name' => 'create_department', 'label' => 'Tạo phòng ban', 'module' => 'department'],
            ['name' => 'edit_department', 'label' => 'Sửa phòng ban', 'module' => 'department'],
            ['name' => 'delete_department', 'label' => 'Xóa phòng ban', 'module' => 'department'],
            ['name' => 'approve_transfer', 'label' => 'Duyệt chuyển phòng', 'module' => 'department'],

            // Nhân sự
            ['name' => 'view_employee', 'label' => 'Xem nhân sự', 'module' => 'employee'],
            ['name' => 'create_employee', 'label' => 'Tạo nhân sự', 'module' => 'employee'],
            ['name' => 'edit_employee', 'label' => 'Sửa nhân sự', 'module' => 'employee'],
            ['name' => 'delete_employee', 'label' => 'Xóa nhân sự', 'module' => 'employee'],

            // Tuyển dụng (Onboarding)
            ['name' => 'view_recruitment', 'label' => 'Xem tuyển dụng', 'module' => 'recruitment'],
            ['name' => 'create_recruitment', 'label' => 'Tạo tin tuyển dụng', 'module' => 'recruitment'],
            ['name' => 'edit_recruitment', 'label' => 'Sửa tin tuyển dụng', 'module' => 'recruitment'],
            ['name' => 'delete_recruitment', 'label' => 'Xóa tin tuyển dụng', 'module' => 'recruitment'],
            ['name' => 'approve_onboard', 'label' => 'Duyệt onboarding', 'module' => 'recruitment'],

            // Nghỉ phép
            ['name' => 'view_leave', 'label' => 'Xem nghỉ phép', 'module' => 'leave'],
            ['name' => 'create_leave', 'label' => 'Tạo đơn nghỉ phép', 'module' => 'leave'],
            ['name' => 'edit_leave', 'label' => 'Sửa đơn nghỉ phép', 'module' => 'leave'],
            ['name' => 'delete_leave', 'label' => 'Xóa đơn nghỉ phép', 'module' => 'leave'],
            ['name' => 'approve_leave', 'label' => 'Duyệt nghỉ phép', 'module' => 'leave'],

            // Hợp đồng lao động
            ['name' => 'view_contract', 'label' => 'Xem hợp đồng', 'module' => 'contract'],
            ['name' => 'create_contract', 'label' => 'Tạo hợp đồng', 'module' => 'contract'],
            ['name' => 'edit_contract', 'label' => 'Sửa hợp đồng', 'module' => 'contract'],
            ['name' => 'delete_contract', 'label' => 'Xóa hợp đồng', 'module' => 'contract'],

            // Kho hàng / Phân khu / Lô đất
            ['name' => 'view_warehouse', 'label' => 'Xem kho hàng', 'module' => 'warehouse'],
            ['name' => 'edit_warehouse', 'label' => 'Cập nhật kho hàng/Khóa cọc', 'module' => 'warehouse'],

            // Lịch sử hoạt động
            ['name' => 'view_activity', 'label' => 'Xem lịch sử hoạt động', 'module' => 'activity'],
            ['name' => 'delete_activity', 'label' => 'Xóa lịch sử hoạt động', 'module' => 'activity'],

            // Chấm công
            ['name' => 'view_attendance', 'label' => 'Xem bảng chấm công', 'module' => 'attendance'],
            ['name' => 'edit_attendance', 'label' => 'Cập nhật/Duyệt công', 'module' => 'attendance'],
            ['name' => 'checkin_checkout', 'label' => 'Chấm công check-in/out', 'module' => 'attendance'],

            // Bảng xếp hạng
            ['name' => 'view_ranking', 'label' => 'Xem bảng xếp hạng', 'module' => 'ranking'],

            // Dashboard
            ['name' => 'view_dashboard', 'label' => 'Xem dashboard quản lý', 'module' => 'dashboard'],

            // Tin tức
            ['name' => 'view_news', 'label' => 'Xem tin tức', 'module' => 'news'],
            ['name' => 'create_news', 'label' => 'Tạo tin tức', 'module' => 'news'],
            ['name' => 'edit_news', 'label' => 'Sửa tin tức', 'module' => 'news'],
            ['name' => 'delete_news', 'label' => 'Xóa tin tức', 'module' => 'news'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'id' => (string) Str::uuid(),
                    'label' => $perm['label'],
                    'module' => $perm['module'],
                    'is_active' => true,
                ]
            );
        }

        // ─── 2. Seed Roles ───────────────────────────────────────
        $roles = [
            ['name' => 'super_admin', 'label' => 'Super Admin', 'level' => 0, 'sort' => 0, 'is_system' => true],
            ['name' => 'admin', 'label' => 'Admin', 'level' => 1, 'sort' => 1, 'is_system' => false],
            ['name' => 'ceo', 'label' => 'Tổng giám đốc', 'level' => 2, 'sort' => 2, 'is_system' => false],
            ['name' => 'gdcn', 'label' => 'Giám đốc chi nhánh', 'level' => 3, 'sort' => 3, 'is_system' => false],
            ['name' => 'gdkd', 'label' => 'Giám đốc kinh doanh', 'level' => 4, 'sort' => 4, 'is_system' => false],
            ['name' => 'tp_kd', 'label' => 'Trưởng phòng kinh doanh', 'level' => 5, 'sort' => 5, 'is_system' => false],
            ['name' => 'hr_manager', 'label' => 'HR Manager', 'level' => 6, 'sort' => 6, 'is_system' => false],
            ['name' => 'employee', 'label' => 'Chuyên viên kinh doanh', 'level' => 7, 'sort' => 7, 'is_system' => false],
            ['name' => 'ctv', 'label' => 'Cộng tác viên', 'level' => 8, 'sort' => 8, 'is_system' => false],
            ['name' => 'buyer', 'label' => 'Khách hàng', 'level' => 99, 'sort' => 99, 'is_system' => false],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                [
                    'id' => (string) Str::uuid(),
                    'label' => $role['label'],
                    'description' => null,
                    'level' => $role['level'],
                    'is_system' => $role['is_system'],
                    'sort' => $role['sort'],
                    'is_active' => true,
                ]
            );
        }

        // ─── 3. Seed Role-Permission mapping ────────────────────
        $rolePermissions = [
            'super_admin' => ['manage_all'],
            'admin' => ['manage_all'],
            'ceo' => ['manage_all'],
            'gdcn' => [
                'view_branch', 'create_branch', 'edit_branch',
                'view_recruitment', 'approve_onboard'
            ],
            'gdkd' => [
                'view_employee', 'create_employee', 'edit_employee', 'delete_employee',
                'view_recruitment', 'approve_onboard',
                'view_leave', 'approve_leave',
                'approve_transfer'
            ],
            'tp_kd' => [
                'view_activity',
                'approve_onboard'
            ],
            'hr_manager' => [
                'view_contract', 'create_contract', 'edit_contract', 'delete_contract'
            ],
            'employee' => [
                'view_warehouse', 'checkin_checkout', 'view_ranking'
            ],
            'ctv' => [
                'view_warehouse'
            ],
            // buyer: no permissions
        ];

        foreach ($rolePermissions as $roleName => $permNames) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;

            $permIds = Permission::whereIn('name', $permNames)->pluck('id')->toArray();
            $role->permissions()->sync($permIds);
        }
    }
}
