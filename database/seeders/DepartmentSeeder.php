<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    // Phòng ban tạo riêng cho từng chi nhánh
    private const BRANCH_DEPARTMENTS = [
        ['name' => 'Kinh doanh',          'code' => 'SALES', 'kpi_quota' => 50],
        ['name' => 'Chăm sóc khách hàng', 'code' => 'CS',    'kpi_quota' => 15],
    ];

    // Phòng ban chỉ tạo 1 lần, gắn với chi nhánh đầu não (HN)
    private const HQ_DEPARTMENTS = [
        ['name' => 'Marketing', 'code' => 'MKT',        'kpi_quota' => 20],
        ['name' => 'Đào tạo',   'code' => 'TRAINING',   'kpi_quota' => 10],
        ['name' => 'Pháp chế',  'code' => 'LEGAL',      'kpi_quota' => 5],
        ['name' => 'Nhân sự',   'code' => 'HR',         'kpi_quota' => 5],
        ['name' => 'Kế toán',   'code' => 'ACCOUNTING', 'kpi_quota' => 0],
        ['name' => 'Công nghệ', 'code' => 'IT',         'kpi_quota' => 10],
    ];

    public function run(): void
    {
        // Xóa vĩnh viễn các phòng ban cũ không có branch_id (legacy từ seeder cũ)
        Department::withTrashed()->whereNull('branch_id')->whereIn('code', ['SALES', 'CS'])->forceDelete();

        $branchIds = $this->ensureMainBranches();

        $this->seedBranchDepartments($branchIds);
        $this->seedHqDepartments($branchIds['HN']);
    }

    // Đảm bảo 3 chi nhánh chính tồn tại (InventoryAreaSeeder sẽ update thêm chi tiết)
    private function ensureMainBranches(): array
    {
        $branches = [
            ['code' => 'HN',  'name' => 'Hà Nội',       'area' => 'Hà Nội',       'sort' => 1],
            ['code' => 'HCM', 'name' => 'Hồ Chí Minh',  'area' => 'Hồ Chí Minh',  'sort' => 2],
            ['code' => 'DN',  'name' => 'Đà Nẵng',      'area' => 'Đà Nẵng',      'sort' => 3],
        ];

        $ids = [];
        foreach ($branches as $br) {
            $existing = DB::table('branches')->where('code', $br['code'])->first();
            if ($existing) {
                $ids[$br['code']] = $existing->id;
                continue;
            }
            $id = (string) Str::uuid();
            DB::table('branches')->insert([
                'id'         => $id,
                'code'       => $br['code'],
                'name'       => $br['name'],
                'area'       => $br['area'],
                'is_active'  => true,
                'sort'       => $br['sort'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ids[$br['code']] = $id;
        }
        return $ids;
    }

    private function seedBranchDepartments(array $branchIds): void
    {
        foreach ($branchIds as $branchCode => $branchId) {
            foreach (self::BRANCH_DEPARTMENTS as $dept) {
                $code = $dept['code'] . '_' . $branchCode;

                $manager = User::where('role', UserRole::MANAGER->value)
                    ->where('branch_id', $branchId)
                    ->whereHas('departmentRel', fn ($q) => $q->where('name', $dept['name']))
                    ->first();

                $existing = Department::where('code', $code)->first();
                if ($existing) {
                    $existing->update([
                        'name'       => $dept['name'],
                        'branch_id'  => $branchId,
                        'manager_id' => $manager?->id,
                        'kpi_quota'  => $dept['kpi_quota'],
                        'is_active'  => true,
                    ]);
                } else {
                    Department::create([
                        'id'         => (string) Str::uuid(),
                        'code'       => $code,
                        'name'       => $dept['name'],
                        'branch_id'  => $branchId,
                        'manager_id' => $manager?->id,
                        'kpi_quota'  => $dept['kpi_quota'],
                        'is_active'  => true,
                    ]);
                }
            }
        }
    }

    private function seedHqDepartments(string $hnBranchId): void
    {
        foreach (self::HQ_DEPARTMENTS as $dept) {
            $manager = User::where('role', UserRole::MANAGER->value)
                ->where('branch_id', $hnBranchId)
                ->whereHas('departmentRel', fn ($q) => $q->where('name', $dept['name']))
                ->first();

            $existing = Department::where('code', $dept['code'])->first();
            if ($existing) {
                $existing->update([
                    'name'       => $dept['name'],
                    'branch_id'  => $hnBranchId,
                    'manager_id' => $manager?->id,
                    'kpi_quota'  => $dept['kpi_quota'],
                    'is_active'  => true,
                ]);
            } else {
                Department::create([
                    'id'         => (string) Str::uuid(),
                    'code'       => $dept['code'],
                    'name'       => $dept['name'],
                    'branch_id'  => $hnBranchId,
                    'manager_id' => $manager?->id,
                    'kpi_quota'  => $dept['kpi_quota'],
                    'is_active'  => true,
                ]);
            }
        }
    }
}
