<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Area\Models\Enums\LotStatus;

class AreaAndLotSeeder extends Seeder
{
    /**
     * Tạo fake data cho: Users (các role), Areas, Lots, AreaAssignments
     * để test API GET /api/v1/areas
     */
    public function run(): void
    {
        $this->command->info('🚀 Bắt đầu tạo fake data cho Area module...');

        // ─── 1. Tạo Users test ───────────────────────────────────────
        $this->command->info('👤 Tạo users test...');
        $users = $this->createTestUsers();

        // ─── 2. Tạo Areas ─────────────────────────────────────────────
        $this->command->info('🏘️  Tạo areas...');
        $areas = $this->createAreas();

        // ─── 3. Tạo Lots cho từng Area ────────────────────────────────
        $this->command->info('🏠 Tạo lots...');
        $this->createLots($areas);

        // ─── 4. Gán Area cho Users (AreaAssignment) ───────────────────
        $this->command->info('🔗 Gán area cho users...');
        $this->createAreaAssignments($users, $areas);

        $this->command->newLine();
        $this->command->info('✅ Hoàn thành! Tóm tắt fake data:');
        $this->command->table(
            ['Loại', 'Số lượng'],
            [
                ['Users', count($users)],
                ['Areas', count($areas)],
                ['Lots', DB::table('lots')->count()],
                ['Area Assignments', DB::table('area_assignments')->whereNull('deleted_at')->count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('📋 Thông tin đăng nhập test:');
        $this->command->table(
            ['Email', 'Password', 'Role', 'Ghi chú'],
            [
                ['employee@test.com',  'password123', 'EMPLOYEE (1)',    'Được assign 2 area đầu'],
                ['manager@test.com',   'password123', 'MANAGER (2)',     'Được assign 3 area đầu'],
                ['director@test.com',  'password123', 'DIRECTOR (3)',    'Xem tất cả area (không cần assign)'],
                ['ceo@test.com',       'password123', 'CEO (4)',         'Xem tất cả area (không cần assign)'],
                ['superadmin@test.com','password123', 'SUPER_ADMIN (5)', 'Xem tất cả area (không cần assign)'],
                ['employee2@test.com', 'password123', 'EMPLOYEE (1)',    'CHƯA được assign area nào'],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Tạo các user test với đủ role
     */
    private function createTestUsers(): array
    {
        $usersData = [
            [
                'email'        => 'employee@test.com',
                'name'         => 'Nguyễn Văn Nhân Viên',
                'staff_code'   => 'TEST-EMP-001',
                'role'         => UserRole::EMPLOYEE->value,
                'department'   => 'HN',
            ],
            [
                'email'        => 'manager@test.com',
                'name'         => 'Trần Thị Trưởng Phòng',
                'staff_code'   => 'TEST-MGR-001',
                'role'         => UserRole::MANAGER->value,
                'department'   => 'HN',
            ],
            [
                'email'        => 'director@test.com',
                'name'         => 'Lê Văn Giám Đốc',
                'staff_code'   => 'TEST-DIR-001',
                'role'         => UserRole::DIRECTOR->value,
                'department'   => 'HN',
            ],
            [
                'email'        => 'ceo@test.com',
                'name'         => 'Phạm Thị Tổng Giám Đốc',
                'staff_code'   => 'TEST-CEO-001',
                'role'         => UserRole::CEO->value,
                'department'   => 'HN',
            ],
            [
                'email'        => 'superadmin@test.com',
                'name'         => 'Super Admin Test',
                'staff_code'   => 'TEST-SA-001',
                'role'         => UserRole::SUPER_ADMIN->value,
                'department'   => 'HN',
            ],
            [
                'email'        => 'employee2@test.com',
                'name'         => 'Võ Thị Nhân Viên Mới (Chưa Assign)',
                'staff_code'   => 'TEST-EMP-002',
                'role'         => UserRole::EMPLOYEE->value,
                'department'   => 'HCM',
            ],
        ];

        $createdUsers = [];
        foreach ($usersData as $data) {
            // Upsert: nếu email đã tồn tại thì cập nhật, không tạo trùng
            $existing = DB::table('users')->where('email', $data['email'])->first();
            if ($existing) {
                $this->command->warn("  ⚠️  User {$data['email']} đã tồn tại, bỏ qua.");
                $createdUsers[$data['email']] = $existing;
                continue;
            }

            $id = Str::uuid()->toString();
            DB::table('users')->insert([
                'id'           => $id,
                'staff_code'   => $data['staff_code'],
                'name'         => $data['name'],
                'email'        => $data['email'],
                'phone'        => '09' . rand(10000000, 99999999),
                'password'     => Hash::make('password123'),
                'role'         => $data['role'],
                'department'   => $data['department'],
                'job_position' => 'Nhân viên kinh doanh test',
                'area'         => 'Hà Nội',
                'is_active'    => true,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now(),
            ]);

            $createdUsers[$data['email']] = DB::table('users')->where('id', $id)->first();
            $this->command->line("  ✔ Tạo user: {$data['email']} [{$data['staff_code']}]");
        }

        return $createdUsers;
    }

    /**
     * Tạo 5 areas fake
     */
    private function createAreas(): array
    {
        $areasData = [
            [
                'name'              => 'Phân khu A - Vinhomes Ocean Park',
                'total_lots'        => 50,
                'remaining_lots'    => 32,
                'area_size'         => 2500.5,
                'direction'         => 'Đông Nam',
                'price'             => 5000000000,
                'unit_price'        => 45000000,
                'status'            => 1,
                'is_featured'       => true,
                'sales_board_image' => 'https://picsum.photos/seed/area1/800/600',
                'planning_check_url'=> 'https://quyhoach24h.vn?ref=TEST001',
            ],
            [
                'name'              => 'Phân khu B - Ecopark Grand',
                'total_lots'        => 80,
                'remaining_lots'    => 15,
                'area_size'         => 4200.0,
                'direction'         => 'Tây Bắc',
                'price'             => 3500000000,
                'unit_price'        => 38000000,
                'status'            => 1,
                'is_featured'       => true,
                'sales_board_image' => 'https://picsum.photos/seed/area2/800/600',
                'planning_check_url'=> 'https://quyhoach24h.vn?ref=TEST002',
            ],
            [
                'name'              => 'Khu đô thị C - Gamuda Gardens',
                'total_lots'        => 120,
                'remaining_lots'    => 60,
                'area_size'         => 6800.75,
                'direction'         => 'Nam',
                'price'             => 7200000000,
                'unit_price'        => 55000000,
                'status'            => 1,
                'is_featured'       => false,
                'sales_board_image' => 'https://picsum.photos/seed/area3/800/600',
                'planning_check_url'=> 'https://quyhoach24h.vn?ref=TEST003',
            ],
            [
                'name'              => 'Phân khu D - Times City Park',
                'total_lots'        => 30,
                'remaining_lots'    => 0,
                'area_size'         => 1500.0,
                'direction'         => 'Bắc',
                'price'             => 9500000000,
                'unit_price'        => 78000000,
                'status'            => 2,
                'is_featured'       => false,
                'sales_board_image' => 'https://picsum.photos/seed/area4/800/600',
                'planning_check_url'=> null,
            ],
            [
                'name'              => 'Khu E - Ciputra Hà Nội',
                'total_lots'        => 45,
                'remaining_lots'    => 28,
                'area_size'         => 3100.25,
                'direction'         => 'Đông',
                'price'             => 12000000000,
                'unit_price'        => 95000000,
                'status'            => 1,
                'is_featured'       => true,
                'sales_board_image' => 'https://picsum.photos/seed/area5/800/600',
                'planning_check_url'=> 'https://quyhoach24h.vn?ref=TEST005',
            ],
        ];

        $createdAreas = [];
        foreach ($areasData as $data) {
            // Kiểm tra area trùng tên
            $existing = DB::table('areas')->where('name', $data['name'])->whereNull('deleted_at')->first();
            if ($existing) {
                $this->command->warn("  ⚠️  Area '{$data['name']}' đã tồn tại, bỏ qua.");
                $createdAreas[] = $existing;
                continue;
            }

            $id = Str::uuid()->toString();
            DB::table('areas')->insert([
                'id'                => $id,
                'project_id'        => null,
                'name'              => $data['name'],
                'total_lots'        => $data['total_lots'],
                'remaining_lots'    => $data['remaining_lots'],
                'area_size'         => $data['area_size'],
                'direction'         => $data['direction'],
                'price'             => $data['price'],
                'unit_price'        => $data['unit_price'],
                'status'            => $data['status'],
                'is_featured'       => $data['is_featured'],
                'sales_board_image' => $data['sales_board_image'],
                'sales_board_iframe'=> null,
                'sales_board_images'=> null,
                'planning_check_url'=> $data['planning_check_url'],
                'created_at'        => Carbon::now()->subDays(rand(1, 90)),
                'updated_at'        => Carbon::now(),
            ]);

            $createdAreas[] = DB::table('areas')->where('id', $id)->first();
            $this->command->line("  ✔ Tạo area: {$data['name']}");
        }

        return $createdAreas;
    }

    /**
     * Tạo lots cho mỗi area
     */
    private function createLots(array $areas): void
    {
        $directions  = ['Đông', 'Tây', 'Nam', 'Bắc', 'Đông Nam', 'Tây Bắc', 'Đông Bắc', 'Tây Nam'];
        $statuses    = [
            LotStatus::AVAILABLE->value,
            LotStatus::AVAILABLE->value,
            LotStatus::AVAILABLE->value,
            LotStatus::SOLD->value,
            LotStatus::RESERVED->value,
        ];

        foreach ($areas as $area) {
            // Kiểm tra xem area này đã có lots chưa
            $existingLots = DB::table('lots')->where('area_id', $area->id)->whereNull('deleted_at')->count();
            if ($existingLots > 0) {
                $this->command->warn("  ⚠️  Area '{$area->name}' đã có {$existingLots} lots, bỏ qua.");
                continue;
            }

            $lotsToCreate = min((int) $area->total_lots, 10); // Tối đa 10 lots mỗi area
            $lotsData = [];

            for ($i = 1; $i <= $lotsToCreate; $i++) {
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $area->name), 0, 1));
                $lotsData[] = [
                    'id'           => Str::uuid()->toString(),
                    'area_id'      => $area->id,
                    'code'         => "{$prefix}-" . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status'       => $statuses[array_rand($statuses)],
                    'area_size'    => round(rand(80, 250) + rand(0, 9) / 10, 1),
                    'direction'    => $directions[array_rand($directions)],
                    'price'        => rand(2, 15) * 1000000000,
                    'unit_price'   => rand(30, 100) * 1000000,
                    'coordinate_x' => rand(50, 500),
                    'coordinate_y' => rand(50, 500),
                    'width'        => rand(40, 80),
                    'height'       => rand(40, 80),
                    'image_url'    => "https://picsum.photos/seed/lot{$i}/400/300",
                    'frontage'     => round(rand(4, 10) + rand(0, 9) / 10, 1),
                    'legal'        => rand(0, 1) ? 'Sổ hồng riêng' : 'Sổ đỏ',
                    'description'  => "Lô đất số {$i} thuộc {$area->name}. Vị trí đẹp, thoáng mát.",
                    'is_locked'    => false,
                    'created_at'   => Carbon::now()->subDays(rand(1, 60)),
                    'updated_at'   => Carbon::now(),
                ];
            }

            DB::table('lots')->insert($lotsData);
            $this->command->line("  ✔ Tạo {$lotsToCreate} lots cho area: {$area->name}");
        }
    }

    /**
     * Gán areas cho users:
     * - employee@test.com  → 2 areas đầu
     * - manager@test.com   → 3 areas đầu
     * - director/ceo/superadmin → không cần assign (code tự bypass)
     * - employee2@test.com → không assign (để test case rỗng)
     */
    private function createAreaAssignments(array $users, array $areas): void
    {
        $assignmentMap = [
            'employee@test.com' => array_slice($areas, 0, 2),   // 2 area đầu
            'manager@test.com'  => array_slice($areas, 0, 3),   // 3 area đầu
            // director/ceo/superadmin không cần assign
            // employee2@test.com không assign (test trường hợp rỗng)
        ];

        foreach ($assignmentMap as $email => $assignedAreas) {
            if (!isset($users[$email])) {
                $this->command->warn("  ⚠️  Không tìm thấy user: {$email}");
                continue;
            }

            $user = $users[$email];
            foreach ($assignedAreas as $area) {
                // Kiểm tra trùng assignment
                $exists = DB::table('area_assignments')
                    ->where('user_id', $user->id)
                    ->where('area_id', $area->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    $this->command->warn("  ⚠️  Assignment đã tồn tại: {$email} → {$area->name}");
                    continue;
                }

                DB::table('area_assignments')->insert([
                    'id'         => Str::uuid()->toString(),
                    'user_id'    => $user->id,
                    'area_id'    => $area->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $this->command->line("  ✔ Assign: {$email} → {$area->name}");
            }
        }
    }
}
