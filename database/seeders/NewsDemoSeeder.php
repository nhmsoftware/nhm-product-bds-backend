<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NewsDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password123';

    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();
            $authors = $this->resolveAuthors($now);

            $this->seedPublicNews($authors, $now);
            $this->seedInternalNews($authors, $now);

            $this->command?->info('Đã seed tin tức chung và tin tức nội bộ demo.');
        });
    }

    private function resolveAuthors(Carbon $now): array
    {
        return [
            'manager' => $this->findOrCreateAuthor(
                ['manager@test.com', 'maiducduong201@gmail.com'],
                [
                    'email' => 'manager@test.com',
                    'name' => 'Trần Thị Trưởng Phòng',
                    'staff_code' => 'TEST-MGR-001',
                    'phone' => '0900000002',
                    'role' => UserRole::MANAGER->value,
                    'department' => 'HN',
                    'job_position' => 'Trưởng phòng kinh doanh',
                    'area' => 'Hà Nội',
                ],
                $now
            ),
            'manager_hcm' => $this->findOrCreateAuthor(
                ['manager.hcm@test.com'],
                [
                    'email' => 'manager.hcm@test.com',
                    'name' => 'Nguyễn Thị Trưởng Nhóm HCM',
                    'staff_code' => 'TEST-MGR-002',
                    'phone' => '0900000007',
                    'role' => UserRole::MANAGER->value,
                    'department' => 'HCM',
                    'job_position' => 'Trưởng nhóm kinh doanh',
                    'area' => 'Hồ Chí Minh',
                ],
                $now
            ),
            'director' => $this->findOrCreateAuthor(
                ['director@test.com', 'director@gmail.com'],
                [
                    'email' => 'director@test.com',
                    'name' => 'Lê Văn Giám Đốc',
                    'staff_code' => 'TEST-DIR-001',
                    'phone' => '0900000003',
                    'role' => UserRole::DIRECTOR->value,
                    'department' => 'HN',
                    'job_position' => 'Giám đốc khu vực',
                    'area' => 'Hà Nội',
                ],
                $now
            ),
        ];
    }

    private function findOrCreateAuthor(array $preferredEmails, array $fallback, Carbon $now): object
    {
        $author = DB::table('users')
            ->whereIn('email', $preferredEmails)
            ->whereNull('deleted_at')
            ->orderByRaw('CASE email ' . collect($preferredEmails)
                ->map(fn (string $email, int $index) => "WHEN '" . str_replace("'", "''", $email) . "' THEN {$index}")
                ->implode(' ') . ' ELSE 999 END')
            ->first();

        if ($author) {
            return $author;
        }

        $id = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $id,
            'staff_code' => $fallback['staff_code'],
            'name' => $fallback['name'],
            'email' => $fallback['email'],
            'phone' => $fallback['phone'],
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => $fallback['role'],
            'department' => $fallback['department'],
            'job_position' => $fallback['job_position'],
            'area' => $fallback['area'],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        return DB::table('users')->where('id', $id)->first();
    }

    private function seedPublicNews(array $authors, Carbon $now): void
    {
        $items = [
            [
                'title' => 'Thị trường bất động sản ven đô tăng nhu cầu tìm kiếm nhà ở xanh',
                'summary' => 'Nhu cầu tìm kiếm các khu đô thị có hạ tầng đồng bộ, nhiều mảng xanh và kết nối thuận tiện tiếp tục tăng trong nhóm khách hàng gia đình trẻ.',
                'content' => "Các khu đô thị ven đô đang được quan tâm nhờ mức giá còn dư địa tăng trưởng và chất lượng sống ngày càng hoàn thiện.\n\nĐội ngũ tư vấn cần nhấn mạnh yếu tố pháp lý, tiến độ hạ tầng và tiện ích thực tế khi giới thiệu sản phẩm cho khách hàng.",
                'thumbnail' => 'https://picsum.photos/seed/news-market-green-city/1200/800',
                'category' => 'market',
                'author' => 'manager',
                'featured' => true,
                'sort' => 1,
                'published_at' => $now->copy()->subDays(1)->setTime(9, 15),
            ],
            [
                'title' => 'Ba điểm cần kiểm tra trước khi tư vấn sản phẩm đất nền cho khách hàng',
                'summary' => 'Pháp lý, quy hoạch và khả năng kết nối hạ tầng là ba nhóm thông tin cần được xác minh trước khi tư vấn sản phẩm đất nền.',
                'content' => "Khi tư vấn đất nền, nhân viên cần chuẩn bị hồ sơ pháp lý, bản đồ quy hoạch và thông tin hạ tầng kết nối xung quanh dự án.\n\nViệc chủ động kiểm tra thông tin giúp tăng độ tin cậy trong quá trình trao đổi và giảm rủi ro phát sinh sau giao dịch.",
                'thumbnail' => 'https://picsum.photos/seed/news-legal-land-checklist/1200/800',
                'category' => 'legal',
                'author' => 'director',
                'featured' => true,
                'sort' => 2,
                'published_at' => $now->copy()->subDays(2)->setTime(14, 30),
            ],
            [
                'title' => 'Xu hướng khách hàng ưu tiên dự án có không gian làm việc linh hoạt',
                'summary' => 'Các căn hộ và nhà phố có không gian làm việc tại nhà đang được khách hàng quan tâm nhiều hơn sau giai đoạn thay đổi thói quen sinh hoạt.',
                'content' => "Khách hàng hiện không chỉ hỏi về diện tích và giá bán mà còn quan tâm đến khả năng bố trí phòng làm việc, ánh sáng tự nhiên và tiện ích cộng đồng.\n\nĐây là nhóm thông tin đội kinh doanh nên chuẩn bị sẵn trong kịch bản tư vấn dự án.",
                'thumbnail' => 'https://picsum.photos/seed/news-flexible-home-office/1200/800',
                'category' => 'investment',
                'author' => 'manager',
                'featured' => true,
                'sort' => 3,
                'published_at' => $now->copy()->subDays(3)->setTime(10, 0),
            ],
            [
                'title' => 'Khởi công tuyến đường vành đai mở ra cơ hội đầu tư bất động sản mới',
                'summary' => 'Việc khởi công tuyến đường vành đai trọng điểm kết nối liên vùng đang tạo động lực mạnh mẽ cho thị trường bất động sản các khu vực lân cận.',
                'content' => "Tuyến đường mới dự kiến hoàn thành sau 2 năm sẽ rút ngắn thời gian di chuyển đáng kể. Đội ngũ kinh doanh cần cập nhật thông tin quy hoạch chi tiết để tư vấn chuẩn xác cho các nhà đầu tư đón đầu làn sóng hạ tầng này.",
                'thumbnail' => 'https://picsum.photos/seed/news-infrastructure-ringroad/1200/800',
                'category' => 'market',
                'author' => 'director',
                'featured' => true,
                'sort' => 4,
                'published_at' => $now->copy()->subDays(4)->setTime(8, 30),
            ],
        ];

        foreach ($items as $item) {
            $this->upsertNews($item, $authors[$item['author']], null, null, $now);
        }
    }

    private function seedInternalNews(array $authors, Carbon $now): void
    {
        $items = [
            [
                'title' => 'Lịch đào tạo kịch bản tư vấn dự án tháng này',
                'summary' => 'Phòng kinh doanh Hà Nội tổ chức buổi đào tạo ngắn về kịch bản tư vấn dự án và xử lý phản hồi phổ biến từ khách hàng.',
                'content' => "Phòng kinh doanh Hà Nội sẽ tổ chức buổi đào tạo vào 09:00 thứ Sáu tuần này tại phòng họp tầng 5.\n\nNội dung gồm cập nhật bảng hàng, cách trình bày pháp lý dự án và kịch bản xử lý các câu hỏi thường gặp. Nhân viên vui lòng chuẩn bị trước danh sách khách hàng đang theo dõi.",
                'thumbnail' => 'https://picsum.photos/seed/internal-training-hn/1200/800',
                'category' => 'internal',
                'author' => 'manager',
                'department' => 'HN',
                'area' => 'Hà Nội',
                'published_at' => $now->copy()->subHours(5),
            ],
            [
                'title' => 'Cập nhật quy trình ghi nhận khách tham quan dự án',
                'summary' => 'Từ tuần này, nhân viên cần bổ sung ảnh hiện trường và ghi chú nhu cầu khách hàng ngay sau khi hoàn tất dẫn khách tham quan.',
                'content' => "Để dữ liệu chăm sóc khách hàng được đồng bộ, đội kinh doanh cần cập nhật ảnh hiện trường, vị trí check-in và ghi chú nhu cầu khách ngay sau buổi tham quan.\n\nTrưởng nhóm sẽ rà soát dữ liệu cuối ngày để hỗ trợ các ca cần tư vấn tiếp theo.",
                'thumbnail' => 'https://picsum.photos/seed/internal-site-tour-process/1200/800',
                'category' => 'internal',
                'author' => 'director',
                'department' => null,
                'area' => 'Hà Nội',
                'published_at' => $now->copy()->subHours(9),
            ],
            [
                'title' => 'Nhắc lịch rà soát khách hàng tiềm năng khu vực Hồ Chí Minh',
                'summary' => 'Đội Hồ Chí Minh cần cập nhật trạng thái khách hàng tiềm năng trước 17:00 để trưởng nhóm tổng hợp báo cáo cuối ngày.',
                'content' => "Các bạn trong đội Hồ Chí Minh vui lòng rà soát lại nhóm khách hàng đang quan tâm sản phẩm căn hộ dịch vụ và nhà phố thương mại.\n\nMỗi khách hàng cần có trạng thái mới nhất, nhu cầu ngân sách và bước chăm sóc tiếp theo để tránh bỏ sót cơ hội giao dịch.",
                'thumbnail' => 'https://picsum.photos/seed/internal-hcm-leads/1200/800',
                'category' => 'internal',
                'author' => 'manager_hcm',
                'department' => 'HCM',
                'area' => 'Hồ Chí Minh',
                'published_at' => $now->copy()->subDay()->setTime(16, 45),
            ],
        ];

        foreach ($items as $item) {
            $this->upsertNews(
                $item,
                $authors[$item['author']],
                $item['department'],
                $item['area'],
                $now
            );
        }
    }

    private function upsertNews(array $item, object $author, ?string $department, ?string $area, Carbon $now): void
    {
        $slug = Str::slug($item['title']);
        $existing = DB::table('news')->where('slug', $slug)->first();
        $payload = [
            'title' => $item['title'],
            'slug' => $slug,
            'summary' => $item['summary'],
            'content' => $item['content'],
            'thumbnail' => $item['thumbnail'],
            'category' => $item['category'],
            'department' => $department,
            'area' => $area,
            'author_id' => $author->id,
            'is_published' => true,
            'is_featured' => (bool) ($item['featured'] ?? false),
            'sort' => (int) ($item['sort'] ?? 0),
            'likes_count' => 0,
            'published_at' => $item['published_at'],
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing) {
            DB::table('news')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('news')->insert([
            ...$payload,
            'id' => (string) Str::uuid(),
            'created_at' => $item['published_at'],
        ]);
    }
}
