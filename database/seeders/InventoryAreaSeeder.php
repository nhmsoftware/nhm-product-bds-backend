<?php

namespace Database\Seeders;

use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Project\Models\Enums\ProjectStatus;
use App\Modules\Planning\Models\Enums\PlanningStatus;
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
                'job_position' => 'Nhân viên kinh doanh',
                'area' => 'Hà Nội',
            ],
            [
                'email' => 'employee2@test.com',
                'name' => 'Võ Thị Nhân Viên Mới',
                'phone' => '0900000006',
                'staff_code' => 'TEST-EMP-002',
                'role' => UserRole::EMPLOYEE->value,
                'department' => 'HCM',
                'job_position' => 'Nhân viên kinh doanh',
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
                'planning_check_ref' => 'C5WA63ND',
                'project' => [
                    'name' => 'The Solaria',
                    'location' => 'Phường Bãi Cháy, Quảng Ninh',
                    'branch' => 'Quảng Ninh',
                    'type' => 'Đất nền khu đô thị biển',
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=20.949083,107.073706',
                    'description' => <<<'DESC'
Đô thị biển The Solaria được phát triển như một điểm đến an cư và đầu tư có nhịp sống chậm, trong lành, nhưng vẫn giữ kết nối thuận tiện với trung tâm Quảng Ninh. Dọc theo tuyến cảnh quan ven biển là chuỗi tiện ích mở, công viên, lối dạo bộ và các không gian sinh hoạt cộng đồng được tổ chức theo tinh thần nghỉ dưỡng dài ngày.

Dự án được thiết kế để phục vụ cả khách hàng ở thực lẫn nhà đầu tư tìm kiếm một tài sản có câu chuyện rõ ràng, vị trí đẹp và khả năng khai thác tốt theo thời gian. Các sản phẩm trong khu có quy hoạch mạch lạc, hạ tầng nội khu đồng bộ và tầm nhìn hướng biển là điểm nhấn lớn nhất của toàn bộ dự án.

Mỗi khu vực của The Solaria đều được định vị theo trải nghiệm sống xanh, riêng tư và dễ tiếp cận hệ tiện ích xung quanh, giúp dự án phù hợp với nhóm khách hàng muốn tìm một nơi vừa có giá trị sử dụng, vừa có giá trị tích lũy lâu dài.
DESC,
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
                    'description' => <<<'DESC'
Eco Garden là khu nhà ở sinh thái hướng đến nhịp sống cân bằng, nơi mật độ xây dựng được tiết chế để nhường chỗ cho mặt nước, cây xanh và các tuyến dạo bộ nội khu. Cách tổ chức không gian ở đây ưu tiên sự thoáng đãng, tạo cảm giác dễ chịu cho cư dân trong từng hoạt động hằng ngày.

Dự án phù hợp với nhóm khách hàng trẻ và gia đình đang tìm một môi trường sống có nhiều khoảng thở, nhưng vẫn kết nối thuận tiện đến các trục giao thông chính. Sản phẩm trong khu được thiết kế theo tinh thần thực dụng, gọn gàng và giàu tính ứng dụng, giúp việc khai thác ở thực lẫn đầu tư đều có cơ sở rõ ràng.
DESC,
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
                    'description' => <<<'DESC'
Riverfront City được định vị như một dải shophouse ven sông giàu tiềm năng thương mại, nơi mặt tiền và dòng khách qua lại tạo nên giá trị khai thác rõ rệt. Cảnh quan nước và tuyến phố đi bộ nội khu giúp dự án giữ được nét sôi động nhưng vẫn có độ thoáng cần thiết cho cư dân và khách ghé thăm.
DESC,
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
                    'description' => <<<'DESC'
Horizon Hills là quần thể biệt thự nghỉ dưỡng nằm giữa đồi thông, được xây dựng theo tinh thần tách biệt khỏi nhịp sống ồn ào nhưng vẫn giữ sự sang trọng cần có của một sản phẩm cao cấp. Các tuyến cảnh quan dốc nhẹ, lớp cây xanh nhiều tầng và khoảng nhìn mở ra không gian tự nhiên tạo cảm giác riêng tư ngay từ khi bước vào dự án.

Từng căn biệt thự được bố trí để đón nắng, đón gió và tận dụng lợi thế địa hình, nhấn mạnh trải nghiệm nghỉ dưỡng hơn là chỉ đơn thuần là một sản phẩm nhà ở. Đây là kiểu tài sản thường phù hợp với khách hàng ưu tiên cảm xúc sống, giá trị lưu trú và khả năng giữ giá theo thời gian.

Với hệ tiện ích gắn với nghỉ dưỡng và các mảng xanh lớn bao quanh, Horizon Hills tạo ra một câu chuyện đầu tư thiên về chất lượng sống, phù hợp với nhóm khách hàng tìm sản phẩm có bản sắc rõ và không bị hòa lẫn với các khu dân cư thông thường.
DESC,
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
                    'description' => <<<'DESC'
Metro Square là dự án căn hộ dịch vụ có cách tiếp cận thực dụng, chú trọng tính vận hành, khả năng cho thuê và sự linh hoạt của không gian ở. Quy hoạch tổng thể được tổ chức theo hướng tối ưu dòng di chuyển, tối ưu tiện ích dùng chung và tạo ra cảm giác hiện đại, gọn gàng cho cư dân trẻ.

Nhờ vị trí kết nối thuận tiện và nhu cầu thuê ổn định từ nhóm khách hàng chuyên gia, dự án có lợi thế nhất định về khai thác dòng tiền. Đây là kiểu sản phẩm thường phù hợp với người mua muốn một tài sản dễ vận hành, dễ cho thuê và ít phát sinh nhu cầu quản lý phức tạp.
DESC,
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
                    'description' => <<<'DESC'
Coastal Bay sở hữu lợi thế lớn từ mặt nước, không khí biển và nhịp sống nghỉ dưỡng đặc trưng của vùng ven bờ. Dự án được định hướng như một tổ hợp lưu trú và nghỉ ngơi, nơi cư dân có thể tận hưởng không gian mở, ánh sáng tự nhiên và hệ cảnh quan kết nối trực tiếp với yếu tố biển.
DESC,
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
            $planningRef = $data['planning_check_ref'] ?? $data['planning_ref'];
            $planningUrl = "https://quyhoach24h.vn?ref={$planningRef}";
            $project = $this->seedProjectForArea($data, $totalLots, $remainingLots, $now, $planningUrl);
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

            $this->seedPlanningForArea($data, $planningUrl, $now);
            $this->seedLots($areaId, $data, $totalLots, $now);
            $areas[$data['name']] = DB::table('areas')->where('id', $areaId)->first();
        }

        return $areas;
    }

    private function seedPlanningForArea(array $areaData, string $planningUrl, Carbon $now): void
    {
        $projectData = $areaData['project'];
        $existing = DB::table('plannings')->where('pdf_url', $planningUrl)->first();
        $planningId = $existing->id ?? (string) Str::uuid();
        [$city, $district] = $this->planningLocationParts($projectData['location']);

        $payload = [
            'title' => "Quy hoạch {$projectData['name']}",
            'map_image' => "https://picsum.photos/seed/{$areaData['image_seed']}-planning-map/1200/800",
            'status' => PlanningStatus::PUBLIC->value,
            'updated_year' => (int) $now->format('Y'),
            'description' => "Tra cứu bản đồ quy hoạch trực tuyến cho {$areaData['name']} qua hệ thống Quy hoạch 24h.",
            'city' => $city,
            'district' => $district,
            'sub_area' => $areaData['name'],
            'symbol' => $areaData['planning_ref'],
            'density' => $projectData['type'],
            'max_height' => 'Theo hồ sơ quy hoạch',
            'land_use_ratio' => 'Đang cập nhật',
            'setback' => 'Theo từng tuyến đường',
            'land_type_notes' => 'Dữ liệu chi tiết được mở trong bản đồ quy hoạch trực tuyến.',
            'pdf_url' => $planningUrl,
            'latitude' => null,
            'longitude' => null,
            'content' => "Ứng dụng mở liên kết quy hoạch bên thứ ba để khách hàng xem lớp bản đồ, vị trí và thông tin kiểm tra mới nhất của {$projectData['name']}.",
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing) {
            DB::table('plannings')->where('id', $planningId)->update($payload);
        } else {
            DB::table('plannings')->insert([
                ...$payload,
                'id' => $planningId,
                'created_at' => $now->copy()->subDays(15),
            ]);
        }
    }

    private function planningLocationParts(string $location): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        $city = $parts[count($parts) - 1] ?? 'Khác';
        $district = $parts[count($parts) - 2] ?? null;

        return [$city, $district];
    }

    private function seedProjectForArea(array $areaData, int $totalLots, int $remainingLots, Carbon $now, string $planningUrl): object
    {
        $projectData = $areaData['project'];
        $existing = DB::table('projects')->where('name', $projectData['name'])->first();
        $projectId = $existing->id ?? (string) Str::uuid();
        $payload = [
            'name' => $projectData['name'],
            'keywords' => $this->json($projectData['keywords']),
            'location' => $projectData['location'],
            'google_maps_url' => $projectData['google_maps_url'],
            'location_image' => "https://picsum.photos/seed/{$areaData['image_seed']}-location-map/1200/900",
            'planning_info' => $this->json([
                ...$projectData['planning_info'],
                'Link tra cứu quy hoạch' => $planningUrl,
                'Ảnh quy hoạch' => "https://picsum.photos/seed/{$areaData['image_seed']}-planning-map/1200/800",
            ]),
            'image' => "https://picsum.photos/seed/{$areaData['image_seed']}-project/1200/800",
            'banner' => $this->json($this->projectBannerImages($areaData['image_seed'])),
            'price' => $areaData['price'],
            'status' => $areaData['status'] === AreaStatus::COMING_SOON->value
                ? ProjectStatus::COMING_SOON->value
                : ProjectStatus::OPENING->value,
            'type' => $projectData['type'],
            'is_public' => true,
            'description' => $projectData['description'],
            'amenities' => $this->json($this->projectAmenities($areaData['image_seed'])),
            'floor_plans' => $this->json([
                'Sơ đồ tổng thể' => "https://picsum.photos/seed/{$areaData['image_seed']}-floor-plan/1200/800",
            ]),
            'legal_info' => $this->json($projectData['legal_info']),
            'brochure' => rtrim(config('app.url'), '/') . "/brochures/{$areaData['planning_ref']}.pdf",
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

    private function projectBannerImages(string $seed): array
    {
        $bannerCount = 3 + (abs(crc32($seed)) % 3);

        return array_map(
            fn (int $index) => "https://picsum.photos/seed/{$seed}-banner-{$index}/1600/900",
            range(1, $bannerCount)
        );
    }

    private function projectAmenities(string $seed): array
    {
        $amenities = [
            'Bể bơi vô cực',
            'Phòng gym 5 sao',
            'Công viên trung tâm',
            'An ninh 24/7',
            'Hồ cảnh quan',
            'Đường dạo bộ',
            'Vườn nội khu',
            'Phố thương mại',
            'Bến du thuyền',
            'Quảng trường ven sông',
            'Tuyến phố thương mại',
            'Khu ngắm cảnh',
            'Câu lạc bộ cư dân',
            'Sảnh đón khách',
            'Khu thương mại',
            'Bãi đỗ xe thông minh',
            'Bãi biển riêng',
            'Khu thể thao nước',
            'Nhà hàng ven biển',
        ];

        $start = abs(crc32($seed)) % count($amenities);
        $count = 3 + (abs(crc32("{$seed}-amenities")) % 2);
        $selected = [];

        for ($offset = 0; count($selected) < $count; $offset += 1) {
            $selected[] = $amenities[($start + ($offset * 5)) % count($amenities)];
        }

        return $selected;
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
