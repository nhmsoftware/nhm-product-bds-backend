<?php

namespace Database\Seeders;

use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Planning\Models\Enums\PlanningStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InventoryAreaSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password123';

    public function run(): void
    {
        // Seeder chính cho danh sách 6 khu đất/bảng hàng demo trên app nhân viên.
        DB::transaction(function () {
            $now = Carbon::now();
            
            // 1. Seed các chi nhánh trước để lấy ID
            $branches = [
                ['name' => 'Hà Nội', 'code' => 'HN'],
                ['name' => 'Hồ Chí Minh', 'code' => 'HCM'],
                ['name' => 'Đà Nẵng', 'code' => 'DN'],
                ['name' => 'Quảng Ninh', 'code' => 'QN'],
                ['name' => 'Lâm Đồng', 'code' => 'LD'],
                ['name' => 'Bình Thuận', 'code' => 'BT'],
            ];
            foreach ($branches as $index => $br) {
                $existingId = DB::table('branches')->where('code', $br['code'])->value('id');
                DB::table('branches')->updateOrInsert(
                    ['code' => $br['code']],
                    [
                        'id' => $existingId ?? (string) Str::uuid(),
                        'name' => $br['name'],
                        'area' => $br['name'],
                        'is_active' => true,
                        'sort' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            // 2. Seed các phòng ban trước để lấy ID
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
                $existingId = DB::table('departments')->where('code', $dept['code'])->value('id');
                DB::table('departments')->updateOrInsert(
                    ['code' => $dept['code']],
                    [
                        'id' => $existingId ?? (string) Str::uuid(),
                        'name' => $dept['name'],
                        'kpi_quota' => $dept['kpi_quota'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $users = $this->ensureDemoUsers($now);
            $this->seedPlanningSubAreas($now);
            $areas = $this->seedAreas($now);
            $this->backfillAreaGoogleMapsUrls($now);

            $this->seedAreaAssignments($users, $areas, $now);

            $this->command?->info('Seeded inventory demo data: ' . count($areas) . ' areas, ' . DB::table('lots')->whereNull('deleted_at')->count() . ' lots.');
        });
    }

    private function ensureDemoUsers(Carbon $now): array
    {
        $usersData = [
            [
                'email' => 'employee@test.com',
                'name' => 'Nguyễn Văn Nhân Viên',
                'phone' => '0900000001',
                'staff_code' => '0900000001',
                'role' => UserRole::EMPLOYEE->value,
                'department' => 'Kinh doanh',
                'job_position' => 'Chuyên viên kinh doanh',
                'area' => 'Hà Nội',
                'branch' => 'Hà Nội',
            ],
            [
                'email' => 'employee2@test.com',
                'name' => 'Võ Thị Nhân Viên Mới',
                'phone' => '0900000006',
                'staff_code' => '0900000006',
                'role' => UserRole::EMPLOYEE->value,
                'department' => 'Kinh doanh',
                'job_position' => 'Cộng tác viên',
                'area' => 'Hà Nội',
                'branch' => 'Hà Nội',
            ],
            [
                'email' => 'candidate@test.com',
                'name' => 'Ứng Viên Chưa Duyệt',
                'phone' => '0900000008',
                'staff_code' => '0900000008',
                'role' => UserRole::EMPLOYEE->value,
                'department' => null,
                'job_position' => null,
                'area' => null,
                'branch' => null,
            ],
            [
                'email' => 'manager@test.com',
                'name' => 'Trần Thị Trưởng Phòng',
                'phone' => '0900000002',
                'staff_code' => '0900000002',
                'role' => UserRole::MANAGER->value,
                'department' => 'Kinh doanh',
                'job_position' => 'Trưởng phòng kinh doanh',
                'area' => 'Hà Nội',
                'branch' => 'Hà Nội',
            ],
            [
                'email' => 'director@test.com',
                'name' => 'Lê Văn Giám Đốc',
                'phone' => '0900000003',
                'staff_code' => '0900000003',
                'role' => UserRole::DIRECTOR->value,
                'department' => 'Kinh doanh',
                'job_position' => 'Giám đốc kinh doanh',
                'area' => 'Hà Nội',
                'branch' => 'Hà Nội',
            ],
            [
                'email' => 'ceo@test.com',
                'name' => 'Phạm Thị Tổng Giám Đốc',
                'phone' => '0900000004',
                'staff_code' => '0900000004',
                'role' => UserRole::CEO->value,
                'department' => null,
                'job_position' => 'Tổng giám đốc',
                'area' => 'Toàn quốc',
                'branch' => null,
            ],
            [
                'email' => 'superadmin@test.com',
                'name' => 'Super Admin Test',
                'phone' => '0900000005',
                'staff_code' => '0900000005',
                'role' => UserRole::SUPER_ADMIN->value,
                'department' => null,
                'job_position' => 'Tổng giám đốc',
                'area' => 'Toàn quốc',
                'branch' => null,
            ],
            [
                'email' => 'customer@test.com',
                'name' => 'Khách Hàng Demo',
                'phone' => '0900000007',
                'staff_code' => null,
                'role' => UserRole::BUYER->value,
                'department' => null,
                'job_position' => null,
                'area' => null,
                'branch' => null,
            ],

        ];

        $users = [];

        $getJobPositionId = function (?string $name) use ($now) {
            if (empty($name)) return null;
            $existingId = DB::table('job_positions')->where('name', $name)->value('id');
            if ($existingId) return $existingId;

            $code = strtoupper(Str::slug($name, '_'));
            return DB::table('job_positions')->insertGetId([
                'name' => $name,
                'code' => $code,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        foreach ($usersData as $data) {
            $existing = DB::table('users')->where('email', $data['email'])->first();
            $phone = $this->resolveDemoPhone($data['phone'], $data['email']);
            $branchId = null;
            if (!empty($data['branch'])) {
                $branchId = DB::table('branches')->where('name', $data['branch'])->value('id');
            }
            $deptId = null;
            if (!empty($data['department'])) {
                $deptId = DB::table('departments')
                    ->where('name', $data['department'])
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->value('id');
            }
            $jobPosId = $getJobPositionId($data['job_position']);

            $payload = [
                'staff_code' => $data['staff_code'],
                'name' => $data['name'],
                'phone' => $phone,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role' => $data['role'],
                'department_id' => $deptId,
                'job_position_id' => $jobPosId,
                'branch_id' => $branchId,
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
                'prefix' => 'A',
                'direction' => 'Đông Nam',
                'area_size' => 4120.5,
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
                'prefix' => 'P',
                'direction' => 'Tây Nam',
                'area_size' => 3680.25,
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
                'prefix' => 'R',
                'direction' => 'Đông Bắc',
                'area_size' => 2860.75,
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
                'prefix' => 'H',
                'direction' => 'Nam',
                'area_size' => 5400.0,
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
                'prefix' => 'M',
                'direction' => 'Bắc',
                'area_size' => 2310.4,
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
                'prefix' => 'C',
                'direction' => 'Tây Bắc',
                'area_size' => 6200.9,
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
            $projectData = $data['project'];
            $existing = DB::table('areas')
                ->where('planning_check_url', $planningUrl)
                ->orWhere('name', $data['name'])
                ->first();
            $areaId = $existing->id ?? (string) Str::uuid();
            $areaPayload = [
                'name' => $data['name'],
                'keywords' => $this->json($projectData['keywords']),
                'location' => $projectData['location'],
                'image' => $this->getPlaceholderUrl(1200, 800, $data['name'], $data['image_seed']),
                'banner' => $this->json($this->projectBannerImages($data['image_seed'], $data['name'])),
                'sales_board_image' => $this->getPlaceholderUrl(1200, 800, "{$data['name']} - Bảng hàng", $data['image_seed']),
                'sales_board_iframe' => null,
                'planning_check_url' => $planningUrl,
                'sales_board_images' => json_encode([
                    $this->getPlaceholderUrl(1200, 800, "{$data['name']} - Sơ đồ 1", $data['image_seed'] . '-1'),
                    $this->getPlaceholderUrl(1200, 800, "{$data['name']} - Sơ đồ 2", $data['image_seed'] . '-2'),
                ], JSON_UNESCAPED_SLASHES),
                'total_lots' => $totalLots,
                'remaining_lots' => $remainingLots,
                'area_size' => $data['area_size'],
                'direction' => $data['direction'],
                'status' => $data['status'],
                'type' => $projectData['type'],
                'area_type_id' => DB::table('area_types')->where('name', $projectData['type'])->value('id'),
                'is_public' => true,
                'description' => $projectData['description'],
                'amenities' => $this->json($this->projectAmenities($data['image_seed'])),
                'floor_plans' => $this->json([
                    'Sơ đồ tổng thể' => $this->getPlaceholderUrl(1200, 800, "{$data['name']} - Sơ đồ tổng thể", $data['image_seed'] . '-floor-plan'),
                ]),
                'legal_info' => $this->json($projectData['legal_info']),
                'brochure' => rtrim(config('app.url'), '/') . "/brochures/{$data['planning_ref']}.pdf",
                'contact_info' => $this->json([
                    'hotline' => '1900 636 668',
                    'email' => 'kinhdoanh@nhm.vn',
                ]),
                'google_maps_url' => $projectData['google_maps_url'],
                'location_image' => $this->getPlaceholderUrl(1200, 900, "{$data['name']} - Bản đồ vị trí", $data['image_seed'] . '-location-map'),
                'planning_info' => $this->json([
                    ...$projectData['planning_info'],
                    'Link tra cứu quy hoạch' => $planningUrl,
                    'Ảnh quy hoạch' => $this->getPlaceholderUrl(1200, 800, "{$data['name']} - Bản đồ quy hoạch", $data['image_seed'] . '-planning-map'),
                ]),
                'branch_id' => DB::table('branches')->where('name', $projectData['branch'])->value('id'),
                'is_featured' => $data['is_featured'],
                'is_locked' => false,
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
            $this->command?->line("  - {$data['name']}: {$totalLots} lô");
            $areas[$data['name']] = DB::table('areas')->where('id', $areaId)->first();
        }

        return $areas;
    }

    private function seedPlanningForArea(array $areaData, string $planningUrl, Carbon $now): void
    {
        $projectData = $areaData['project'];
        $detail = $this->planningDetailForArea($areaData);
        $pdfUrl = $this->ensurePlanningPdfForArea($areaData, $detail);
        $existing = DB::table('plannings')
            ->where('symbol', $detail['symbol'])
            ->orWhere('pdf_url', $planningUrl)
            ->first();
        $planningId = $existing->id ?? (string) Str::uuid();
        $loc = $this->resolveLocationComponents($projectData['location']);
 
         $defaultColors = [
             '#EF4444', '#F97316', '#EAB308', '#22C55E',
             '#14B8A6', '#3B82F6', '#8B5CF6', '#EC4899',
             '#6B7280', '#78350F',
         ];
 
         $landTypeNotes = array_map(function ($label, $index) use ($defaultColors) {
             return [
                 'label' => $label,
                 'color' => $defaultColors[$index % count($defaultColors)],
             ];
         }, $detail['land_types'], array_keys($detail['land_types']));
 
         $payload = [
             'title' => $areaData['name'],
             'map_image' => $this->getPlaceholderUrl(1200, 800, "{$areaData['name']} - Bản đồ quy hoạch", $areaData['image_seed'] . '-planning-map'),
             'status' => PlanningStatus::PUBLIC->value,
             'updated_year' => (int) $now->format('Y'),
             'description' => $detail['description'],
             'city' => $loc['city'],
             'district' => $loc['district'],
             'sub_area' => $detail['zone_title'],
             'symbol' => $detail['symbol'],
             'density' => $detail['density'],
             'max_height' => $detail['max_height'],
             'land_use_ratio' => $detail['land_use_ratio'],
             'setback' => $detail['setback'],
             'land_type_notes' => $this->json($landTypeNotes),
             'pdf_url' => $pdfUrl,
             'latitude' => $loc['latitude'],
             'longitude' => $loc['longitude'],
             'content' => "Hồ sơ quy hoạch demo của {$areaData['name']} gồm chỉ tiêu xây dựng, tầng cao, hệ số sử dụng đất, khoảng lùi và chú giải loại đất.",
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

    private function planningDetailForArea(array $areaData): array
    {
        $details = [
            'A' => [
                'zone_title' => 'Khu trung tâm tài chính',
                'symbol' => 'C1-Z1',
                'density' => '65%',
                'max_height' => '88',
                'land_use_ratio' => '12.5',
                'setback' => '6-10m',
                'description' => 'Quy hoạch chi tiết 1/2000 phục vụ phát triển kinh tế vùng.',
                'land_types' => ['Đất trung tâm thương mại', 'Đất ở cao tầng', 'Đất ở biệt thự thấp tầng', 'Hạ tầng kỹ thuật', 'Công viên cây xanh'],
            ],
            'P' => [
                'zone_title' => 'Khu nhà ở sinh thái',
                'symbol' => 'P2-E1',
                'density' => '48%',
                'max_height' => '32',
                'land_use_ratio' => '7.8',
                'setback' => '5-8m',
                'description' => 'Quy hoạch phân khu sinh thái, ưu tiên mặt nước, cây xanh và trục đi bộ nội khu.',
                'land_types' => ['Đất ở cao tầng', 'Đất nhà ở thấp tầng', 'Mặt nước cảnh quan', 'Công viên cây xanh'],
            ],
            'R' => [
                'zone_title' => 'Khu thương mại ven sông',
                'symbol' => 'R3-S2',
                'density' => '58%',
                'max_height' => '45',
                'land_use_ratio' => '9.2',
                'setback' => '4-7m',
                'description' => 'Quy hoạch trục shophouse ven sông kết hợp quảng trường, bến thuyền và phố đi bộ.',
                'land_types' => ['Đất thương mại dịch vụ', 'Đất hỗn hợp', 'Đất giao thông nội khu', 'Công viên ven sông'],
            ],
            'H' => [
                'zone_title' => 'Khu biệt thự đồi thông',
                'symbol' => 'H4-V1',
                'density' => '35%',
                'max_height' => '5',
                'land_use_ratio' => '1.6',
                'setback' => '8-12m',
                'description' => 'Quy hoạch biệt thự nghỉ dưỡng mật độ thấp, giữ lớp cây xanh và tầm nhìn đồi thông.',
                'land_types' => ['Đất biệt thự nghỉ dưỡng', 'Đất cây xanh cảnh quan', 'Đất dịch vụ cộng đồng', 'Đất hạ tầng kỹ thuật'],
            ],
            'M' => [
                'zone_title' => 'Khu căn hộ dịch vụ',
                'symbol' => 'M5-A3',
                'density' => '60%',
                'max_height' => '38',
                'land_use_ratio' => '10.4',
                'setback' => '5-9m',
                'description' => 'Quy hoạch cụm căn hộ dịch vụ, tối ưu khai thác lưu trú và tiện ích thương mại tầng đế.',
                'land_types' => ['Đất căn hộ dịch vụ', 'Đất thương mại tầng đế', 'Đất giao thông', 'Bãi đỗ xe', 'Cây xanh công cộng'],
            ],
            'C' => [
                'zone_title' => 'Khu nghỉ dưỡng biển',
                'symbol' => 'B6-R1',
                'density' => '42%',
                'max_height' => '18',
                'land_use_ratio' => '4.5',
                'setback' => '10-15m',
                'description' => 'Quy hoạch tổ hợp nghỉ dưỡng biển, bảo toàn hành lang cảnh quan và không gian công cộng ven bờ.',
                'land_types' => ['Đất lưu trú nghỉ dưỡng', 'Đất công trình dịch vụ', 'Đất cây xanh ven biển', 'Đất giao thông nội bộ', 'Hạ tầng kỹ thuật'],
            ],
        ];

        return $details[$areaData['prefix']] ?? $details['A'];
    }

    private function ensurePlanningPdfForArea(array $areaData, array $detail): string
    {
        $directory = public_path('planning-pdfs');
        File::ensureDirectoryExists($directory);

        $fileName = Str::slug($areaData['planning_ref']) . '.pdf';
        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        File::put($path, $this->planningPdfContent($areaData, $detail));

        return rtrim(config('app.url'), '/') . '/planning-pdfs/' . $fileName;
    }

    private function planningPdfContent(array $areaData, array $detail): string
    {
        $projectName = $areaData['project']['name'];
        $lines = [
            'HO SO QUY HOACH DEMO',
            "Du an: {$projectName}",
            "Khu vuc: {$areaData['name']}",
            "Phan khu: {$detail['zone_title']}",
            "Ky hieu: {$detail['symbol']}",
            "Mat do XD: {$detail['density']}",
            "Tang cao toi da: {$detail['max_height']}",
            "He so SDD: {$detail['land_use_ratio']}",
            "Khoang lui: {$detail['setback']}",
            'Chu giai: ' . implode(', ', $detail['land_types']),
        ];
        $text = implode('\\n', array_map(fn (string $line) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line), $lines));
        $stream = "BT /F1 13 Tf 50 760 Td ({$text}) Tj ET";
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }

    private function resolveLocationComponents(string $location): array
    {
        $apiKey = config('services.goong.api_key') ?? env('GOONG_API_KEY');

        if ($apiKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->get('https://rsapi.goong.io/geocode', [
                        'address' => $location,
                        'api_key' => $apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $compound = $data['results'][0]['compound'] ?? null;
                    $geometry = $data['results'][0]['geometry'] ?? null;

                    if ($compound) {
                        $province = $compound['province'] ?? null;
                        $district = $compound['district'] ?? null;
                        $lat = $geometry['location']['lat'] ?? null;
                        $lng = $geometry['location']['lng'] ?? null;

                        if ($province && $district) {
                            return [
                                'city' => $province,
                                'district' => $district,
                                'latitude' => $lat,
                                'longitude' => $lng,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        // Hardcoded fallback mapping
        if (str_contains($location, 'Quảng Ninh')) {
            return [
                'city' => 'Quảng Ninh',
                'district' => 'Hạ Long',
                'latitude' => 20.9587,
                'longitude' => 107.0406,
            ];
        }
        if (str_contains($location, 'Gia Lâm') || str_contains($location, 'Hà Nội')) {
            return [
                'city' => 'Hà Nội',
                'district' => 'Gia Lâm',
                'latitude' => 21.0083,
                'longitude' => 105.9340,
            ];
        }
        if (str_contains($location, 'Thảo Điền') || str_contains($location, 'Hồ Chí Minh')) {
            return [
                'city' => 'Hồ Chí Minh',
                'district' => 'Quận 2',
                'latitude' => 10.8076,
                'longitude' => 106.7326,
            ];
        }
        if (str_contains($location, 'Đà Lạt') || str_contains($location, 'Lâm Đồng')) {
            return [
                'city' => 'Lâm Đồng',
                'district' => 'Đà Lạt',
                'latitude' => 11.9259,
                'longitude' => 108.4389,
            ];
        }
        if (str_contains($location, 'Thủ Đức')) {
            return [
                'city' => 'Hồ Chí Minh',
                'district' => 'Thủ Đức',
                'latitude' => 10.8027,
                'longitude' => 106.7427,
            ];
        }
        if (str_contains($location, 'Phan Thiết') || str_contains($location, 'Bình Thuận')) {
            return [
                'city' => 'Bình Thuận',
                'district' => 'Phan Thiết',
                'latitude' => 10.9454,
                'longitude' => 108.2879,
            ];
        }

        // Naive fallback
        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        $city = $parts[count($parts) - 1] ?? 'Khác';
        $district = $parts[count($parts) - 2] ?? null;

        return [
            'city' => $city,
            'district' => $district,
            'latitude' => null,
            'longitude' => null,
        ];
    }

    private function seedPlanningSubAreas(Carbon $now): void
    {
        $subAreas = [
            [
                'name' => 'Khu trung tâm tài chính',
                'color' => '#EF4444',
                'description' => 'Phân khu tài chính và thương mại cao tầng',
            ],
            [
                'name' => 'Khu nhà ở sinh thái',
                'color' => '#22C55E',
                'description' => 'Phân khu sinh thái sinh hoạt xanh',
            ],
            [
                'name' => 'Khu thương mại ven sông',
                'color' => '#3B82F6',
                'description' => 'Phân khu phố đi bộ và shophouse ven sông',
            ],
            [
                'name' => 'Khu biệt thự đồi thông',
                'color' => '#F97316',
                'description' => 'Phân khu nghỉ dưỡng biệt thự đồi thông mật độ thấp',
            ],
            [
                'name' => 'Khu căn hộ dịch vụ',
                'color' => '#8B5CF6',
                'description' => 'Phân khu tổ hợp căn hộ dịch vụ và khách sạn',
            ],
            [
                'name' => 'Khu nghỉ dưỡng biển',
                'color' => '#EC4899',
                'description' => 'Phân khu resort ven biển và dịch vụ du lịch',
            ],
        ];

        foreach ($subAreas as $sa) {
            $existingId = DB::table('planning_sub_areas')->where('name', $sa['name'])->value('id');
            DB::table('planning_sub_areas')->updateOrInsert(
                ['name' => $sa['name']],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'color' => $sa['color'],
                    'description' => $sa['description'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function projectBannerImages(string $seed, string $name = ''): array
    {
        $bannerCount = 3 + (abs(crc32($seed)) % 3);

        return array_map(
            fn (int $index) => $this->getPlaceholderUrl(1600, 900, "{$name} - Banner {$index}", "{$seed}-banner-{$index}"),
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

        $existingLots = DB::table('lots')
            ->where('area_id', $areaId)
            ->orderByRaw('COALESCE(coordinate_y, 999999), COALESCE(coordinate_x, 999999), created_at, id')
            ->get()
            ->values();

        foreach ($existingLots as $index => $lot) {
            DB::table('lots')
                ->where('id', $lot->id)
                ->update([
                    'code' => '__seed_tmp_' . $index . '_' . $lot->id,
                    'updated_at' => $now,
                ]);
        }

        for ($i = 1; $i <= $totalLots; $i++) {
            $code = $areaData['prefix'] . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $existing = $existingLots->get($i - 1);
            $column = ($i - 1) % 6;
            $row = intdiv($i - 1, 6);
            $status = $statuses[$i - 1] ?? LotStatus::AVAILABLE->value;
            $areaSize = 82 + (($i % 8) * 12.5);
            $unitPrice = 15000000 + (($i % 7) * 3000000);
            $price = (int) ($unitPrice * $areaSize);

            $payload = [
                'area_id' => $areaId,
                'code' => $code,
                'status' => $status,
                'image_url' => $this->getPlaceholderUrl(900, 600, "Lô {$code}", "{$areaData['prefix']}-lot-{$i}"),
                'images' => json_encode([
                    $this->getPlaceholderUrl(900, 600, "Lô {$code} - Góc 1", "{$areaData['prefix']}-lot-{$i}-a"),
                    $this->getPlaceholderUrl(900, 600, "Lô {$code} - Góc 2", "{$areaData['prefix']}-lot-{$i}-b"),
                ], JSON_UNESCAPED_SLASHES),
                'area_size' => $areaSize,
                'price' => $price,
                'unit_price' => $unitPrice,
                'frontage' => 5 + (($i % 5) * 0.5),
                'direction' => $areaData['direction'],
                'legal' => $i % 3 === 0 ? 'Sổ đỏ lâu dài' : 'Sổ hồng riêng',
                'description' => "Lô {$code} thuộc {$areaData['name']}, phù hợp tư vấn khách hàng demo trên mobile.",
                'planning_id' => null,
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

        $extraLots = $existingLots->slice($totalLots);
        foreach ($extraLots as $lot) {
            DB::table('lots')
                ->where('id', $lot->id)
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    private function backfillAreaGoogleMapsUrls(Carbon $now): void
    {
        $fallbackQueries = [
            'Hạ Long' => '20.949083,107.073706',
            'Hải Phòng' => '20.844911,106.688084',
            'Hà Nội' => '21.028511,105.804817',
            'Hồ Chí Minh' => '10.776889,106.700806',
            'Đà Lạt' => '11.940419,108.458313',
            'Phan Thiết' => '10.933210,108.102445',
            'Mũi Né' => '10.933333,108.283333',
            'Quảng Ninh' => '21.006382,107.292514',
        ];

        DB::table('areas')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('google_maps_url')
                    ->orWhere('google_maps_url', '');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'location'])
            ->each(function ($area) use ($fallbackQueries, $now) {
                $searchText = trim((string) ($area->location ?: $area->name));
                $query = $searchText;

                foreach ($fallbackQueries as $needle => $coordinate) {
                    if (str_contains(mb_strtolower($searchText), mb_strtolower($needle))) {
                        $query = $coordinate;
                        break;
                    }
                }

                DB::table('areas')->where('id', $area->id)->update([
                    'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query),
                    'updated_at' => $now,
                ]);
            });
    }

    private function syncBranchesFromAreas(Carbon $now): void
    {
        if (!DB::getSchemaBuilder()->hasTable('branches')) {
            return;
        }

        $branchNames = DB::table('areas')
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();

        foreach ($branchNames as $index => $name) {
            $payload = [
                'code' => $this->branchCodeFromName((string) $name),
                'area' => $name,
                'is_active' => true,
                'sort' => $index + 1,
                'updated_at' => $now,
            ];

            $existing = DB::table('branches')->where('name', $name)->first();

            if ($existing) {
                DB::table('branches')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('branches')->insert([
                ...$payload,
                'id' => (string) Str::uuid(),
                'name' => $name,
                'created_at' => $now,
            ]);
        }
    }

    private function branchCodeFromName(string $name): string
    {
        return match ($name) {
            'Hà Nội' => 'HN',
            'Hồ Chí Minh' => 'HCM',
            'Đà Nẵng' => 'DN',
            default => strtoupper(Str::slug($name, '_')),
        };
    }
    private function seedAreaAssignments(array $users, array $areas, Carbon $now): void
    {
        $areaList = array_values($areas);
        $assignmentPlan = [
            'employee@test.com' => $areaList,
            'employee2@test.com' => array_values(array_filter($areaList, fn ($area) => $area->name === 'Metro Square - Khu căn hộ dịch vụ')) ?: array_slice($areaList, 0, 1),
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
                        'assignable_id' => $users[$email]->id,
                        'assignable_type' => 'user',
                        'permissions' => json_encode(['view_project', 'view_area', 'view_lot', 'lock_lot', 'deposit_lot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
                    continue;
                }

                DB::table('area_assignments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $users[$email]->id,
                    'assignable_id' => $users[$email]->id,
                    'assignable_type' => 'user',
                    'permissions' => json_encode(['view_project', 'view_area', 'view_lot', 'lock_lot', 'deposit_lot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'area_id' => $area->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }
    }

    private function getPlaceholderUrl(int $width, int $height, string $text, string $seed): string
    {
        $colors = [
            '3b82f6', // blue
            '10b981', // green
            'f59e0b', // amber
            'ef4444', // red
            '8b5cf6', // purple
            'ec4899', // pink
        ];
        $colorIndex = abs(crc32($seed)) % count($colors);
        $bgColor = $colors[$colorIndex];
        return "https://placehold.co/{$width}x{$height}/{$bgColor}/ffffff.png?text=" . urlencode($text);
    }
}
