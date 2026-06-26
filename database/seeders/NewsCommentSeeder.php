<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsCommentSeeder extends Seeder
{
    private const COMMENTS_PER_NEWS = 3;

    public function run(): void
    {
        $newsList = DB::table('news')
            ->whereNull('deleted_at')
            ->where('is_published', true)
            ->get();

        if ($newsList->isEmpty()) {
            $this->command?->info('Không có tin tức nào để tạo bình luận mẫu.');
            return;
        }

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereNotIn('role', [UserRole::BUYER->value, UserRole::SUPER_ADMIN->value])
            ->whereNotNull('job_position_id')
            ->get();

        if ($users->isEmpty()) {
            $this->command?->info('Không có user nào phù hợp để tạo bình luận mẫu.');
            return;
        }

        $now = now();
        $rows = [];

        foreach ($newsList as $news) {
            $existingCount = DB::table('news_comments')
                ->where('news_id', $news->id)
                ->count();

            if ($existingCount >= self::COMMENTS_PER_NEWS) {
                continue;
            }

            $remaining = self::COMMENTS_PER_NEWS - $existingCount;
            $shuffledUsers = $users->shuffle()->take($remaining);

            foreach ($shuffledUsers as $index => $user) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'news_id' => $news->id,
                    'user_id' => $user->id,
                    'content' => $this->pickContent($news, $index),
                    'created_at' => $now->subMinutes(rand(1, 5000)),
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('news_comments')->insert($rows);
        }

        $this->command?->info("Đã tạo " . count($rows) . " bình luận tin tức mẫu.");
    }

    private function pickContent(object $news, int $index): string
    {
        $isPublic = empty($news->department) && empty($news->branch_id);

        $publicTemplates = [
            'Bài viết rất hữu ích, cảm ơn đội ngũ đã chia sẻ thông tin thị trường.',
            'Tin tức này giúp ích nhiều cho quá trình tư vấn khách hàng. Mong được cập nhật thêm.',
            'Nội dung chi tiết và rõ ràng, phù hợp để gửi tham khảo cho khách hàng.',
            'Phân tích thị trường rất chuẩn, nên bổ sung thêm dữ liệu về khu vực lân cận.',
            'Thông tin pháp lý rất cần thiết, cảm ơn đã tổng hợp.',
            'Bài viết tốt, nên thêm so sánh giữa các phân khúc sản phẩm.',
        ];

        $internalTemplates = [
            'Đã nhận, sẽ triển khai theo lịch đào tạo và báo cáo lại kết quả.',
            'Cảm ơn giám đốc đã nhắc nhở, em sẽ cập nhật dữ liệu trong ngày.',
            'Nhóm HCM đã tiếp nhận, sẽ hoàn thành trước deadline.',
            'Thông tin này rất cần thiết cho buổi họp tuần này, em đã lưu lại.',
            'Sẽ rà soát lại danh sách khách hàng theo hướng dẫn và báo cáo cuối ngày.',
            'Nhận được, em sẽ chuẩn bị hồ sơ pháp lý và kịch bản tư vấn trước buổi họp.',
        ];

        $templates = $isPublic ? $publicTemplates : $internalTemplates;

        return $templates[$index % count($templates)];
    }
}
