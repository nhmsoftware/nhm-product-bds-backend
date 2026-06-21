<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\User;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use App\Modules\Learning\Models\LessonProgress;
use App\Modules\Learning\Models\QuizAttempt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LearningPathDemoSeeder extends Seeder
{
    private const VIDEO_URL = 'https://dswa1xdat8uez.cloudfront.net/27yvp%2Ffile%2F900a804230b16379a024523ea4672d84_dcc4514484c10342f3ce0ae9da0a529b.mp4?response-content-disposition=inline%3Bfilename%3D%22900a804230b16379a024523ea4672d84_dcc4514484c10342f3ce0ae9da0a529b.mp4%22%3B&response-content-type=video%2Fmp4&Expires=1781622438&Signature=DefG1B3fOLzAR7EnNKHyki3Hv2hgxmpxlxlFAystYBVJgb4hZXS69MZbZNpLf45LcXRtQHJRTS3~IzIxcqsmA-~0VJx6hkwJn2yiB1xnjosfq04TFKeCxRPlrzw23MKRNwIm~S~fLZcqE5GOwiNOJd4Rr5tRpA~CTTFPqZHnvcxGp8-Zx3HIiuFmxW6Ktcp~aDkfUcOmICeVixg6IV2Qzflj2ow8WMX~8SViPDgt-~sspxSCaMrIShfuwZ5QLFRiH2tWrmB1uW1arKr92fNrYnuBgpE08gpAEkSqNs0ri8C54h5BFk58vdR277WECzR6CJBe-N6u0SPoDcWbqMZxQQ__&Key-Pair-Id=APKAJT5WQLLEOADKLHBQ';
    private const ATTACHMENT_DIR = 'storage/learning/attachments';

    /**
     * Seed lộ trình học local cho các tài khoản demo nhân viên.
     */
    public function run(): void
    {
        $this->clearExistingDemoCourses();
        $attachments = $this->ensureAttachmentFiles();

        $courses = [
            [
                'title' => 'Lộ trình hội nhập nhân viên kinh doanh BĐS',
                'description' => 'Khóa học bắt buộc giúp nhân viên nắm quy trình tư vấn, bảng hàng và quy tắc làm việc với khách hàng.',
                'thumbnail' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80',
                'is_required' => true,
                'department' => null,
                'job_position' => null,
                'order' => 1,
                'lessons' => [
                    [
                        'title' => 'Bài 1: Tổng quan hệ sinh thái kinh doanh',
                        'content' => '<p>Tổng quan sản phẩm, khách hàng mục tiêu và các điểm chạm trong quy trình bán hàng.</p>',
                        'attachments' => [$attachments['overview_pdf'], $attachments['consulting_docx']],
                    ],
                    [
                        'title' => 'Bài 2: Quy trình tư vấn khách hàng',
                        'content' => '<p>Các bước ghi nhận nhu cầu, giới thiệu sản phẩm và theo dõi lịch sử chăm sóc khách hàng.</p>',
                        'attachments' => [$attachments['consulting_pdf'], $attachments['customer_checklist_docx']],
                    ],
                    [
                        'title' => 'Bài 3: Quy định cập nhật bảng hàng',
                        'content' => '<p>Nguyên tắc kiểm tra trạng thái lô đất, giữ chỗ và phối hợp với bộ phận quản lý kho hàng.</p>',
                        'attachments' => [$attachments['inventory_pdf']],
                    ],
                ],
                'quiz' => [
                    ['question' => 'Nhân viên cần làm gì trước khi tư vấn một lô đất cho khách hàng?', 'options' => ['Kiểm tra trạng thái bảng hàng', 'Báo giá theo trí nhớ', 'Bỏ qua lịch sử khách hàng', 'Giữ chỗ không cần xác nhận'], 'correct_option' => 0],
                    ['question' => 'Lộ trình học bắt buộc yêu cầu nhân viên học theo nguyên tắc nào?', 'options' => ['Xem lần lượt và hoàn thành video', 'Bỏ qua bài chưa xem', 'Chỉ làm quiz cuối khóa', 'Tự đánh dấu hoàn thành'], 'correct_option' => 0],
                    ['type' => 'essay', 'question' => 'Mô tả ngắn gọn quy trình bạn sẽ thực hiện khi lần đầu tư vấn khách hàng mua bất động sản.', 'placeholder' => 'Nhập câu trả lời của bạn (tối thiểu 2–3 câu)...'],
                ],
            ],
            [
                'title' => 'Kỹ năng khai thác bảng hàng theo khu vực',
                'description' => 'Khóa học tự chọn tập trung vào cách đọc bảng hàng và phối hợp giữ chỗ trong khu vực phụ trách.',
                'thumbnail' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=80',
                'is_required' => false,
                'department' => null,
                'job_position' => null,
                'order' => 2,
                'lessons' => [
                    [
                        'title' => 'Bài 1: Đọc thông tin khu đất',
                        'content' => '<p>Cách đọc diện tích, hướng, giá bán, pháp lý và các thông tin cần xác minh trước khi tư vấn.</p>',
                        'attachments' => [$attachments['area_reading_pdf']],
                    ],
                    [
                        'title' => 'Bài 2: Phối hợp giữ chỗ và đặt cọc',
                        'content' => '<p>Luồng xử lý yêu cầu giữ chỗ, đặt cọc và cập nhật trạng thái với quản lý.</p>',
                        'attachments' => [$attachments['deposit_workflow_docx'], $attachments['inventory_pdf']],
                    ],
                ],
                'quiz' => [
                    ['question' => 'Thông tin nào cần kiểm tra trước khi tư vấn bảng hàng?', 'options' => ['Trạng thái lô và pháp lý', 'Màu giao diện app', 'Tên file ảnh', 'Số lượt thích tin tức'], 'correct_option' => 0],
                ],
            ],
            [
                'title' => 'Kỹ năng chăm sóc khách hàng sau tư vấn',
                'description' => 'Khóa học tự chọn giúp nhân viên xây dựng lịch chăm sóc, phân nhóm nhu cầu và tăng tỷ lệ quay lại của khách hàng.',
                'thumbnail' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=80',
                'is_required' => false,
                'department' => null,
                'job_position' => null,
                'order' => 3,
                'lessons' => [
                    [
                        'title' => 'Bài 1: Thiết lập lịch chăm sóc khách hàng',
                        'content' => '<p>Cách đặt nhắc việc, phân loại mức độ quan tâm và chuẩn bị nội dung chăm sóc sau buổi tư vấn.</p>',
                        'attachments' => [$attachments['customer_care_pdf']],
                    ],
                    [
                        'title' => 'Bài 2: Kịch bản chăm sóc sau site tour',
                        'content' => '<p>Mẫu kịch bản gọi lại khách hàng, xử lý băn khoăn và chốt bước tiếp theo sau khi đi xem dự án.</p>',
                        'attachments' => [$attachments['follow_up_docx']],
                    ],
                ],
                'quiz' => [
                    ['question' => 'Sau buổi tư vấn, nhân viên nên ưu tiên việc gì?', 'options' => ['Ghi nhận nhu cầu và đặt lịch chăm sóc', 'Xóa thông tin khách hàng', 'Chỉ gửi bảng giá chung', 'Không cần cập nhật CRM'], 'correct_option' => 0],
                    ['question' => 'Mục tiêu chính của chăm sóc sau site tour là gì?', 'options' => ['Xác định bước tiếp theo với khách hàng', 'Đổi mã lô ngẫu nhiên', 'Tăng số thông báo hệ thống', 'Bỏ qua phản hồi'], 'correct_option' => 0],
                ],
            ],
            [
                'title' => 'Phân tích nhu cầu đầu tư bất động sản',
                'description' => 'Khóa học tự chọn về cách đọc khẩu vị đầu tư, dòng tiền kỳ vọng và mức độ phù hợp của từng nhóm sản phẩm.',
                'thumbnail' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80',
                'is_required' => false,
                'department' => null,
                'job_position' => null,
                'order' => 4,
                'lessons' => [
                    [
                        'title' => 'Bài 1: Nhận diện khẩu vị đầu tư',
                        'content' => '<p>Các câu hỏi cần dùng để xác định mục tiêu lợi nhuận, thời gian nắm giữ và mức chấp nhận rủi ro.</p>',
                        'attachments' => [$attachments['investment_needs_pdf']],
                    ],
                    [
                        'title' => 'Bài 2: Gợi ý sản phẩm theo dòng tiền',
                        'content' => '<p>Cách đối chiếu ngân sách, tiến độ thanh toán và kỳ vọng khai thác để chọn nhóm sản phẩm phù hợp.</p>',
                        'attachments' => [$attachments['cashflow_docx']],
                    ],
                ],
                'quiz' => [
                    ['question' => 'Yếu tố nào cần hỏi khi phân tích nhu cầu đầu tư?', 'options' => ['Mục tiêu lợi nhuận và thời gian nắm giữ', 'Màu yêu thích của nút bấm', 'Số lượng tab trên app', 'Tên file video'], 'correct_option' => 0],
                    ['question' => 'Khi tư vấn sản phẩm theo dòng tiền, nhân viên cần đối chiếu điều gì?', 'options' => ['Ngân sách và tiến độ thanh toán', 'Ảnh đại diện nội bộ', 'Số bình luận tin tức', 'Màu bản đồ'], 'correct_option' => 0],
                ],
            ],
        ];

        $createdCourses = [];
        foreach ($courses as $courseData) {
            $createdCourses[] = $this->createCourse($courseData);
        }

        $users = User::query()
            ->whereIn('email', ['employee@test.com', 'employee2@test.com'])
            ->get();

        foreach ($users as $user) {
            foreach ($createdCourses as $course) {
                $this->enrollUser($user, $course, false);
            }
        }

        echo "Đã seed lộ trình học: " . count($createdCourses) . " khóa học, " . $users->count() . " nhân viên.\n";
    }

    private function clearExistingDemoCourses(): void
    {
        $oldPrefix = '[' . 'DEMO' . '] ';

        $courses = Course::withTrashed()
            ->whereIn('title', [
                $oldPrefix . 'Lộ trình hội nhập nhân viên kinh doanh BĐS',
                $oldPrefix . 'Kỹ năng khai thác bảng hàng theo khu vực',
                $oldPrefix . 'Kỹ năng chăm sóc khách hàng sau tư vấn',
                $oldPrefix . 'Phân tích nhu cầu đầu tư bất động sản',
                'Lộ trình hội nhập nhân viên kinh doanh BĐS',
                'Kỹ năng khai thác bảng hàng theo khu vực',
                'Kỹ năng chăm sóc khách hàng sau tư vấn',
                'Phân tích nhu cầu đầu tư bất động sản',
            ])
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

    private function createCourse(array $courseData): Course
    {
        $lessons = $courseData['is_required'] ? array_slice($courseData['lessons'], 0, 1) : $courseData['lessons'];
        $quizzes = $courseData['quiz'];
        unset($courseData['lessons'], $courseData['quiz']);

        $course = Course::create([
            ...$courseData,
            'is_active' => true,
            'has_certificate' => true,
        ]);

        $lastLesson = null;
        foreach ($lessons as $index => $lessonData) {
            $lastLesson = CourseLesson::create([
                'course_id' => $course->id,
                'title' => $lessonData['title'],
                'content' => $lessonData['content'],
                'video_url' => self::VIDEO_URL,
                'duration_seconds' => 30,
                'order' => $index + 1,
                'is_active' => true,
                'attachments' => $lessonData['attachments'] ?? [],
            ]);
        }

        foreach ($quizzes as $index => $quizData) {
            $type = $quizData['type'] ?? 'multiple_choice';
            CourseQuiz::create([
                'lesson_id'      => $lastLesson->id,
                'type'           => $type,
                'order'          => $index + 1,
                'title'          => 'Câu ' . ($index + 1),
                'question'       => $quizData['question'],
                'options'        => $type === 'essay' ? [] : array_map(
                    fn (string $label, int $value) => ['value' => $value, 'label' => $label],
                    $quizData['options'],
                    array_keys($quizData['options'])
                ),
                'placeholder'    => $quizData['placeholder'] ?? null,
                'correct_option' => $type === 'essay' ? null : $quizData['correct_option'],
            ]);
        }

        return $course;
    }

    private function ensureAttachmentFiles(): array
    {
        $directory = public_path(self::ATTACHMENT_DIR);
        File::ensureDirectoryExists($directory);

        return [
            'overview_pdf' => $this->staticAttachment('tong-quan-he-sinh-thai-kinh-doanh.pdf', 'Tổng quan hệ sinh thái kinh doanh BĐS.pdf', 'pdf', 'application/pdf'),
            'consulting_pdf' => $this->staticAttachment('quy-trinh-tu-van-khach-hang.pdf', 'Quy trình tư vấn khách hàng.pdf', 'pdf', 'application/pdf'),
            'inventory_pdf' => $this->staticAttachment('quy-dinh-cap-nhat-bang-hang.pdf', 'Quy định cập nhật bảng hàng.pdf', 'pdf', 'application/pdf'),
            'area_reading_pdf' => $this->staticAttachment('huong-dan-doc-thong-tin-khu-dat.pdf', 'Hướng dẫn đọc thông tin khu đất.pdf', 'pdf', 'application/pdf'),
            'customer_care_pdf' => $this->staticAttachment('lich-cham-soc-khach-hang.pdf', 'Lịch chăm sóc khách hàng sau tư vấn.pdf', 'pdf', 'application/pdf'),
            'investment_needs_pdf' => $this->staticAttachment('phan-tich-nhu-cau-dau-tu.pdf', 'Phân tích nhu cầu đầu tư bất động sản.pdf', 'pdf', 'application/pdf'),
            'consulting_docx' => $this->staticAttachment('mau-kich-ban-tu-van.docx', 'Mẫu kịch bản tư vấn.docx', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'follow_up_docx' => $this->staticAttachment('kich-ban-cham-soc-sau-site-tour.docx', 'Kịch bản chăm sóc sau site tour.docx', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'cashflow_docx' => $this->staticAttachment('goi-y-san-pham-theo-dong-tien.docx', 'Gợi ý sản phẩm theo dòng tiền.docx', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'customer_checklist_docx' => $this->staticAttachment('checklist-gap-khach-hang.docx', 'Checklist gặp khách hàng.docx', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'deposit_workflow_docx' => $this->staticAttachment('quy-trinh-giu-cho-dat-coc.docx', 'Quy trình giữ chỗ và đặt cọc.docx', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
    }

    private function staticAttachment(string $fileName, string $name, string $type, string $mimeType): array
    {
        $source = database_path('seeders/assets/learning/attachments/' . $fileName);
        $target = public_path(self::ATTACHMENT_DIR . '/' . $fileName);

        if (! File::exists($source)) {
            throw new \RuntimeException("Thiếu file attachment demo học tập: {$source}");
        }

        if (! File::exists($target)) {
            File::copy($source, $target);
        }

        return $this->attachmentPayload($fileName, $name, $type, $mimeType);
    }

    private function attachmentPayload(string $fileName, string $name, string $type, string $mimeType): array
    {
        $relativePath = '/' . self::ATTACHMENT_DIR . '/' . $fileName;
        $fullPath = public_path(self::ATTACHMENT_DIR . '/' . $fileName);

        return [
            'type' => $type,
            'name' => $name,
            'url' => $relativePath,
            'mime_type' => $mimeType,
            'size' => $this->formatFileSize(filesize($fullPath) ?: 0),
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1) . ' MB';
        }

        return max(1, (int) ceil($bytes / 1024)) . ' KB';
    }

    private function enrollUser(User $user, Course $course, bool $completed): void
    {
        $lessons = CourseLesson::query()
            ->where('course_id', $course->id)
            ->orderBy('order')
            ->get();

        $enrollment = CourseEnrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => $completed ? CourseEnrollmentStatus::COMPLETED : CourseEnrollmentStatus::NOT_STARTED,
            'progress_percent' => $completed ? 100.00 : 0.00,
            'completed_at' => $completed ? now() : null,
        ]);

        if (!$completed || $lessons->isEmpty()) {
            return;
        }

        foreach ($lessons as $lesson) {
            LessonProgress::create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
                'is_completed' => true,
                'completed_at' => now(),
                'current_watch_seconds' => $lesson->duration_seconds ?? 30,
            ]);
        }

        $lastLesson = $lessons->last();
        $quizzes = CourseQuiz::query()->where('lesson_id', $lastLesson->id)->get();

        foreach ($quizzes as $quiz) {
            QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'selected_option' => $quiz->correct_option,
                'essay_answer' => null,
                'is_correct' => true,
                'is_draft' => false,
            ]);
        }
    }
}
