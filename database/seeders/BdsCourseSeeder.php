<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Auth\Models\User;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\LessonProgress;
use App\Modules\Learning\Models\QuizAttempt;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;

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

        // ── 1. Khóa học onboarding bắt buộc ────────────────────────────────
        $course = Course::create([
            'title'           => 'Nền tảng Kinh doanh Bất động sản',
            'description'     => 'Hoàn thành tuần tự các bài giảng dưới đây để nắm vững quy trình bán hàng chuẩn. Bạn không thể bỏ qua bài học.',
            'thumbnail'       => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?auto=format&fit=crop&w=1200&q=80',
            'is_required'     => true,
            'department'      => null,
            'job_position'    => null,
            'order'           => 1,
            'is_active'       => true,
            'has_certificate' => true,
        ]);

        echo "Đã tạo khóa học bắt buộc: {$course->title} (ID: {$course->id})\n";

        // ── 2. Tạo 3 bài học ────────────────────────────────────────────────
        $lesson1 = CourseLesson::create([
            'course_id'             => $course->id,
            'title'                 => 'Tổng quan thị trường BĐS Cao cấp',
            'content'               => '<p>Nội dung bài học 1: Tổng quan thị trường bất động sản cao cấp tại Việt Nam.</p>',
            'video_url'             => self::VIDEO_URL,
            'duration_seconds'      => 30,
            'order'                 => 1,
            'is_active'             => true,
            'attachments'           => [],
        ]);

        $lesson2 = CourseLesson::create([
            'course_id'             => $course->id,
            'title'                 => 'Quy trình Tư vấn & Chốt Sales',
            'content'               => '<p>Nội dung bài học 2: Quy trình tư vấn và chốt sales hiệu quả.</p>',
            'video_url'             => self::VIDEO_URL,
            'duration_seconds'      => 30,
            'order'                 => 2,
            'is_active'             => true,
            'attachments'           => [],
        ]);

        $lesson3 = CourseLesson::create([
            'course_id'             => $course->id,
            'title'                 => 'Xử lý Từ chối & Chăm sóc Khách hàng',
            'content'               => '<p>Nội dung bài học 3: Kỹ năng xử lý từ chối và chăm sóc khách hàng sau bán.</p>',
            'video_url'             => self::VIDEO_URL,
            'duration_seconds'      => 30,
            'order'                 => 3,
            'is_active'             => true,
            'attachments'           => [],
        ]);

        echo "Đã tạo 3 bài học.\n";

        // ── 3. Quiz cho bài 1: trắc nghiệm + tự luận ───────────────────────
        CourseQuiz::create([
            'lesson_id'      => $lesson1->id,
            'type'           => 'multiple_choice',
            'order'          => 1,
            'question'       => 'Thị trường BĐS cao cấp tập trung chủ yếu ở đâu tại Việt Nam?',
            'options'        => ['Hà Nội và TP.HCM', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng'],
            'correct_option' => 0,
        ]);

        CourseQuiz::create([
            'lesson_id'   => $lesson1->id,
            'type'        => 'essay',
            'order'       => 2,
            'question'    => 'Theo bạn, điều gì tạo nên giá trị cốt lõi của bất động sản cao cấp? Hãy trình bày ngắn gọn.',
            'options'     => [],
            'placeholder' => 'Nhập câu trả lời của bạn (tối thiểu 2–3 câu)...',
        ]);

        // ── 4. Quiz cho bài 2: trắc nghiệm + tự luận ───────────────────────
        CourseQuiz::create([
            'lesson_id'      => $lesson2->id,
            'type'           => 'multiple_choice',
            'order'          => 3,
            'question'       => 'Bước đầu tiên trong quy trình tư vấn BĐS là gì?',
            'options'        => ['Chốt hợp đồng', 'Tìm hiểu nhu cầu khách hàng', 'Giới thiệu sản phẩm', 'Báo giá'],
            'correct_option' => 1,
        ]);

        CourseQuiz::create([
            'lesson_id'   => $lesson2->id,
            'type'        => 'essay',
            'order'       => 4,
            'question'    => 'Mô tả một tình huống tư vấn khách hàng khó tính mà bạn đã từng gặp (hoặc giả định). Bạn đã hoặc sẽ xử lý như thế nào?',
            'options'     => [],
            'placeholder' => 'Nhập câu trả lời của bạn...',
        ]);

        // ── 5. Quiz cho bài 3: trắc nghiệm + tự luận ───────────────────────
        CourseQuiz::create([
            'lesson_id'      => $lesson3->id,
            'type'           => 'multiple_choice',
            'order'          => 5,
            'question'       => 'Khi khách hàng từ chối, điều đầu tiên cần làm là?',
            'options'        => ['Bỏ cuộc', 'Lắng nghe và thấu hiểu lý do', 'Giảm giá ngay', 'Mời khách hàng khác'],
            'correct_option' => 1,
        ]);

        CourseQuiz::create([
            'lesson_id'   => $lesson3->id,
            'type'        => 'essay',
            'order'       => 6,
            'question'    => 'Sau khi khách hàng đã ký hợp đồng, bạn sẽ thực hiện các bước chăm sóc hậu mãi nào để duy trì mối quan hệ lâu dài?',
            'options'     => [],
            'placeholder' => 'Nhập câu trả lời của bạn...',
        ]);

        // Lưu lại quiz IDs để tạo attempts
        $quizzes = CourseQuiz::whereIn('lesson_id', [
            $lesson1->id, $lesson2->id, $lesson3->id,
        ])->get()->groupBy('lesson_id');

        echo "Đã tạo quiz (3 câu trắc nghiệm + 3 câu tự luận) cho 3 bài học.\n";

        // ── 6. Enrollment + đã hoàn thành bài + chờ chấm tự luận ──────────
        $essayAnswers = [
            $lesson1->id => 'Theo tôi, giá trị cốt lõi của bất động sản cao cấp đến từ ba yếu tố chính: vị trí đắc địa, chất lượng xây dựng vượt trội và hệ sinh thái tiện ích đẳng cấp xung quanh. Khách hàng cao cấp không chỉ mua một nơi ở mà còn mua một phong cách sống và đẳng cấp xã hội.',
            $lesson2->id => 'Tôi từng tư vấn cho một khách hàng liên tục so sánh giá với các dự án khác và tỏ ra không hài lòng. Tôi đã lắng nghe toàn bộ mối lo của họ, sau đó phân tích cụ thể điểm khác biệt về pháp lý, tiến độ bàn giao và uy tín chủ đầu tư. Cuối cùng khách đồng ý ký hợp đồng sau buổi tư vấn thứ ba.',
            $lesson3->id => 'Sau khi khách ký hợp đồng, tôi thực hiện: (1) Gửi tin nhắn cảm ơn và xác nhận thông tin giao dịch trong 24h. (2) Nhắc lịch thanh toán định kỳ 3 ngày trước mỗi đợt. (3) Cập nhật tiến độ xây dựng hàng tháng qua Zalo. (4) Mời tham gia sự kiện tri ân khách hàng. Những việc này giúp duy trì niềm tin và tạo cơ hội giới thiệu khách mới.',
        ];

        foreach ($users as $user) {
            $enrollment = CourseEnrollment::create([
                'user_id'          => $user->id,
                'course_id'        => $course->id,
                'status'           => CourseEnrollmentStatus::IN_PROGRESS,
                'progress_percent' => 100.00,
            ]);

            // Tất cả bài học đã hoàn thành
            foreach ([$lesson1, $lesson2, $lesson3] as $lesson) {
                LessonProgress::create([
                    'enrollment_id'         => $enrollment->id,
                    'lesson_id'             => $lesson->id,
                    'is_completed'          => true,
                    'current_watch_seconds' => 30,
                ]);

                $lessonQuizzes = $quizzes->get($lesson->id, collect());

                foreach ($lessonQuizzes as $quiz) {
                    if ($quiz->type === 'multiple_choice') {
                        // MC đã trả lời đúng
                        QuizAttempt::create([
                            'user_id'         => $user->id,
                            'quiz_id'         => $quiz->id,
                            'selected_option' => $quiz->correct_option,
                            'is_correct'      => true,
                            'is_draft'        => false,
                        ]);
                    } else {
                        // Tự luận đã nộp, chờ chấm
                        QuizAttempt::create([
                            'user_id'      => $user->id,
                            'quiz_id'      => $quiz->id,
                            'essay_answer' => $essayAnswers[$lesson->id],
                            'is_correct'   => null,
                            'is_draft'     => false,
                        ]);
                    }
                }
            }
        }

        echo "Đã tạo enrollment + quiz attempts (MC đúng, tự luận chờ chấm) cho " . $users->count() . " nhân viên demo.\n";
        echo "\nSeed BDS Course thành công!\n";
        echo "   Khóa học: {$course->title} | is_required: true\n";
        echo "   Cấu trúc: 3 bài học × (1 trắc nghiệm + 1 tự luận)\n";
        echo "   Chờ chấm: " . ($users->count() * 3) . " câu tự luận\n";
    }

    private function clearExistingCourse(): void
    {
        $courses = Course::withTrashed()
            ->where('is_required', true)
            ->get();

        foreach ($courses as $course) {
            $lessonIds = CourseLesson::withTrashed()
                ->where('course_id', $course->id)
                ->pluck('id');

            $quizIds = CourseQuiz::withTrashed()
                ->whereIn('lesson_id', $lessonIds)
                ->pluck('id');

            $enrollmentIds = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->pluck('id');

            QuizAttempt::whereIn('quiz_id', $quizIds)->delete();
            LessonProgress::query()->whereIn('enrollment_id', $enrollmentIds)->delete();
            CourseEnrollment::query()->where('course_id', $course->id)->delete();
            CourseQuiz::withTrashed()->whereIn('lesson_id', $lessonIds)->forceDelete();
            CourseLesson::withTrashed()->where('course_id', $course->id)->forceDelete();
            $course->forceDelete();
        }
    }
}
