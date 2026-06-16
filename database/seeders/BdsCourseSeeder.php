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
    private const VIDEO_URL = 'https://dswa1xdat8uez.cloudfront.net/27yvp%2Ffile%2F900a804230b16379a024523ea4672d84_dcc4514484c10342f3ce0ae9da0a529b.mp4?response-content-disposition=inline%3Bfilename%3D%22900a804230b16379a024523ea4672d84_dcc4514484c10342f3ce0ae9da0a529b.mp4%22%3B&response-content-type=video%2Fmp4&Expires=1781622438&Signature=DefG1B3fOLzAR7EnNKHyki3Hv2hgxmpxlxlFAystYBVJgb4hZXS69MZbZNpLf45LcXRtQHJRTS3~IzIxcqsmA-~0VJx6hkwJn2yiB1xnjosfq04TFKeCxRPlrzw23MKRNwIm~S~fLZcqE5GOwiNOJd4Rr5tRpA~CTTFPqZHnvcxGp8-Zx3HIiuFmxW6Ktcp~aDkfUcOmICeVixg6IV2Qzflj2ow8WMX~8SViPDgt-~sspxSCaMrIShfuwZ5QLFRiH2tWrmB1uW1arKr92fNrYnuBgpE08gpAEkSqNs0ri8C54h5BFk58vdR277WECzR6CJBe-N6u0SPoDcWbqMZxQQ__&Key-Pair-Id=APKAJT5WQLLEOADKLHBQ';

    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', ['employee@test.com', 'employee2@test.com'])
            ->get();

        if ($users->isEmpty()) {
            echo "Không tìm thấy demo nhân viên. Vui lòng chạy InventoryAreaSeeder trước.\n";
            return;
        }

        $this->clearExistingCourse();

        echo "Đang seed dữ liệu cho " . $users->count() . " nhân viên demo.\n";

        // ── 1. Tạo khóa học BDS Foundation ────────────────────────────────
        $course = Course::create([
            'title'          => 'Nền tảng Kinh doanh Bất động sản',
            'description'    => 'Hoàn thành tuần tự các bài giảng dưới đây để nắm vững quy trình bán hàng chuẩn. Bạn không thể bỏ qua bài học.',
            'thumbnail'      => 'https://cdn.example.com/courses/bds-foundation.jpg',
            'is_required'    => false,
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
            'video_url'        => self::VIDEO_URL,
            'duration_seconds' => 30,
            'order'            => 1,
            'is_active'        => true,
            'attachments'      => [],
        ]);

        $lesson2 = CourseLesson::create([
            'course_id'        => $course->id,
            'title'            => 'Quy trình Tư vấn & Chốt Sales',
            'content'          => '<p>Nội dung bài học 2: Quy trình tư vấn và chốt sales hiệu quả.</p>',
            'video_url'        => self::VIDEO_URL,
            'duration_seconds' => 30,
            'order'            => 2,
            'is_active'        => true,
            'attachments'      => [],
        ]);

        $lesson3 = CourseLesson::create([
            'course_id'        => $course->id,
            'title'            => 'Xử lý Từ chối & Chăm sóc Khách hàng',
            'content'          => '<p>Nội dung bài học 3: Kỹ năng xử lý từ chối và chăm sóc khách hàng sau bán.</p>',
            'video_url'        => self::VIDEO_URL,
            'duration_seconds' => 30,
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

        // ── 4. Tạo Enrollment cho demo nhân viên ───────────────────────────
        foreach ($users as $user) {
            $enrollment = CourseEnrollment::create([
                'user_id'          => $user->id,
                'course_id'        => $course->id,
                'status'           => CourseEnrollmentStatus::IN_PROGRESS,
                'progress_percent' => 0.00,
            ]);

            LessonProgress::create([
                'enrollment_id'        => $enrollment->id,
                'lesson_id'            => $lesson1->id,
                'is_completed'         => false,
                'current_watch_seconds' => 0,
            ]);
        }

        echo "Đã tạo Enrollment cho " . $users->count() . " nhân viên demo.\n";
        echo "Đã tạo tiến độ xem video cho bài học 1.\n";

        echo "\n✅ Seed dữ liệu BDS Course thành công!\n";
        echo "   - Khóa học: {$course->title}\n";
        echo "   - ID: {$course->id}\n";
        echo "   - 3 bài học tự chọn, bài 1 chưa xem, 2 bài sau mở khi hoàn thành bài trước\n";
    }

    private function clearExistingCourse(): void
    {
        $courses = Course::withTrashed()
            ->where('title', 'Nền tảng Kinh doanh Bất động sản')
            ->get();

        foreach ($courses as $course) {
            $lessonIds = CourseLesson::withTrashed()
                ->where('course_id', $course->id)
                ->pluck('id');
            $enrollmentIds = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->pluck('id');

            LessonProgress::query()->whereIn('enrollment_id', $enrollmentIds)->delete();
            CourseEnrollment::query()->where('course_id', $course->id)->delete();
            CourseQuiz::withTrashed()->whereIn('lesson_id', $lessonIds)->forceDelete();
            CourseLesson::withTrashed()->where('course_id', $course->id)->forceDelete();
            $course->forceDelete();
        }
    }
}
