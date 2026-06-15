<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
                ['customer@test.com',  'password123', 'BUYER (6)',       'Khách hàng demo mobile app'],
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
                'name'         => 'Võ Thị Nhân Viên Mới',
                'staff_code'   => 'TEST-EMP-002',
                'role'         => UserRole::EMPLOYEE->value,
                'department'   => 'HCM',
                'job_position' => 'Nhân viên kinh doanh',
                'area'         => 'Hồ Chí Minh',
            ],
            [
                'email'        => 'customer@test.com',
                'name'         => 'Khách Hàng Demo',
                'staff_code'   => 'TEST-CUS-001',
                'role'         => UserRole::BUYER->value,
                'department'   => null,
                'job_position' => 'Khách hàng',
                'area'         => null,
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
                'job_position' => $data['job_position'] ?? 'Nhân viên kinh doanh',
                'area'         => $data['area'] ?? 'Hà Nội',
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
            $payload = [
                'project_id'         => null,
                'name'               => $data['name'],
                'total_lots'         => $data['total_lots'],
                'remaining_lots'     => $data['remaining_lots'],
                'area_size'          => $data['area_size'],
                'direction'          => $data['direction'],
                'price'              => $data['price'],
                'unit_price'         => $data['unit_price'],
                'status'             => $data['status'],
                'is_featured'        => $data['is_featured'],
                'sales_board_image'  => $data['sales_board_image'],
                'sales_board_iframe' => null,
                'sales_board_images' => null,
                'planning_check_url' => $data['planning_check_url'],
                'updated_at'         => Carbon::now(),
            ];

            $existing = DB::table('areas')->where('name', $data['name'])->whereNull('deleted_at')->first();
            if ($existing) {
                DB::table('areas')->where('id', $existing->id)->update($payload);
                $createdAreas[] = DB::table('areas')->where('id', $existing->id)->first();
                $this->command->line("  ✔ Cập nhật area: {$data['name']}");
                continue;
            }

            $id = Str::uuid()->toString();
            DB::table('areas')->insert([
                ...$payload,
                'id'         => $id,
                'created_at' => Carbon::now()->subDays(rand(1, 90)),
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

        foreach ($areas as $area) {
            $lotsToCreate = (int) $area->total_lots;
            $remainingLots = min((int) $area->remaining_lots, $lotsToCreate);
            $prefix = $this->lotPrefix($area->name);
            $existingLots = DB::table('lots')
                ->where('area_id', $area->id)
                ->orderByRaw('COALESCE(coordinate_y, 999999), COALESCE(coordinate_x, 999999), created_at, id')
                ->get()
                ->values();

            foreach ($existingLots as $index => $lot) {
                DB::table('lots')
                    ->where('id', $lot->id)
                    ->update([
                        'code' => '__seed_tmp_' . $index . '_' . $lot->id,
                        'updated_at' => Carbon::now(),
                    ]);
            }

            $unavailableLots = $lotsToCreate - $remainingLots;
            $reservedLots = (int) floor($unavailableLots * 0.25);
            $notForSaleLots = (int) floor($unavailableLots * 0.1);
            $soldLots = $unavailableLots - $reservedLots - $notForSaleLots;
            $statuses = [
                ...array_fill(0, $remainingLots, LotStatus::AVAILABLE->value),
                ...array_fill(0, $reservedLots, LotStatus::RESERVED->value),
                ...array_fill(0, $soldLots, LotStatus::SOLD->value),
                ...array_fill(0, $notForSaleLots, LotStatus::UNAVAILABLE->value),
            ];

            for ($i = 1; $i <= $lotsToCreate; $i++) {
                $column = ($i - 1) % 10;
                $row = intdiv($i - 1, 10);
                $payload = [
                    'area_id'      => $area->id,
                    'code'         => "{$prefix}-" . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status'       => $statuses[$i - 1] ?? LotStatus::SOLD->value,
                    'area_size'    => 82 + (($i * 7) % 140),
                    'direction'    => $directions[($i - 1) % count($directions)],
                    'price'        => (2 + ($i % 14)) * 1000000000,
                    'unit_price'   => (30 + ($i % 70)) * 1000000,
                    'coordinate_x' => 48 + ($column * 72),
                    'coordinate_y' => 48 + ($row * 64),
                    'width'        => 58,
                    'height'       => 48,
                    'image_url'    => "https://picsum.photos/seed/lot{$i}/400/300",
                    'frontage'     => 4 + (($i % 7) * 0.6),
                    'legal'        => $i % 2 === 0 ? 'Sổ hồng riêng' : 'Sổ đỏ',
                    'description'  => "Lô đất số {$i} thuộc {$area->name}. Vị trí đẹp, thoáng mát.",
                    'is_locked'    => false,
                    'updated_at'   => Carbon::now(),
                ];

                $existing = $existingLots->get($i - 1);
                if ($existing) {
                    DB::table('lots')->where('id', $existing->id)->update([
                        ...$payload,
                        'deleted_at' => null,
                    ]);
                    continue;
                }

                DB::table('lots')->insert([
                    ...$payload,
                    'id'           => Str::uuid()->toString(),
                    'created_at'   => Carbon::now()->subDays(rand(1, 60)),
                ]);
            }

            foreach ($existingLots->slice($lotsToCreate) as $lot) {
                DB::table('lots')
                    ->where('id', $lot->id)
                    ->update([
                        'deleted_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
            }

            $this->command->line("  ✔ Đồng bộ {$lotsToCreate} lots cho area: {$area->name} ({$remainingLots} lô còn hàng)");
        }
    }

    private function lotPrefix(string $areaName): string
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $areaName);

        return strtoupper(substr($letters ?: 'L', 0, 1));
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
