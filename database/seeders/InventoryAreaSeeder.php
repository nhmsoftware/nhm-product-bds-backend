<?php

namespace Database\Seeders;

use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Project\Models\Enums\ProjectStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InventoryAreaSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password123';

    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();
            $users = $this->ensureDemoUsers($now);
            $areas = $this->seedAreas($now);

            $this->seedAreaAssignments($users, $areas, $now);

            $this->command?->info('Seeded inventory demo data: ' . count($areas) . ' areas.');
        });
    }

    private function ensureDemoUsers(Carbon $now): array
    {
        $usersData = [
            [
                'email' => 'employee@test.com',
                'name' => 'Nguyễn Văn Nhân Viên',
                'phone' => '0900000001',
                'staff_code' => 'TEST-EMP-001',
                'role' => UserRole::EMPLOYEE->value,
                'department' => 'HN',
                'job_position' => 'Nhân viên kinh doanh test',
                'area' => 'Hà Nội',
            ],
            [
                'email' => 'employee2@test.com',
                'name' => 'Võ Thị Nhân Viên Mới',
                'phone' => '0900000006',
                'staff_code' => 'TEST-EMP-002',
                'role' => UserRole::EMPLOYEE->value,
                'department' => 'HCM',
                'job_position' => 'Nhân viên kinh doanh test',
                'area' => 'Hồ Chí Minh',
            ],
            [
                'email' => 'manager@test.com',
                'name' => 'Trần Thị Trưởng Phòng',
                'phone' => '0900000002',
                'staff_code' => 'TEST-MGR-001',
                'role' => UserRole::MANAGER->value,
                'department' => 'HN',
                'job_position' => 'Trưởng phòng kinh doanh',
                'area' => 'Hà Nội',
            ],
            [
                'email' => 'director@test.com',
                'name' => 'Lê Văn Giám Đốc',
                'phone' => '0900000003',
                'staff_code' => 'TEST-DIR-001',
                'role' => UserRole::DIRECTOR->value,
                'department' => 'HN',
                'job_position' => 'Giám đốc khu vực',
                'area' => 'Hà Nội',
            ],
            [
                'email' => 'ceo@test.com',
                'name' => 'Phạm Thị Tổng Giám Đốc',
                'phone' => '0900000004',
                'staff_code' => 'TEST-CEO-001',
                'role' => UserRole::CEO->value,
                'department' => 'ALL',
                'job_position' => 'Tổng giám đốc',
                'area' => 'Toàn quốc',
            ],
            [
                'email' => 'superadmin@test.com',
                'name' => 'Super Admin Test',
                'phone' => '0900000005',
                'staff_code' => 'TEST-SA-001',
                'role' => UserRole::SUPER_ADMIN->value,
                'department' => 'SYSTEM',
                'job_position' => 'Quản trị hệ thống',
                'area' => 'Toàn quốc',
            ],
            [
                'email' => 'customer@test.com',
                'name' => 'Khách Hàng Demo',
                'phone' => '0900000007',
                'staff_code' => 'TEST-CUS-001',
                'role' => UserRole::BUYER->value,
                'department' => null,
                'job_position' => 'Khách hàng',
                'area' => null,
            ],
        ];

        $users = [];

        foreach ($usersData as $data) {
            $existing = DB::table('users')->where('email', $data['email'])->first();
            $phone = $this->resolveDemoPhone($data['phone'], $data['email']);
            $payload = [
                'staff_code' => $data['staff_code'],
                'name' => $data['name'],
                'phone' => $phone,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role' => $data['role'],
                'department' => $data['department'],
                'job_position' => $data['job_position'],
                'area' => $data['area'],
                'is_active' => true,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update($payload);
                $users[$data['email']] = DB::table('users')->where('id', $existing->id)->first();
                continue;
            }

            $id = (string) Str::uuid();
            DB::table('users')->insert([
                ...$payload,
                'id' => $id,
                'email' => $data['email'],
                'created_at' => $now,
            ]);
            $users[$data['email']] = DB::table('users')->where('id', $id)->first();
        }

        return $users;
    }

    private function resolveDemoPhone(string $preferredPhone, string $email): string
    {
        $phoneOwner = DB::table('users')
            ->where('phone', $preferredPhone)
            ->where('email', '!=', $email)
            ->first();

        if (!$phoneOwner) {
            return $preferredPhone;
        }

        for ($index = 80; $index <= 99; $index++) {
            $candidate = '09000000' . $index;
            $exists = DB::table('users')
                ->where('phone', $candidate)
                ->where('email', '!=', $email)
                ->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        return $preferredPhone;
    }

    private function seedAreas(Carbon $now): array
    {
        $areasData = [
            [
                'name' => 'The Solaria - Phân khu Aurora',
                'prefix' => 'AUR',
                'direction' => 'Đông Nam',
                'area_size' => 4120.5,
                'price' => 5200000000,
                'unit_price' => 52000000,
                'status' => AreaStatus::OPENING->value,
                'is_featured' => true,
                'image_seed' => 'inventory-aurora',
                'planning_ref' => 'INV-AURORA',
                'project' => [
                    'name' => 'The Solaria',
                    'location' => 'Phường Bãi Cháy, Quảng Ninh',
                    'branch' => 'Quảng Ninh',
                    'type' => 'Đất nền khu đô thị biển',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=20.949083,107.073706',
                    'description' => 'Dự án khu đô thị biển phục vụ dữ liệu thử nghiệm cho bảng hàng và chỉ đường trên mobile.',
                    'keywords' => ['đất nền', 'khu đô thị biển', 'Quảng Ninh'],
                    'amenities' => ['công viên ven biển', 'phố thương mại', 'bến du thuyền'],
                    'legal_info' => ['quy hoạch 1/500', 'sổ đỏ từng lô'],
                    'planning_info' => ['Mã quy hoạch' => 'INV-AURORA'],
                ],
                'sold' => 5,
                'reserved' => 3,
                'unavailable' => 2,
            ],
            [
                'name' => 'Eco Garden - Khu Palm Residence',
                'prefix' => 'PAL',
                'direction' => 'Tây Nam',
                'area_size' => 3680.25,
                'price' => 3900000000,
                'unit_price' => 41000000,
                'status' => AreaStatus::OPENING->value,
                'is_featured' => true,
                'image_seed' => 'inventory-palm',
                'planning_ref' => 'INV-PALM',
                'project' => [
                    'name' => 'Eco Garden',
                    'location' => 'Xã Đông Dư, Gia Lâm, Hà Nội',
                    'branch' => 'Hà Nội',
                    'type' => 'Khu nhà ở sinh thái',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=21.008261,105.933951',
                    'description' => 'Dự án nhà ở sinh thái có dữ liệu khu đất, lô đất và liên kết chỉ đường phục vụ kiểm thử.',
                    'keywords' => ['nhà ở sinh thái', 'Gia Lâm', 'Hà Nội'],
                    'amenities' => ['hồ cảnh quan', 'đường dạo bộ', 'vườn nội khu'],
                    'legal_info' => ['pháp lý minh bạch', 'sổ hồng riêng'],
                    'planning_info' => ['Mã quy hoạch' => 'INV-PALM'],
                ],
                'sold' => 7,
                'reserved' => 4,
                'unavailable' => 1,
            ],
            [
                'name' => 'Riverfront City - Shophouse Central',
                'prefix' => 'RFC',
                'direction' => 'Đông Bắc',
                'area_size' => 2860.75,
                'price' => 7600000000,
                'unit_price' => 68000000,
                'status' => AreaStatus::OPENING->value,
                'is_featured' => true,
                'image_seed' => 'inventory-riverfront',
                'planning_ref' => 'INV-RFC',
                'project' => [
                    'name' => 'Riverfront City',
                    'location' => 'Phường Thảo Điền, Thành phố Hồ Chí Minh',
                    'branch' => 'Hồ Chí Minh',
                    'type' => 'Shophouse ven sông',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=10.807639,106.732631',
                    'description' => 'Dự án shophouse ven sông dùng để kiểm thử luồng kho hàng và chỉ đường cho nhân viên.',
                    'keywords' => ['shophouse', 'ven sông', 'Thảo Điền'],
                    'amenities' => ['bến thuyền', 'quảng trường ven sông', 'tuyến phố thương mại'],
                    'legal_info' => ['sở hữu lâu dài', 'hồ sơ pháp lý đầy đủ'],
                    'planning_info' => ['Mã quy hoạch' => 'INV-RFC'],
                ],
                'sold' => 8,
                'reserved' => 2,
                'unavailable' => 2,
            ],
            [
                'name' => 'Horizon Hills - Biệt thự đồi thông',
                'prefix' => 'HH',
                'direction' => 'Nam',
                'area_size' => 5400.0,
                'price' => 9800000000,
                'unit_price' => 74000000,
                'status' => AreaStatus::COMING_SOON->value,
                'is_featured' => false,
                'image_seed' => 'inventory-horizon',
                'planning_ref' => 'INV-HH',
                'project' => [
                    'name' => 'Horizon Hills',
                    'location' => 'Phường 3, Đà Lạt, Lâm Đồng',
                    'branch' => 'Lâm Đồng',
                    'type' => 'Biệt thự nghỉ dưỡng đồi thông',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=11.925901,108.438913',
                    'description' => 'Dự án biệt thự nghỉ dưỡng đồi thông có dữ liệu mẫu cho bảng hàng và bản đồ lô đất.',
                    'keywords' => ['biệt thự nghỉ dưỡng', 'Đà Lạt', 'đồi thông'],
                    'amenities' => ['đường dạo bộ', 'khu ngắm cảnh', 'câu lạc bộ cư dân'],
                    'legal_info' => ['quy hoạch nghỉ dưỡng', 'hồ sơ từng lô'],
                    'planning_info' => ['Mã quy hoạch' => 'INV-HH'],
                ],
                'sold' => 2,
                'reserved' => 2,
                'unavailable' => 4,
            ],
            [
                'name' => 'Metro Square - Khu căn hộ dịch vụ',
                'prefix' => 'MSQ',
                'direction' => 'Bắc',
                'area_size' => 2310.4,
                'price' => 3450000000,
                'unit_price' => 36000000,
                'status' => AreaStatus::OPENING->value,
                'is_featured' => false,
                'image_seed' => 'inventory-metro',
                'planning_ref' => 'INV-MSQ',
                'project' => [
                    'name' => 'Metro Square',
                    'location' => 'Phường An Phú, Thành phố Thủ Đức',
                    'branch' => 'Hồ Chí Minh',
                    'type' => 'Căn hộ dịch vụ',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=10.802705,106.742747',
                    'description' => 'Dự án căn hộ dịch vụ có dữ liệu mẫu phục vụ kiểm thử danh sách khu đất và chỉ đường.',
                    'keywords' => ['căn hộ dịch vụ', 'Thủ Đức', 'metro'],
                    'amenities' => ['sảnh đón khách', 'khu thương mại', 'bãi đỗ xe thông minh'],
                    'legal_info' => ['giấy phép xây dựng', 'hồ sơ nghiệm thu'],
                    'planning_info' => ['Mã quy hoạch' => 'INV-MSQ'],
                ],
                'sold' => 4,
                'reserved' => 5,
                'unavailable' => 1,
            ],
            [
                'name' => 'Coastal Bay - Khu nghỉ dưỡng biển',
                'prefix' => 'CB',
                'direction' => 'Tây Bắc',
                'area_size' => 6200.9,
                'price' => 12200000000,
                'unit_price' => 88000000,
                'status' => AreaStatus::OPENING->value,
                'is_featured' => true,
                'image_seed' => 'inventory-coastal',
                'planning_ref' => 'INV-CB',
                'project' => [
                    'name' => 'Coastal Bay',
                    'location' => 'Phường Mũi Né, Phan Thiết, Bình Thuận',
                    'branch' => 'Bình Thuận',
                    'type' => 'Khu nghỉ dưỡng biển',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=10.945405,108.287894',
                    'description' => 'Dự án nghỉ dưỡng biển có dữ liệu bảng hàng đầy đủ để kiểm thử trên thiết bị di động.',
                    'keywords' => ['nghỉ dưỡng biển', 'Mũi Né', 'Phan Thiết'],
                    'amenities' => ['bãi biển riêng', 'khu thể thao nước', 'nhà hàng ven biển'],
                    'legal_info' => ['quy hoạch du lịch', 'hồ sơ pháp lý dự án'],
                    'planning_info' => ['Mã quy hoạch' => 'INV-CB'],
                ],
                'sold' => 6,
                'reserved' => 3,
                'unavailable' => 1,
            ],
        ];

        $areas = [];

        foreach ($areasData as $index => $data) {
            $totalLots = 24;
            $remainingLots = $totalLots - $data['sold'] - $data['reserved'] - $data['unavailable'];
            $planningUrl = "https://quyhoach24h.vn?ref={$data['planning_ref']}";
            $project = $this->seedProjectForArea($data, $totalLots, $remainingLots, $now);
            $existing = DB::table('areas')
                ->where('planning_check_url', $planningUrl)
                ->orWhere('name', $data['name'])
                ->first();
            $areaId = $existing->id ?? (string) Str::uuid();
            $areaPayload = [
                'project_id' => $project->id,
                'name' => $data['name'],
                'sales_board_image' => "https://picsum.photos/seed/{$data['image_seed']}/1200/800",
                'sales_board_iframe' => null,
                'planning_check_url' => $planningUrl,
                'sales_board_images' => json_encode([
                    "https://picsum.photos/seed/{$data['image_seed']}-1/1200/800",
                    "https://picsum.photos/seed/{$data['image_seed']}-2/1200/800",
                ], JSON_UNESCAPED_SLASHES),
                'total_lots' => $totalLots,
                'remaining_lots' => $remainingLots,
                'area_size' => $data['area_size'],
                'direction' => $data['direction'],
                'price' => $data['price'],
                'unit_price' => $data['unit_price'],
                'status' => $data['status'],
                'is_featured' => $data['is_featured'],
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($existing) {
                DB::table('areas')->where('id', $areaId)->update($areaPayload);
            } else {
                DB::table('areas')->insert([
                    ...$areaPayload,
                    'id' => $areaId,
                    'created_at' => $now->copy()->subDays(20 - $index),
                ]);
            }

            $this->seedLots($areaId, $data, $totalLots, $now);
            $areas[$data['name']] = DB::table('areas')->where('id', $areaId)->first();
        }

        return $areas;
    }

    private function seedProjectForArea(array $areaData, int $totalLots, int $remainingLots, Carbon $now): object
    {
        $projectData = $areaData['project'];
        $existing = DB::table('projects')->where('name', $projectData['name'])->first();
        $projectId = $existing->id ?? (string) Str::uuid();
        $payload = [
            'name' => $projectData['name'],
            'keywords' => $this->json($projectData['keywords']),
            'location' => $projectData['location'],
            'google_maps_url' => $projectData['google_maps_url'],
            'planning_info' => $this->json($projectData['planning_info']),
            'image' => "https://picsum.photos/seed/{$areaData['image_seed']}-project/1200/800",
            'banner' => "https://picsum.photos/seed/{$areaData['image_seed']}-banner/1600/900",
            'price' => $areaData['price'],
            'status' => $areaData['status'] === AreaStatus::COMING_SOON->value
                ? ProjectStatus::COMING_SOON->value
                : ProjectStatus::OPENING->value,
            'type' => $projectData['type'],
            'is_public' => true,
            'description' => $projectData['description'],
            'amenities' => $this->json($projectData['amenities']),
            'floor_plans' => $this->json([
                'Sơ đồ tổng thể' => "https://picsum.photos/seed/{$areaData['image_seed']}-floor-plan/1200/800",
            ]),
            'legal_info' => $this->json($projectData['legal_info']),
            'brochure' => "https://example.com/brochures/{$areaData['planning_ref']}.pdf",
            'contact_info' => $this->json([
                'hotline' => '1900 636 668',
                'email' => 'kinhdoanh@nhm.vn',
            ]),
            'branch' => $projectData['branch'],
            'total_lots' => $totalLots,
            'remaining_lots' => $remainingLots,
            'is_featured' => $areaData['is_featured'],
            'is_locked' => false,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing) {
            DB::table('projects')->where('id', $projectId)->update($payload);
        } else {
            DB::table('projects')->insert([
                ...$payload,
                'id' => $projectId,
                'created_at' => $now->copy()->subDays(30),
            ]);
        }

        return DB::table('projects')->where('id', $projectId)->first();
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function seedLots(string $areaId, array $areaData, int $totalLots, Carbon $now): void
    {
        $statuses = [];
        $availableCount = $totalLots - $areaData['sold'] - $areaData['reserved'] - $areaData['unavailable'];
        $statuses = array_merge($statuses, array_fill(0, $availableCount, LotStatus::AVAILABLE->value));
        $statuses = array_merge($statuses, array_fill(0, $areaData['reserved'], LotStatus::RESERVED->value));
        $statuses = array_merge($statuses, array_fill(0, $areaData['sold'], LotStatus::SOLD->value));
        $statuses = array_merge($statuses, array_fill(0, $areaData['unavailable'], LotStatus::UNAVAILABLE->value));

        for ($i = 1; $i <= $totalLots; $i++) {
            $code = $areaData['prefix'] . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $existing = DB::table('lots')
                ->where('area_id', $areaId)
                ->where('code', $code)
                ->first();
            $column = ($i - 1) % 6;
            $row = intdiv($i - 1, 6);
            $status = $statuses[$i - 1] ?? LotStatus::AVAILABLE->value;
            $price = $areaData['price'] + ($i * 125000000);
            $payload = [
                'area_id' => $areaId,
                'code' => $code,
                'status' => $status,
                'image_url' => "https://picsum.photos/seed/{$areaData['prefix']}-lot-{$i}/900/600",
                'images' => json_encode([
                    "https://picsum.photos/seed/{$areaData['prefix']}-lot-{$i}-a/900/600",
                    "https://picsum.photos/seed/{$areaData['prefix']}-lot-{$i}-b/900/600",
                ], JSON_UNESCAPED_SLASHES),
                'area_size' => 82 + (($i % 8) * 12.5),
                'frontage' => 5 + (($i % 5) * 0.5),
                'direction' => $areaData['direction'],
                'legal' => $i % 3 === 0 ? 'Sổ đỏ lâu dài' : 'Sổ hồng riêng',
                'description' => "Lô {$code} thuộc {$areaData['name']}, phù hợp tư vấn khách hàng demo trên mobile.",
                'planning_id' => null,
                'price' => $price,
                'unit_price' => $areaData['unit_price'],
                'coordinate_x' => 24 + ($column * 64),
                'coordinate_y' => 24 + ($row * 56),
                'width' => 52,
                'height' => 44,
                'is_locked' => $status === LotStatus::RESERVED->value,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($existing) {
                DB::table('lots')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('lots')->insert([
                ...$payload,
                'id' => (string) Str::uuid(),
                'created_at' => $now,
            ]);
        }
    }

    private function seedAreaAssignments(array $users, array $areas, Carbon $now): void
    {
        $areaList = array_values($areas);
        $assignmentPlan = [
            'employee@test.com' => array_slice($areaList, 0, 4),
            'manager@test.com' => array_slice($areaList, 0, 5),
            'director@test.com' => $areaList,
            'ceo@test.com' => $areaList,
            'superadmin@test.com' => $areaList,
        ];

        foreach ($assignmentPlan as $email => $assignedAreas) {
            if (!isset($users[$email])) {
                continue;
            }

            foreach ($assignedAreas as $area) {
                $existing = DB::table('area_assignments')
                    ->where('user_id', $users[$email]->id)
                    ->where('area_id', $area->id)
                    ->first();

                if ($existing) {
                    DB::table('area_assignments')->where('id', $existing->id)->update([
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
                    continue;
                }

                DB::table('area_assignments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $users[$email]->id,
                    'area_id' => $area->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
