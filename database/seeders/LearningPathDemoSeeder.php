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
use ZipArchive;

class LearningPathDemoSeeder extends Seeder
{
    private const VIDEO_URL = 'https://d1p1y5pyxk2k6i.cloudfront.net/46sqc%2Ffile%2F318fd3c7e66174be5a09944477b10d66_dcc4514484c10342f3ce0ae9da0a529b.mp4?response-content-disposition=inline%3Bfilename%3D%22318fd3c7e66174be5a09944477b10d66_dcc4514484c10342f3ce0ae9da0a529b.mp4%22%3B&response-content-type=video%2Fmp4&Expires=1781009192&Signature=CAFyy2AhLIGJF3KSst3PtulY6xNYtv8cETxa65O1YXwnZb3TgT2SuGUItHVDRWhwMTXkSfxw87Sq0CgKAeCAWK~6zUIEproTN8OPDcHD46ZaQwViVolIWxhrnpHQZzmGxmcaOnkTGMJq1W4smKTaWkdpgLKAWhrmRmob8814iY3aVJz~0oHFptScScDeIRSvg8AB5PwN3q3RZ-9Nzb-PQdUPnrXx2rhaaEtY1TEm4~4W9LQsUPDeMTIdAj5ltLEtAm-B2Op7qkTo4ags-t72GMaz4eDDTPuepbWgKGscoXBRmUXito4m7TG1g0sHTW1v3SfFghZNXzufqt52MpN8sw__&Key-Pair-Id=APKAJT5WQLLEOADKLHBQ';
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
            foreach ($createdCourses as $index => $course) {
                $this->enrollUser($user, $course, $index === 0 && $user->email === 'employee@test.com');
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
        $lessons = $courseData['lessons'];
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
            CourseQuiz::create([
                'lesson_id' => $lastLesson->id,
                'type' => 'multiple_choice',
                'order' => $index + 1,
                'title' => 'Câu ' . ($index + 1),
                'question' => $quizData['question'],
                'options' => array_map(
                    fn (string $label, int $value) => ['value' => $value, 'label' => $label],
                    $quizData['options'],
                    array_keys($quizData['options'])
                ),
                'placeholder' => null,
                'correct_option' => $quizData['correct_option'],
            ]);
        }

        return $course;
    }

    private function ensureAttachmentFiles(): array
    {
        $directory = public_path(self::ATTACHMENT_DIR);
        File::ensureDirectoryExists($directory);

        $files = [
            'overview_pdf' => $this->createPdfAttachment(
                'tong-quan-he-sinh-thai-kinh-doanh.pdf',
                'Tong quan he sinh thai kinh doanh BDS',
                ['San pham, khach hang muc tieu, quy trinh tu van va cac diem can ghi nhan trong CRM.']
            ),
            'consulting_pdf' => $this->createPdfAttachment(
                'quy-trinh-tu-van-khach-hang.pdf',
                'Quy trinh tu van khach hang',
                ['1. Ghi nhan nhu cau.', '2. Kiem tra bang hang.', '3. Tu van san pham phu hop.', '4. Cap nhat lich su cham soc.']
            ),
            'inventory_pdf' => $this->createPdfAttachment(
                'quy-dinh-cap-nhat-bang-hang.pdf',
                'Quy dinh cap nhat bang hang',
                ['Kiem tra trang thai lo, phap ly, gia ban va thong tin giu cho truoc khi tu van khach hang.']
            ),
            'area_reading_pdf' => $this->createPdfAttachment(
                'huong-dan-doc-thong-tin-khu-dat.pdf',
                'Huong dan doc thong tin khu dat',
                ['Doc dien tich, huong, don gia, tong gia, phap ly va tinh trang giao dich cua tung lo dat.']
            ),
            'customer_care_pdf' => $this->createPdfAttachment(
                'lich-cham-soc-khach-hang.pdf',
                'Lich cham soc khach hang sau tu van',
                ['Phan nhom nhu cau, dat lich goi lai va cap nhat trang thai cham soc sau moi diem cham.']
            ),
            'investment_needs_pdf' => $this->createPdfAttachment(
                'phan-tich-nhu-cau-dau-tu.pdf',
                'Phan tich nhu cau dau tu bat dong san',
                ['Xac dinh muc tieu loi nhuan, thoi gian nam giu, dong tien va muc chap nhan rui ro cua khach hang.']
            ),
            'consulting_docx' => $this->createDocxAttachment(
                'mau-kich-ban-tu-van.docx',
                'Mau kich ban tu van',
                ['Mo dau cuoc goi', 'Xac dinh nhu cau', 'Gioi thieu san pham', 'Chot lich gap hoac site tour']
            ),
            'follow_up_docx' => $this->createDocxAttachment(
                'kich-ban-cham-soc-sau-site-tour.docx',
                'Kich ban cham soc sau site tour',
                ['Cam on khach hang', 'Tong hop san pham da xem', 'Xu ly ban khoan', 'Hen buoc tiep theo']
            ),
            'cashflow_docx' => $this->createDocxAttachment(
                'goi-y-san-pham-theo-dong-tien.docx',
                'Goi y san pham theo dong tien',
                ['Ngan sach', 'Tien do thanh toan', 'Ky vong khai thac', 'Muc do phu hop san pham']
            ),
            'customer_checklist_docx' => $this->createDocxAttachment(
                'checklist-gap-khach-hang.docx',
                'Checklist gap khach hang',
                ['Thong tin khach hang', 'Nhu cau dau tu', 'Ngan sach', 'San pham quan tam', 'Buoc cham soc tiep theo']
            ),
            'deposit_workflow_docx' => $this->createDocxAttachment(
                'quy-trinh-giu-cho-dat-coc.docx',
                'Quy trinh giu cho va dat coc',
                ['Xac minh lo dat', 'Tao yeu cau giu cho', 'Cho phe duyet', 'Cap nhat trang thai dat coc']
            ),
        ];

        return $files;
    }

    private function createPdfAttachment(string $fileName, string $title, array $lines): array
    {
        $path = public_path(self::ATTACHMENT_DIR . '/' . $fileName);
        $contentLines = array_merge([$title, ''], $lines);
        $streamLines = [];
        $y = 760;
        foreach ($contentLines as $line) {
            $streamLines[] = sprintf('BT /F1 13 Tf 50 %d Td (%s) Tj ET', $y, $this->escapePdfText($line));
            $y -= 24;
        }
        $stream = implode("\n", $streamLines);

        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            '5 0 obj << /Length ' . strlen($stream) . " >> stream\n" . $stream . "\nendstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        file_put_contents($path, $pdf);

        return $this->attachmentPayload($fileName, $title . '.pdf', 'pdf', 'application/pdf');
    }

    private function createDocxAttachment(string $fileName, string $title, array $lines): array
    {
        $path = public_path(self::ATTACHMENT_DIR . '/' . $fileName);
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', $this->docxDocumentXml($title, $lines));
        $zip->close();

        return $this->attachmentPayload($fileName, $title . '.docx', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    private function docxDocumentXml(string $title, array $lines): string
    {
        $paragraphs = array_map(
            fn (string $line) => '<w:p><w:r><w:t>' . htmlspecialchars($line, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r></w:p>',
            array_merge([$title, ''], $lines)
        );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . implode('', $paragraphs) . '<w:sectPr/></w:body></w:document>';
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

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
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
