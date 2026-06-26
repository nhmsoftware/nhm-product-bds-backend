<?php

declare(strict_types=1);

namespace App\Modules\Learning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseLessonRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseQuizRepositoryInterface;
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;

final class CertificateService extends BaseService
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepository,
        protected CourseEnrollmentRepositoryInterface $courseEnrollmentRepository,
        protected CourseLessonRepositoryInterface $lessonRepository,
        protected CourseQuizRepositoryInterface $quizRepository,
        protected AuthRepositoryInterface $authRepository,
        protected QuizAttemptRepositoryInterface $quizAttemptRepository,
    ) {
    }

    public function getCertificate(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            if ($course->has_certificate === false) {
                return ServiceReturn::error(
                    message: 'Khóa học không hỗ trợ chứng nhận.',
                    code: 403
                );
            }

            $enrollment = $course->enrollments->first();
            if ($enrollment === null || $enrollment->status !== CourseEnrollmentStatus::COMPLETED) {
                return ServiceReturn::error(
                    message: 'Bạn chưa hoàn thành khóa học.',
                    code: 403
                );
            }

            $score = 10.00;
            $lessons = $this->lessonRepository->getByCourseId($courseId);
            if ($lessons->isNotEmpty()) {
                $lastLesson = $lessons->sortByDesc('order')->first();
                if ($lastLesson !== null) {
                    $quizQuestions = $this->quizRepository->getByLessonId($lastLesson->id);
                    if ($quizQuestions->isNotEmpty()) {
                        $questionIds = $quizQuestions->pluck('id')->toArray();
                        $correctAttemptsCount = $this->quizAttemptRepository
                            ->countCorrectByUserAndQuizIds($userId, $questionIds);

                        $totalQuestionsCount = $quizQuestions->count();
                        $score = ($correctAttemptsCount / $totalQuestionsCount) * 10;
                    }
                }
            }

            $payload = [
                'course_title' => $course->title,
                'employee_name' => $user->name,
                'completed_at' => $enrollment->completed_at ? $enrollment->completed_at->toIso8601String() : now()->toIso8601String(),
                'score' => (float) number_format($score, 2),
                'certificate_code' => $enrollment->id,
            ];

            return $this->success(
                data: $payload,
                message: 'Tải dữ liệu chứng nhận thành công.'
            );
        });
    }

    public function downloadCertificate(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            $certResult = $this->getCertificate($courseId, $userId);
            if ($certResult->isError()) {
                return $certResult;
            }

            $data = $certResult->getData();

            $content = "---------------------------------------------------------\n"
                     . "             CHỨNG NHẬN HOÀN THÀNH KHÓA HỌC              \n"
                     . "---------------------------------------------------------\n\n"
                     . " Chứng nhận học viên:\n"
                     . " Họ và tên:      " . $data['employee_name'] . "\n"
                     . " Đã hoàn thành:  " . $data['course_title'] . "\n"
                     . " Ngày hoàn thành: " . date('Y-m-d H:i:s', strtotime($data['completed_at'])) . "\n"
                     . " Điểm đánh giá:   " . number_format($data['score'], 2) . " / 10.00\n"
                     . " Mã chứng nhận:   " . $data['certificate_code'] . "\n\n"
                     . "---------------------------------------------------------\n"
                     . "             BẤT ĐỘNG SẢN BDS-APP LMS SYSTEM            \n"
                     . "---------------------------------------------------------\n";

            $filename = 'Certificate_' . str_replace(' ', '_', $data['course_title']) . '_' . $data['certificate_code'] . '.txt';

            return $this->success(
                data: [
                    'content' => $content,
                    'filename' => $filename,
                ],
                message: 'Tạo file chứng nhận thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải chứng nhận.',
                code: 500
            );
        });
    }
}
