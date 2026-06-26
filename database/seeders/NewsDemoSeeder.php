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
                    'staff_code' => '0900000002',
                    'phone' => '0900000002',
                    'role' => UserRole::MANAGER->value,
                    'department' => 'Kinh doanh',
                    'job_position' => 'Trưởng phòng kinh doanh',
                    'area' => 'Hà Nội',
                ],
                $now
            ),
            'manager_hcm' => $this->findOrCreateAuthor(
                ['manager.hcm@test.com'],
                [
                    'email' => 'manager.hcm@test.com',
                    'name' => 'Nguyễn Thị Trưởng Phòng HCM',
                    'staff_code' => '0900000017',
                    'phone' => '0900000017',
                    'role' => UserRole::MANAGER->value,
                    'department' => 'Kinh doanh',
                    'job_position' => 'Trưởng phòng kinh doanh',
                    'area' => 'Hồ Chí Minh',
                ],
                $now
            ),
            'director' => $this->findOrCreateAuthor(
                ['director@test.com', 'director@gmail.com'],
                [
                    'email' => 'director@test.com',
                    'name' => 'Lê Văn Giám Đốc',
                    'staff_code' => '0900000003',
                    'phone' => '0900000003',
                    'role' => UserRole::DIRECTOR->value,
                    'department' => 'Kinh doanh',
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
        
        $branchId = null;
        if (!empty($fallback['area'])) {
            $branchId = DB::table('branches')->where('name', $fallback['area'])->value('id');
        }
        $deptId = null;
        if (!empty($fallback['department'])) {
            $deptId = DB::table('departments')->where('name', $fallback['department'])->value('id');
        }

        $jobPosId = null;
        if (!empty($fallback['job_position'])) {
            $existingPosId = DB::table('job_positions')->where('name', $fallback['job_position'])->value('id');
            if ($existingPosId) {
                $jobPosId = $existingPosId;
            } else {
                $jobPosId = (string) Str::uuid();
                DB::table('job_positions')->insert([
                    'id' => $jobPosId,
                    'name' => $fallback['job_position'],
                    'code' => strtoupper(Str::slug($fallback['job_position'], '_')),
                    'department_id' => $deptId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('users')->insert([
            'id' => $id,
            'staff_code' => $fallback['staff_code'],
            'name' => $fallback['name'],
            'email' => $fallback['email'],
            'phone' => $fallback['phone'],
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => $fallback['role'],
            'department_id' => $deptId,
            'job_position_id' => $jobPosId,
            'branch_id' => $branchId,
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
                'content_blocks' => [
                    ['type' => 'paragraph', 'text' => 'Các khu đô thị ven đô đang được quan tâm nhờ mức giá còn dư địa tăng trưởng và chất lượng sống ngày càng hoàn thiện. Nhóm khách hàng gia đình trẻ ưu tiên không gian xanh, trường học, dịch vụ y tế và khả năng di chuyển ổn định về trung tâm.'],
                    ['type' => 'heading', 'text' => 'Kết nối hạ tầng và giá trị gia tăng'],
                    ['type' => 'paragraph', 'text' => 'Những dự án nằm gần trục vành đai, tuyến metro hoặc các tuyến đường liên vùng thường có biên độ tăng giá tốt hơn trong trung hạn. Khi tư vấn, đội kinh doanh cần làm rõ tiến độ hạ tầng, pháp lý dự án và tiện ích thực tế đã vận hành.'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-market-green-city-1/1200/800', 'caption' => 'Không gian xanh và tiện ích nội khu là yếu tố nổi bật trong nhóm dự án ven đô.'],
                    ['type' => 'quote', 'text' => 'Không gian sống tốt không chỉ nằm ở vị trí, mà ở nhịp thở mỗi ngày.', 'author' => 'Giám đốc Kinh doanh Khởi Nguyên Land'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-market-green-city-2/1200/800', 'caption' => 'Hạ tầng kết nối giúp rút ngắn thời gian di chuyển và nâng giá trị khai thác.'],
                ],
                'attachments' => [
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-market-green-city-1/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'green-city-1.jpg'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-market-green-city-2/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'green-city-2.jpg'],
                ],
                'quote' => [
                    'text' => 'Không gian sống tốt không chỉ nằm ở vị trí, mà ở nhịp thở mỗi ngày.',
                    'author' => 'Giám đốc Kinh doanh Khởi Nguyên Land',
                ],
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
                'content_blocks' => [
                    ['type' => 'paragraph', 'text' => 'Khi tư vấn đất nền, nhân viên cần chuẩn bị hồ sơ pháp lý, bản đồ quy hoạch và thông tin hạ tầng kết nối xung quanh dự án trước khi trao đổi với khách hàng.'],
                    ['type' => 'heading', 'text' => 'Kết nối hạ tầng và giá trị gia tăng'],
                    ['type' => 'paragraph', 'text' => 'Thông tin về đường hiện hữu, đường quy hoạch, khoảng cách đến trung tâm hành chính, trường học, bệnh viện và khu công nghiệp giúp khách hàng nhìn rõ khả năng khai thác của sản phẩm. Đây là phần cần được trình bày bằng dữ liệu cụ thể thay vì chỉ dùng hình ảnh minh họa.'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-legal-land-checklist-1/1200/800', 'caption' => 'Bản đồ kết nối và các điểm tiện ích nên được kiểm tra trước buổi tư vấn.'],
                    ['type' => 'quote', 'text' => 'Pháp lý rõ ràng là lớp nền đầu tiên của một quyết định đầu tư vững vàng.', 'author' => 'Trưởng phòng Pháp chế'],
                ],
                'attachments' => [
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-legal-land-checklist-1/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'land-checklist-1.jpg'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-legal-land-checklist-2/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'land-checklist-2.jpg'],
                ],
                'quote' => [
                    'text' => 'Pháp lý rõ ràng là lớp nền đầu tiên của một quyết định đầu tư vững vàng.',
                    'author' => 'Trưởng phòng Pháp chế',
                ],
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
                'content_blocks' => [
                    ['type' => 'paragraph', 'text' => 'Khách hàng hiện không chỉ hỏi về diện tích và giá bán mà còn quan tâm đến khả năng bố trí phòng làm việc, ánh sáng tự nhiên và tiện ích cộng đồng.'],
                    ['type' => 'heading', 'text' => 'Kết nối hạ tầng và giá trị gia tăng'],
                    ['type' => 'paragraph', 'text' => 'Các dự án có kết nối nhanh đến trung tâm văn phòng, khu công nghệ, trục thương mại và không gian dịch vụ lân cận thường dễ thuyết phục nhóm khách hàng làm việc linh hoạt. Giá trị gia tăng đến từ cả công năng căn nhà lẫn hệ sinh thái xung quanh.'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-flexible-home-office-1/1200/800', 'caption' => 'Không gian làm việc riêng trong nhà trở thành điểm cộng khi tư vấn sản phẩm.'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-flexible-home-office-2/1200/800', 'caption' => 'Tiện ích cộng đồng hỗ trợ nhịp sống linh hoạt của cư dân.'],
                    ['type' => 'quote', 'text' => 'Ngôi nhà hiện đại phải làm được nhiều hơn là để ở.', 'author' => 'Giám đốc Tư vấn Sản phẩm'],
                ],
                'attachments' => [
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-flexible-home-office-1/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'home-office-1.jpg'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-flexible-home-office-2/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'home-office-2.jpg'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-flexible-home-office-3/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'home-office-3.jpg'],
                ],
                'quote' => [
                    'text' => 'Ngôi nhà hiện đại phải làm được nhiều hơn là để ở.',
                    'author' => 'Giám đốc Tư vấn Sản phẩm',
                ],
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
                'content_blocks' => [
                    ['type' => 'paragraph', 'text' => 'Tuyến đường mới dự kiến hoàn thành sau 2 năm sẽ rút ngắn thời gian di chuyển đáng kể và mở thêm trục giao thương cho các khu đô thị vệ tinh.'],
                    ['type' => 'heading', 'text' => 'Kết nối hạ tầng và giá trị gia tăng'],
                    ['type' => 'paragraph', 'text' => 'Nhà đầu tư thường quan tâm tới thời điểm hoàn thành, các nút giao chính, khả năng kết nối khu công nghiệp và quỹ đất dịch vụ quanh tuyến. Đây là những dữ liệu cần được admin cập nhật trực tiếp trong nội dung bài viết để mobile hiển thị chính xác.'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-infrastructure-ringroad-1/1200/800', 'caption' => 'Các nút giao mới có thể tạo lực đẩy cho sản phẩm nằm trong bán kính kết nối thuận tiện.'],
                    ['type' => 'quote', 'text' => 'Hạ tầng đi trước một nhịp, giá trị bất động sản thường đi sau bằng một bước nhảy.', 'author' => 'Giám đốc Khu vực'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-infrastructure-ringroad-2/1200/800', 'caption' => 'Quỹ đất ven trục hạ tầng cần được đánh giá cùng pháp lý và tiến độ thực tế.'],
                ],
                'attachments' => [
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-infrastructure-ringroad-1/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'ringroad-1.jpg'],
                    ['type' => 'image', 'url' => 'https://picsum.photos/seed/news-infrastructure-ringroad-2/1200/800', 'mime_type' => 'image/jpeg', 'name' => 'ringroad-2.jpg'],
                ],
                'quote' => [
                    'text' => 'Hạ tầng đi trước một nhịp, giá trị bất động sản thường đi sau bằng một bước nhảy.',
                    'author' => 'Giám đốc Khu vực',
                ],
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
                'department' => 'Kinh doanh',
                'area' => 'Hà Nội',
                'published_at' => $now->copy()->subHours(5),
            ],
            [
                'title' => 'Cập nhật quy trình ghi nhận khách tham quan dự án',
                'summary' => 'Từ tuần này, nhân viên cần bổ sung ảnh hiện trường và ghi chú nhu cầu khách hàng ngay sau khi hoàn tất dẫn khách tham quan.',
                'content' => "Để dữ liệu chăm sóc khách hàng được đồng bộ, đội kinh doanh cần cập nhật ảnh hiện trường, vị trí check-in và ghi chú nhu cầu khách ngay sau buổi tham quan.\n\nTrưởng phòng sẽ rà soát dữ liệu cuối ngày để hỗ trợ các ca cần tư vấn tiếp theo.",
                'thumbnail' => 'https://picsum.photos/seed/internal-site-tour-process/1200/800',
                'category' => 'internal',
                'author' => 'director',
                'department' => null,
                'area' => 'Hà Nội',
                'published_at' => $now->copy()->subHours(9),
            ],
            [
                'title' => 'Nhắc lịch rà soát khách hàng tiềm năng khu vực Hồ Chí Minh',
                'summary' => 'Đội Hồ Chí Minh cần cập nhật trạng thái khách hàng tiềm năng trước 17:00 để trưởng phòng tổng hợp báo cáo cuối ngày.',
                'content' => "Các bạn trong đội Hồ Chí Minh vui lòng rà soát lại nhóm khách hàng đang quan tâm sản phẩm căn hộ dịch vụ và nhà phố thương mại.\n\nMỗi khách hàng cần có trạng thái mới nhất, nhu cầu ngân sách và bước chăm sóc tiếp theo để tránh bỏ sót cơ hội giao dịch.",
                'thumbnail' => 'https://picsum.photos/seed/internal-hcm-leads/1200/800',
                'category' => 'internal',
                'author' => 'manager_hcm',
                'department' => 'Kinh doanh',
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
        $branchId = null;
        if (!empty($area)) {
            $branchId = DB::table('branches')->where('name', $area)->value('id');
        }

        $thumbnail = $this->replacePicsumUrl($item['thumbnail'] ?? '');
        
        $contentBlocks = $item['content_blocks'] ?? null;
        if (is_array($contentBlocks)) {
            foreach ($contentBlocks as &$block) {
                if (($block['type'] ?? '') === 'image' && !empty($block['url'])) {
                    $block['url'] = $this->replacePicsumUrl($block['url']);
                }
            }
        }
        
        $attachments = $item['attachments'] ?? null;
        if (is_array($attachments)) {
            foreach ($attachments as &$att) {
                if (($att['type'] ?? '') === 'image' && !empty($att['url'])) {
                    $att['url'] = $this->replacePicsumUrl($att['url']);
                }
            }
        }

        $payload = [
            'title' => $item['title'],
            'slug' => $slug,
            'summary' => $item['summary'],
            'content' => $item['content'],
            'content_blocks' => $this->jsonOrNull($contentBlocks),
            'thumbnail' => $thumbnail,
            'attachments' => $this->jsonOrNull($attachments),
            'quote' => $this->jsonOrNull($item['quote'] ?? null),
            'category' => $item['category'],
            'department' => $department,
            'branch_id' => $branchId,
            'author_id' => $author->id,
            'is_published' => true,
            'is_featured' => (bool) ($item['featured'] ?? false),
            'sort' => (int) ($item['sort'] ?? 1),
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
    private function jsonOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function replacePicsumUrl(string $url): string
    {
        if (preg_match('/picsum\.photos\/seed\/(.*?)\/(\d+)\/(\d+)/', $url, $matches)) {
            $seed = $matches[1];
            $width = $matches[2];
            $height = $matches[3];
            
            $text = str_replace('-', ' ', $seed);
            $text = ucwords($text);
            
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
        return $url;
    }
}
