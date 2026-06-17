<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $depts = [
            ['name' => 'Kinh doanh', 'code' => 'SALES', 'kpi_quota' => 50],
            ['name' => 'Marketing', 'code' => 'MKT', 'kpi_quota' => 20],
            ['name' => 'Đào tạo', 'code' => 'TRAINING', 'kpi_quota' => 10],
            ['name' => 'Pháp chế', 'code' => 'LEGAL', 'kpi_quota' => 5],
            ['name' => 'Chăm sóc khách hàng', 'code' => 'CS', 'kpi_quota' => 15],
            ['name' => 'Nhân sự', 'code' => 'HR', 'kpi_quota' => 5],
            ['name' => 'Kế toán', 'code' => 'ACCOUNTING', 'kpi_quota' => 0],
            ['name' => 'Công nghệ', 'code' => 'IT', 'kpi_quota' => 10],
        ];

        foreach ($depts as $dept) {
            $manager = User::where('role', UserRole::MANAGER->value)
                ->whereHas('departmentRel', function ($q) use ($dept) {
                    $q->where('name', $dept['name']);
                })
                ->first();

            $existing = Department::where('code', $dept['code'])->first();
            if ($existing) {
                $existing->update([
                    'name' => $dept['name'],
                    'manager_id' => $manager?->id,
                    'kpi_quota' => $dept['kpi_quota'],
                    'is_active' => true,
                ]);
            } else {
                Department::create([
                    'id' => (string) Str::uuid(),
                    'code' => $dept['code'],
                    'name' => $dept['name'],
                    'manager_id' => $manager?->id,
                    'kpi_quota' => $dept['kpi_quota'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
