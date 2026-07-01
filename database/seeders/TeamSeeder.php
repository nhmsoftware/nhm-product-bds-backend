<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\Team;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa sạch các đội nhóm cũ trước khi seed
        Team::query()->forceDelete();

        // Lấy tất cả phòng ban đang hoạt động và có chi nhánh
        $departments = Department::whereNotNull('branch_id')->where('is_active', true)->get();

        foreach ($departments as $dept) {
            if (str_contains(strtolower($dept->name), 'kinh doanh') || str_contains($dept->code, 'SALES')) {
                // Tạo 2 đội nhóm cho phòng Kinh doanh
                $teamNames = ['Đội Chiến Binh', 'Đội Tiên Phong'];
                foreach ($teamNames as $index => $name) {
                    $code = Str::slug($name) . '_' . strtolower($dept->code);
                    
                    // Lấy một nhân viên ngẫu nhiên thuộc phòng ban này làm leader
                    $leader = User::where('department_id', $dept->id)
                        ->where('is_active', true)
                        ->first();

                    $team = Team::updateOrCreate(
                        ['code' => $code],
                        [
                            'id' => (string) Str::uuid(),
                            'name' => $name . ' ' . $dept->branch?->name,
                            'department_id' => $dept->id,
                            'leader_id' => $leader?->id,
                            'is_active' => true,
                        ]
                    );

                    // Gán các nhân viên khác thuộc phòng ban này vào nhóm
                    if ($leader) {
                        User::where('department_id', $dept->id)
                            ->where('id', '!=', $leader->id)
                            ->update(['team_id' => $team->id]);
                    }
                }
            }
        }
    }
}
