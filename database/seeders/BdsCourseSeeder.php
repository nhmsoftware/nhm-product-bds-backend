<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Auth\Models\User;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\LessonProgress;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Illuminate\Support\Str;

/**
 * Class BdsCourseSeeder
 *
 * Tạo dữ liệu mẫu chuẩn cho khóa học "Nền tảng Kinh doanh BĐS" với 3 bài học,
 * phục vụ việc test API /api/v1/learning/courses trả về đúng format JSON mới.
 */
class BdsCourseSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy user đầu tiên trong DB để gán dữ liệu
        $user = User::first();
        if (!$user) {
            echo "Không tìm thấy user nào trong hệ thống. Vui lòng tạo user trước.\n";
            return;
        }

        echo "Đang seed dữ liệu cho user: {$user->name} (ID: {$user->id})\n";

        // ── 1. Tạo khóa học BDS Foundation ────────────────────────────────
        $course = Course::create([
            'title'          => 'Nền tảng Kinh doanh Bất động sản',
            'description'    => 'Hoàn thành tuần tự các bài giảng dưới đây để nắm vững quy trình bán hàng chuẩn. Bạn không thể bỏ qua bài học.',
            'thumbnail'      => 'https://cdn.example.com/courses/bds-foundation.jpg',
            'is_required'    => true,
            'department'     => null,
            'job_position'   => null,
            'order'          => 1,
            'is_active'      => true,
            'has_certificate' => true,
        ]);

        echo "Đã tạo khóa học: {$course->title} (ID: {$course->id})\n";

        // ── 2. Tạo 3 bài học ───────────────────────────────────────────────
        $lesson1 = CourseLesson::create([
            'course_id'        => $course->id,
            'title'            => 'Tổng quan thị trường BĐS Cao cấp',
            'content'          => '<p>Nội dung bài học 1: Tổng quan thị trường bất động sản cao cấp tại Việt Nam.</p>',
            'video_url'        => 'https://cdn.example.com/videos/lesson-001.mp4',
            'duration_seconds' => 45, // 2700 giây
            'order'            => 1,
            'is_active'        => true,
            'attachments'      => [],
        ]);

        $lesson2 = CourseLesson::create([
            'course_id'        => $course->id,
            'title'            => 'Quy trình Tư vấn & Chốt Sales',
            'content'          => '<p>Nội dung bài học 2: Quy trình tư vấn và chốt sales hiệu quả.</p>',
            'video_url'        => 'https://cdn.example.com/videos/lesson-002.mp4',
            'duration_seconds' => 60, // 3600 giây
            'order'            => 2,
            'is_active'        => true,
            'attachments'      => [],
        ]);

        $lesson3 = CourseLesson::create([
            'course_id'        => $course->id,
            'title'            => 'Xử lý Từ chối & Chăm sóc Khách hàng',
            'content'          => '<p>Nội dung bài học 3: Kỹ năng xử lý từ chối và chăm sóc khách hàng sau bán.</p>',
            'video_url'        => 'https://cdn.example.com/videos/lesson-003.mp4',
            'duration_seconds' => 55, // 3300 giây
            'order'            => 3,
            'is_active'        => true,
            'attachments'      => [],
        ]);

        echo "Đã tạo 3 bài học cho khóa học.\n";

        // ── 3. Tạo Quiz cho mỗi bài học ────────────────────────────────────
        CourseQuiz::create([
            'lesson_id'      => $lesson1->id,
            'question'       => 'Thị trường BĐS cao cấp tập trung chủ yếu ở đâu tại Việt Nam?',
            'options'        => ['Hà Nội và TP.HCM', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng'],
            'correct_option' => 0,
        ]);

        CourseQuiz::create([
            'lesson_id'      => $lesson2->id,
            'question'       => 'Bước đầu tiên trong quy trình tư vấn BĐS là gì?',
            'options'        => ['Chốt hợp đồng', 'Tìm hiểu nhu cầu khách hàng', 'Giới thiệu sản phẩm', 'Báo giá'],
            'correct_option' => 1,
        ]);

        CourseQuiz::create([
            'lesson_id'      => $lesson3->id,
            'question'       => 'Khi khách hàng từ chối, điều đầu tiên cần làm là?',
            'options'        => ['Bỏ cuộc', 'Lắng nghe và thấu hiểu lý do', 'Giảm giá ngay', 'Mời khách hàng khác'],
            'correct_option' => 1,
        ]);

        echo "Đã tạo Quiz cho 3 bài học.\n";

        // ── 4. Tạo Enrollment cho user (đang học - lesson 1 đang xem 45%) ──
        $enrollment = CourseEnrollment::create([
            'user_id'          => $user->id,
            'course_id'        => $course->id,
            'status'           => CourseEnrollmentStatus::IN_PROGRESS,
            'progress_percent' => 15.00, // 0/3 bài hoàn thành nhưng đang xem bài 1
        ]);

        echo "Đã tạo Enrollment cho user (đang học).\n";

        // ── 5. Tạo LessonProgress: bài 1 đang xem được 45% (1215/2700 giây) ─
        LessonProgress::create([
            'enrollment_id'        => $enrollment->id,
            'lesson_id'            => $lesson1->id,
            'is_completed'         => false,
            'current_watch_seconds' => 1215, // ~45% của 2700 giây
        ]);

        echo "Đã tạo tiến độ xem video cho bài học 1.\n";

        echo "\n✅ Seed dữ liệu BDS Course thành công!\n";
        echo "   - Khóa học: {$course->title}\n";
        echo "   - ID: {$course->id}\n";
        echo "   - User: {$user->name}\n";
        echo "   - 3 bài học, 1 đang học (bài 1 - 45%), 2 bị khóa\n";
    }
}
