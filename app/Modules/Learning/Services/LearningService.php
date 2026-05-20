<?php

namespace App\Modules\Learning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Learning\DTO\ViewCoursesDTO;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Interfaces\LearningServiceInterface;

/**
 * Class LearningService
 *
 * Linh hồn nghiệp vụ (Business Logic) liên quan đến quản lý học tập của nhân viên.
 *
 * @package App\Modules\Learning\Services
 */
final class LearningService extends BaseService implements LearningServiceInterface
{
    /**
     * @var CourseRepositoryInterface
     */
    protected CourseRepositoryInterface $courseRepository;

    /**
     * @var CourseEnrollmentRepositoryInterface
     */
    protected CourseEnrollmentRepositoryInterface $courseEnrollmentRepository;

    /**
     * Khởi tạo Service với các Repository tương ứng.
     *
     * @param CourseRepositoryInterface $courseRepository
     * @param CourseEnrollmentRepositoryInterface $courseEnrollmentRepository
     */
    public function __construct(
        CourseRepositoryInterface $courseRepository,
        CourseEnrollmentRepositoryInterface $courseEnrollmentRepository
    ) {
        $this->courseRepository = $courseRepository;
        $this->courseEnrollmentRepository = $courseEnrollmentRepository;
    }

    /**
     * Tải danh sách khóa học bắt buộc được phân bổ cho Employee (UC-053).
     *
     * @param ViewCoursesDTO $dto DTO chứa thông tin phòng ban, vị trí công việc
     * @return ServiceReturn Chứa danh sách khóa học và tiến độ hoàn thành
     */
    public function getMandatoryCourses(ViewCoursesDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động (không bị khóa)
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tải danh sách khóa học bắt buộc
            $courses = $this->courseRepository->getMandatoryCourses(
                $dto->userId,
                $dto->department,
                $dto->jobPosition
            );

            // A1 – Không có khóa học bắt buộc
            if ($courses->isEmpty()) {
                return $this->success(
                    data: [],
                    message: 'Hiện chưa có khóa học bắt buộc.'
                );
            }

            // 4. Chuẩn hóa dữ liệu theo đặc tả
            $result = $courses->map(function ($course) {
                // Lấy enrollment tương ứng (đã được load eager via relation)
                $enrollment = $course->enrollments->first();

                return [
                    'id' => (string) $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->thumbnail,
                    'description' => $course->description,
                    'progress_percent' => $enrollment ? (float) $enrollment->progress_percent : 0.00,
                    'status' => $enrollment ? $enrollment->status : 'not_started',
                    'status_label' => $enrollment 
                        ? ($enrollment->status === 'completed' ? 'Hoàn thành' : 'Đang học') 
                        : 'Chưa học',
                ];
            });

            return $this->success(
                data: $result->toArray(),
                message: 'Tải danh sách khóa học bắt buộc thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2 – Lỗi tải khóa học
            return ServiceReturn::error(
                message: 'Không thể tải danh sách khóa học.',
                code: 500
            );
        });
    }

    /**
     * Lấy thông tin chi tiết một khóa học kèm tiến độ các bài học của Employee (UC-053).
     *
     * @param string $courseId ID khóa học cần xem
     * @param string $userId ID của nhân viên đang đăng nhập
     * @return ServiceReturn Chứa thông tin chi tiết khóa học, các bài học, thời lượng video và trạng thái từng bài học
     */
    public function getCourseDetails(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tải chi tiết khóa học và các bài học liên quan
            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 4. Nếu chưa đăng ký học (enrollment chưa tồn tại), tiến hành tự động đăng ký
            $enrollment = $course->enrollments->first();
            if (!$enrollment) {
                $enrollment = $this->courseEnrollmentRepository->create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => 'in_progress',
                    'progress_percent' => 0.00,
                ]);
            }

            // 5. Tải danh sách bài học đã hoàn thành
            $completedLessons = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('is_completed', true)
                ->pluck('lesson_id')
                ->toArray();

            // 6. Tính toán trạng thái cho từng bài học (Đang học, Hoàn thành, Khóa)
            $lessons = $course->lessons;
            $mappedLessons = [];
            $hasUncompletedPreceding = false;

            foreach ($lessons as $lesson) {
                $isCompleted = in_array($lesson->id, $completedLessons);

                if ($isCompleted) {
                    $status = 'completed';
                    $statusLabel = 'Hoàn thành';
                } else {
                    // Bài học chưa hoàn thành đầu tiên sẽ ở trạng thái "Đang học"
                    if (!$hasUncompletedPreceding) {
                        $status = 'learning';
                        $statusLabel = 'Đang học';
                        $hasUncompletedPreceding = true; // Đánh dấu đã gặp bài học chưa hoàn thành
                    } else {
                        // Các bài học chưa hoàn thành tiếp theo sẽ bị "Khóa"
                        $status = 'locked';
                        $statusLabel = 'Khóa';
                    }
                }

                $mappedLessons[] = [
                    'id' => (string) $lesson->id,
                    'title' => $lesson->title,
                    'content' => $lesson->content,
                    'video_url' => $lesson->video_url,
                    'duration_minutes' => $lesson->duration_minutes,
                    'order' => $lesson->order,
                    'status' => $status,
                    'status_label' => $statusLabel,
                ];
            }

            // 7. Chuẩn hóa cấu trúc trả về
            $courseDetails = [
                'id' => (string) $course->id,
                'title' => $course->title,
                'thumbnail' => $course->thumbnail,
                'description' => $course->description,
                'progress_percent' => (float) $enrollment->progress_percent,
                'status' => $enrollment->status,
                'lessons' => $mappedLessons,
            ];

            return $this->success(
                data: $courseDetails,
                message: 'Tải thông tin khóa học thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải chi tiết khóa học. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Tải thông tin chi tiết của một bài học trong khóa học của Employee (UC-054).
     *
     * @param string $lessonId
     * @param string $userId
     * @return ServiceReturn
     */
    public function getLessonDetails(string $lessonId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. A3 – Không tìm thấy bài học
            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            // 4. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 5. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            // 6. Tải danh sách bài học đã hoàn thành và tiến độ xem video bài học hiện tại
            $progressRecord = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->first();
            $currentWatchSeconds = $progressRecord ? $progressRecord->current_watch_seconds : 0;

            $completedLessons = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('is_completed', true)
                ->pluck('lesson_id')
                ->toArray();

            // 7. Tính toán trạng thái các bài học để xác định trạng thái của bài học hiện tại
            $lessons = $course->lessons;
            $targetStatus = 'locked';
            $targetStatusLabel = 'Khóa';
            $hasUncompletedPreceding = false;
            $nextLesson = null;
            $foundCurrent = false;

            foreach ($lessons as $item) {
                $isCompleted = in_array($item->id, $completedLessons);
                $status = 'locked';
                $statusLabel = 'Khóa';

                if ($isCompleted) {
                    $status = 'completed';
                    $statusLabel = 'Hoàn thành';
                } else {
                    if (!$hasUncompletedPreceding) {
                        $status = 'learning';
                        $statusLabel = 'Đang học';
                        $hasUncompletedPreceding = true;
                    }
                }

                if ($item->id === $lesson->id) {
                    $targetStatus = $status;
                    $targetStatusLabel = $statusLabel;
                    $foundCurrent = true;
                    continue;
                }

                // Nếu đã tìm thấy bài học hiện tại, bài tiếp theo đầu tiên sẽ là $nextLesson
                if ($foundCurrent && $nextLesson === null) {
                    $nextLesson = $item;
                }
            }

            // 8. A1 – Bài học chưa được mở khóa
            $this->validate($targetStatus !== 'locked', 'Vui lòng hoàn thành bài học trước để mở khóa.', 403);

            // 9. Xác định thông báo điều kiện mở khóa bài tiếp theo nếu có
            if ($nextLesson !== null) {
                $unlockCondition = 'Hoàn thành bài học này để mở khóa bài tiếp theo: ' . $nextLesson->title;
            } else {
                $unlockCondition = 'Hoàn thành bài học này để hoàn thành khóa học.';
            }

            // 10. A2 – Xử lý tài liệu đính kèm
            $attachments = $lesson->attachments ?? [];

            $result = [
                'id' => (string) $lesson->id,
                'course_id' => (string) $lesson->course_id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'video_url' => $lesson->video_url,
                'duration_minutes' => $lesson->duration_minutes,
                'order' => $lesson->order,
                'status' => $targetStatus,
                'status_label' => $targetStatusLabel,
                'attachments' => $attachments,
                'current_watch_seconds' => $currentWatchSeconds,
                'unlock_condition' => $unlockCondition,
            ];

            return $this->success(
                data: $result,
                message: 'Tải thông tin bài học thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A4 – Lỗi tải dữ liệu bài học
            return ServiceReturn::error(
                message: 'Không thể tải thông tin bài học.',
                code: 500
            );
        });
    }

    /**
     * Cập nhật tiến độ xem video của bài học và tự động đánh giá hoàn thành (UC-055).
     *
     * @param string $lessonId
     * @param int $watchTimeSeconds
     * @param string $userId
     * @return ServiceReturn
     */
    public function updateLessonProgress(string $lessonId, int $watchTimeSeconds, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $watchTimeSeconds, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin bài học
            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            // 4. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 5. A1 – Video không khả dụng
            $this->validate(!empty($lesson->video_url), 'Video hiện không khả dụng.', 400);

            // 6. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            // 7. Tạo hoặc lấy bản ghi tiến độ học tập hiện tại
            $progressRecord = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if (!$progressRecord) {
                $progressRecord = new \App\Modules\Learning\Models\LessonProgress([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => false,
                    'current_watch_seconds' => 0,
                ]);
            }

            // A5 – Lưu tiến độ xem hiện tại của Employee
            $progressRecord->current_watch_seconds = max($progressRecord->current_watch_seconds, $watchTimeSeconds);

            // 8. Đánh giá hoàn thành bài học
            // Thời lượng yêu cầu tính bằng giây (duration_minutes * 60)
            $requiredSeconds = ($lesson->duration_minutes ?? 0) * 60;
            
            $alreadyCompleted = $progressRecord->is_completed;
            $newlyCompleted = false;

            if (!$alreadyCompleted) {
                if ($watchTimeSeconds >= $requiredSeconds) {
                    $progressRecord->is_completed = true;
                    $progressRecord->completed_at = now();
                    $newlyCompleted = true;
                }
            }

            $progressRecord->save();

            // 9. Nếu hoàn thành mới, cập nhật tiến độ tổng quan của Enrollment
            if ($newlyCompleted) {
                $lessons = $course->lessons;
                $totalLessonsCount = $lessons->count();

                if ($totalLessonsCount > 0) {
                    $completedLessonsCount = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                        ->where('is_completed', true)
                        ->count();

                    $progressPercent = round(($completedLessonsCount / $totalLessonsCount) * 100, 2);
                    $enrollment->progress_percent = $progressPercent;

                    if ($completedLessonsCount === $totalLessonsCount) {
                        $enrollment->status = 'completed';
                        $enrollment->completed_at = now();
                    } else {
                        $enrollment->status = 'in_progress';
                    }

                    $enrollment->save();
                }
            }

            // 10. Tìm thông tin bài học tiếp theo (nếu có) để chuẩn bị nút "Bài tiếp theo"
            $nextLesson = $course->lessons
                ->where('order', '>', $lesson->order)
                ->sortBy('order')
                ->first();

            $isCompleted = $progressRecord->is_completed;
            $nextLessonId = null;
            $unlockCondition = '';

            if ($nextLesson !== null) {
                $nextLessonId = (string) $nextLesson->id;
                $unlockCondition = 'Hoàn thành bài học này để mở khóa bài tiếp theo: ' . $nextLesson->title;
            } else {
                $unlockCondition = 'Hoàn thành bài học này để hoàn thành khóa học.';
            }

            // A2 – Employee chưa xem đủ video
            if (!$isCompleted) {
                $message = 'Vui lòng xem hết video để mở khóa bài học tiếp theo.';
            } else {
                $message = 'Cập nhật tiến độ xem video thành công.';
            }

            $responsePayload = [
                'lesson_id' => (string) $lesson->id,
                'current_watch_seconds' => $progressRecord->current_watch_seconds,
                'is_completed' => $isCompleted,
                'course_progress_percent' => (float) $enrollment->progress_percent,
                'course_status' => $enrollment->status,
                'next_lesson_id' => $nextLessonId,
                'unlock_condition' => $unlockCondition,
            ];

            return $this->success(
                data: $responsePayload,
                message: $message
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A3 – Lỗi phát video hoặc cập nhật tiến độ
            return ServiceReturn::error(
                message: 'Không thể cập nhật tiến độ học tập.',
                code: 500
            );
        });
    }

    /**
     * Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học (UC-056).
     *
     * @param string $lessonId
     * @param string $userId
     * @return ServiceReturn
     */
    public function getLessonQuiz(string $lessonId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin bài học
            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            // 4. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 5. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            // 6. A1 – Employee chưa hoàn thành bài học trước (hoặc chưa xem hoàn thành video bài này)
            $progressRecord = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->first();
            $this->validate($progressRecord !== null && $progressRecord->is_completed === true, 'Bạn cần hoàn thành bài học trước khi làm quiz.', 403);

            // 7. Lấy danh sách câu hỏi kiểm tra (Quiz)
            $questions = $this->courseRepository->getQuizQuestions($lesson->id);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            $questionIds = $questions->pluck('id')->toArray();
            $draftAttempts = \App\Modules\Learning\Models\QuizAttempt::where('user_id', $userId)
                ->whereIn('quiz_id', $questionIds)
                ->where('is_draft', true)
                ->get()
                ->keyBy('quiz_id');

            // Trả về danh sách câu hỏi không kèm đáp án đúng, kèm câu trả lời nháp đã lưu nếu có
            $quizQuestions = $questions->map(function ($item) use ($draftAttempts) {
                $draft = $draftAttempts->get($item->id);
                return [
                    'id' => (string) $item->id,
                    'question' => $item->question,
                    'options' => $item->options,
                    'draft_selected_option' => $draft ? $draft->selected_option : null,
                ];
            })->toArray();

            return $this->success(
                data: [
                    'lesson_id' => (string) $lesson->id,
                    'lesson_title' => $lesson->title,
                    'questions' => $quizQuestions,
                    'time_limit_minutes' => 15, // Thời gian làm bài mặc định là 15 phút
                ],
                message: 'Tải danh sách câu hỏi kiểm tra thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải thông tin bài quiz.',
                code: 500
            );
        });
    }

    /**
     * Nộp kết quả làm bài kiểm tra trắc nghiệm (UC-056).
     *
     * @param string $lessonId
     * @param array $answers
     * @param bool $isTimeout
     * @param string $userId
     * @return ServiceReturn
     */
    public function submitLessonQuiz(string $lessonId, array $answers, bool $isTimeout, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $answers, $isTimeout, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin bài học
            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            // 4. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 5. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            // 6. A1 – Employee chưa hoàn thành bài học trước
            $progressRecord = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->first();
            $this->validate($progressRecord !== null && $progressRecord->is_completed === true, 'Bạn cần hoàn thành bài học trước khi làm quiz.', 403);

            // 7. Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học từ DB
            $questions = $this->courseRepository->getQuizQuestions($lesson->id);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            // Tạo map câu trả lời đã gửi
            $submittedMap = [];
            foreach ($answers as $ans) {
                if (isset($ans['quiz_id'])) {
                    $submittedMap[(string) $ans['quiz_id']] = (int) ($ans['selected_option'] ?? -1);
                }
            }

            // 8. A2 – Employee chưa trả lời đủ câu hỏi (không chấp nhận thiếu khi không phải timeout)
            if (!$isTimeout) {
                $answeredCount = 0;
                foreach ($questions as $q) {
                    if (isset($submittedMap[(string) $q->id]) && $submittedMap[(string) $q->id] !== -1) {
                        $answeredCount++;
                    }
                }
                $this->validate($answeredCount === $questions->count(), 'Vui lòng hoàn thành tất cả câu hỏi.', 422);
            }

            // Xóa lịch sử làm bài trước đó của nhân viên cho các câu hỏi này
            $questionIds = $questions->pluck('id')->toArray();
            \App\Modules\Learning\Models\QuizAttempt::where('user_id', $userId)
                ->whereIn('quiz_id', $questionIds)
                ->delete();

            // Chấm điểm và lưu kết quả
            $correctCount = 0;
            $totalCount = $questions->count();
            $details = [];

            foreach ($questions as $q) {
                $selectedOption = $submittedMap[(string) $q->id] ?? -1;
                $isCorrect = ($selectedOption === (int) $q->correct_option);

                if ($isCorrect) {
                    $correctCount++;
                }

                // Lưu kết quả bài làm vào DB
                \App\Modules\Learning\Models\QuizAttempt::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'quiz_id' => $q->id,
                    'selected_option' => $selectedOption,
                    'is_correct' => $isCorrect,
                ]);

                $details[] = [
                    'quiz_id' => (string) $q->id,
                    'question' => $q->question,
                    'options' => $q->options,
                    'selected_option' => $selectedOption,
                    'correct_option' => (int) $q->correct_option,
                    'is_correct' => $isCorrect,
                ];
            }

            // Tính điểm số (%)
            $score = round(($correctCount / $totalCount) * 100, 2);
            $passingScore = 80.00; // Tiêu chuẩn đạt là 80% câu đúng
            $isPassed = $score >= $passingScore;

            $message = $isPassed ? 'Chúc mừng! Bạn đã hoàn thành bài quiz đạt yêu cầu.' : 'Rất tiếc! Bạn chưa đạt điểm yêu cầu của bài quiz.';

            $responsePayload = [
                'score' => $score,
                'correct_count' => $correctCount,
                'total_questions' => $totalCount,
                'is_passed' => $isPassed,
                'passing_score' => $passingScore,
                'details' => $details,
            ];

            return $this->success(
                data: $responsePayload,
                message: $message
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A4 – Lỗi nộp bài kiểm tra
            return ServiceReturn::error(
                message: 'Không thể nộp bài kiểm tra.',
                code: 500
            );
        });
    }

    /**
     * Ghi nhận nhân viên hoàn thành khóa học (UC-057).
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function completeCourse(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 4. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            // Nếu đã hoàn thành từ trước, trả về thông tin thành công ngay
            if ($enrollment->status === 'completed') {
                return $this->success(
                    data: $enrollment,
                    message: 'Bạn đã hoàn thành khóa học.'
                );
            }

            // 5. A1 – Employee chưa hoàn thành toàn bộ bài học
            $lessons = \App\Modules\Learning\Models\CourseLesson::where('course_id', $courseId)->get();
            $this->validate($lessons->isNotEmpty(), 'Khóa học này không có bài học nào.', 400);

            $completedLessonIds = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('is_completed', true)
                ->pluck('lesson_id')
                ->toArray();

            $totalLessonsCount = $lessons->count();
            $completedLessonsCount = count($completedLessonIds);

            if ($completedLessonsCount < $totalLessonsCount) {
                return ServiceReturn::error(
                    message: 'Bạn chưa hoàn thành tất cả bài học.',
                    code: 403
                );
            }

            // 6. A2 – Employee chưa đạt điểm yêu cầu của bài quiz cuối khóa (Quiz của bài học cuối cùng)
            $lastLesson = $lessons->sortByDesc('order')->first();
            if ($lastLesson !== null) {
                $quizQuestions = \App\Modules\Learning\Models\CourseQuiz::where('lesson_id', $lastLesson->id)->get();
                if ($quizQuestions->isNotEmpty()) {
                    $questionIds = $quizQuestions->pluck('id')->toArray();
                    $correctAttemptsCount = \App\Modules\Learning\Models\QuizAttempt::where('user_id', $userId)
                        ->whereIn('quiz_id', $questionIds)
                        ->where('is_correct', true)
                        ->count();

                    $totalQuestionsCount = $quizQuestions->count();
                    $score = ($correctAttemptsCount / $totalQuestionsCount) * 10; // Điểm quy về hệ 10

                    if ($score < 8.00) {
                        return ServiceReturn::error(
                            message: 'Bạn chưa đạt điểm yêu cầu để hoàn thành khóa học.',
                            code: 403
                        );
                    }
                }
            }

            // 7. Cập nhật trạng thái và tiến độ khóa học thành Hoàn thành (100%)
            $enrollment->status = 'completed';
            $enrollment->progress_percent = 100.00;
            $enrollment->completed_at = now();
            $enrollment->save();

            return $this->success(
                data: $enrollment,
                message: 'Bạn đã hoàn thành khóa học.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A3 – Lỗi cập nhật trạng thái khóa học
            return ServiceReturn::error(
                message: 'Không thể cập nhật trạng thái khóa học.',
                code: 500
            );
        });
    }

    /**
     * Lấy dữ liệu chứng nhận của khóa học (UC-058).
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function getCertificate(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 4. A2 – Khóa học không hỗ trợ chứng nhận
            if ($course->has_certificate === false) {
                return ServiceReturn::error(
                    message: 'Khóa học không hỗ trợ chứng nhận.',
                    code: 403
                );
            }

            // 5. A1 – Employee chưa hoàn thành khóa học
            $enrollment = $course->enrollments->first();
            if ($enrollment === null || $enrollment->status !== 'completed') {
                return ServiceReturn::error(
                    message: 'Bạn chưa hoàn thành khóa học.',
                    code: 403
                );
            }

            // 6. Tính toán điểm số của bài quiz cuối khóa (Quiz của bài học cuối cùng)
            $score = 10.00; // Mặc định điểm hoàn hảo nếu không có quiz nào
            $lessons = \App\Modules\Learning\Models\CourseLesson::where('course_id', $courseId)->get();
            if ($lessons->isNotEmpty()) {
                $lastLesson = $lessons->sortByDesc('order')->first();
                if ($lastLesson !== null) {
                    $quizQuestions = \App\Modules\Learning\Models\CourseQuiz::where('lesson_id', $lastLesson->id)->get();
                    if ($quizQuestions->isNotEmpty()) {
                        $questionIds = $quizQuestions->pluck('id')->toArray();
                        $correctAttemptsCount = \App\Modules\Learning\Models\QuizAttempt::where('user_id', $userId)
                            ->whereIn('quiz_id', $questionIds)
                            ->where('is_correct', true)
                            ->count();

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

    /**
     * Tải file chứng nhận của khóa học (UC-058).
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function downloadCertificate(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            // Lấy dữ liệu chứng nhận trước
            $certResult = $this->getCertificate($courseId, $userId);
            if ($certResult->isError()) {
                return $certResult;
            }

            $data = $certResult->getData();

            // Biên dịch nội dung file chứng nhận dạng văn bản (text) đơn giản
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
            // A3 – Lỗi tải chứng nhận
            return ServiceReturn::error(
                message: 'Không thể tải chứng nhận.',
                code: 500
            );
        });
    }

    /**
     * Lưu tạm bài làm quiz (lưu bản nháp) (UC-059).
     *
     * @param string $lessonId ID bài học
     * @param array $answers Danh sách câu trả lời nháp [{quiz_id, selected_option}]
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function saveQuizDraft(string $lessonId, array $answers, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $answers, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin bài học
            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            // 4. Tìm thông tin khóa học tương ứng
            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 5. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            // 6. Kiểm tra Preconditions: Nhân viên cần hoàn thành xem video bài học này trước khi làm quiz
            $progressRecord = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->first();
            $this->validate($progressRecord !== null && $progressRecord->is_completed === true, 'Bạn cần hoàn thành bài học trước khi làm quiz.', 403);

            // 7. Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học từ DB
            $questions = $this->courseRepository->getQuizQuestions($lesson->id);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            // 8. A1 – Không có dữ liệu để lưu
            $hasData = false;
            $submittedMap = [];
            foreach ($answers as $ans) {
                if (isset($ans['quiz_id']) && isset($ans['selected_option']) && $ans['selected_option'] !== null && $ans['selected_option'] !== '') {
                    $submittedMap[(string) $ans['quiz_id']] = (int) $ans['selected_option'];
                    $hasData = true;
                }
            }

            if (!$hasData) {
                return ServiceReturn::error(
                    message: 'Không có dữ liệu để lưu.',
                    code: 422
                );
            }

            // Xóa lịch sử nháp/bài làm trước đó của nhân viên cho các câu hỏi này
            $questionIds = $questions->pluck('id')->toArray();
            \App\Modules\Learning\Models\QuizAttempt::where('user_id', $userId)
                ->whereIn('quiz_id', $questionIds)
                ->delete();

            // Lưu các câu trả lời nháp hiện tại
            foreach ($questions as $q) {
                $selectedOption = $submittedMap[(string) $q->id] ?? null;
                if ($selectedOption !== null) {
                    \App\Modules\Learning\Models\QuizAttempt::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'user_id' => $userId,
                        'quiz_id' => $q->id,
                        'selected_option' => $selectedOption,
                        'is_correct' => null, // Chưa đánh giá điểm số cho bản nháp
                        'is_draft' => true,
                    ]);
                }
            }

            return $this->success(
                data: null,
                message: 'Lưu bản nháp thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A2 – Lỗi lưu bản nháp
            return ServiceReturn::error(
                message: 'Không thể lưu bản nháp.',
                code: 500
            );
        });
    }
}

