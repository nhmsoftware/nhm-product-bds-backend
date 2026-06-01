<?php

namespace App\Modules\Learning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Learning\DTO\ViewCoursesDTO;
use App\Modules\Learning\DTO\AdminViewCoursesDTO;
use App\Modules\Learning\DTO\AdminCreateCourseDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseStatusDTO;
use App\Modules\Learning\DTO\AdminCreateCourseQuizDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseQuizDTO;
use App\Modules\Learning\DTO\AdminCreateLessonDTO;
use App\Modules\Learning\DTO\AdminUpdateLessonDTO;
use App\Modules\Learning\DTO\AdminCreateQuizDTO;
use App\Modules\Learning\DTO\AdminUpdateQuizDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseLessonRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseQuizRepositoryInterface;
use App\Modules\Learning\Interfaces\LearningServiceInterface;
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use App\Modules\Learning\Models\Enums\LessonStatus;

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
     * @var CourseLessonRepositoryInterface
     */
    protected CourseLessonRepositoryInterface $lessonRepository;

    /**
     * @var CourseQuizRepositoryInterface
     */
    protected CourseQuizRepositoryInterface $quizRepository;

    protected AuthRepositoryInterface $authRepository;

    protected QuizAttemptRepositoryInterface $quizAttemptRepository;

    /**
     * Khởi tạo Service với các Repository tương ứng.
     *
     * @param CourseRepositoryInterface $courseRepository
     * @param CourseEnrollmentRepositoryInterface $courseEnrollmentRepository
     * @param CourseLessonRepositoryInterface $lessonRepository
     * @param CourseQuizRepositoryInterface $quizRepository
     */
    public function __construct(
        CourseRepositoryInterface $courseRepository,
        CourseEnrollmentRepositoryInterface $courseEnrollmentRepository,
        CourseLessonRepositoryInterface $lessonRepository,
        CourseQuizRepositoryInterface $quizRepository,
        AuthRepositoryInterface $authRepository,
        QuizAttemptRepositoryInterface $quizAttemptRepository
    ) {
        $this->courseRepository = $courseRepository;
        $this->courseEnrollmentRepository = $courseEnrollmentRepository;
        $this->lessonRepository = $lessonRepository;
        $this->quizRepository = $quizRepository;
        $this->authRepository = $authRepository;
        $this->quizAttemptRepository = $quizAttemptRepository;
    }

    /**
     * Tải danh sách khóa học bắt buộc được phân bổ cho Employee (UC-053).
     *
     * @param ViewCoursesDTO $dto DTO chứa thông tin phòng ban, vị trí công việc
     * @return ServiceReturn
     */
    public function getMandatoryCourses(ViewCoursesDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = $this->authRepository->find($dto->userId);
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
                return $this->success(data: null, message: 'Hiện chưa có khóa học bắt buộc.');
            }

            // 4. Lấy khóa học đầu tiên và load các bài học
            $course     = $courses->first();
            $course->load('lessons');
            $enrollment = $course->enrollments->first();

            // 5. Tải map tiến độ bài học của enrollment hiện tại (nếu đã đăng ký)
            $lessonProgressMap  = collect();
            $completedLessonIds = [];

            if ($enrollment) {
                $lessonProgressRecords = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id);
                $lessonProgressMap     = $lessonProgressRecords->keyBy(fn ($lp) => (string) $lp->lesson_id);
                $completedLessonIds    = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);
            }

            // 6. Tính toán trạng thái từng bài học và xác định bài đang học hiện tại
            $mappedLessons           = [];
            $hasUncompletedPreceding = false;
            $completedCount          = 0;
            $currentLessonId         = null;

            foreach ($course->lessons as $lesson) {
                $lessonId       = (string) $lesson->id;
                $isCompleted    = in_array($lessonId, $completedLessonIds);
                $progressRecord = $lessonProgressMap->get($lessonId);

                if ($isCompleted) {
                    $lessonStatus          = LessonStatus::COMPLETED;
                    $lessonProgressPercent = 100;
                    $completedCount++;
                } elseif (!$hasUncompletedPreceding) {
                    // Bài học đầu tiên chưa hoàn thành → đang học
                    $lessonStatus            = LessonStatus::LEARNING;
                    $hasUncompletedPreceding = true;

                    if ($currentLessonId === null) {
                        $currentLessonId = $lessonId;
                    }

                    // Tính % tiến độ xem video của bài đang học
                    $durationSeconds       = $lesson->duration_seconds ?? 0;
                    $watchedSeconds        = $progressRecord ? (int) $progressRecord->current_watch_seconds : 0;
                    $lessonProgressPercent = $durationSeconds > 0
                        ? (int) round(($watchedSeconds / $durationSeconds) * 100)
                        : 0;
                } else {
                    // Các bài sau bài đang học → bị khóa
                    $lessonStatus          = LessonStatus::LOCKED;
                    $lessonProgressPercent = 0;
                }

                $actionText = match ($lessonStatus) {
                    LessonStatus::COMPLETED => 'Xem lại',
                    LessonStatus::LEARNING  => ($lessonProgressPercent > 0 ? 'Tiếp tục' : 'Bắt đầu'),
                    LessonStatus::LOCKED    => 'Khóa',
                };

                $mappedLessons[] = [
                    'id'              => $lessonId,
                    'order'           => $lesson->order,
                    'title'           => $lesson->title,
                    'durationSeconds' => $lesson->duration_seconds ?? 0,
                    'status'          => strtolower($lessonStatus->name),
                    'progressPercent' => $lessonProgressPercent,
                    'isLocked'        => $lessonStatus === LessonStatus::LOCKED,
                    'canContinue'     => $lessonStatus !== LessonStatus::LOCKED,
                    'actionText'      => $actionText,
                ];
            }

            // 7. Tính % tiến độ tổng quan và trạng thái enrollment
            $totalLessons        = $course->lessons->count();
            $overallPercent      = $enrollment ? (float) $enrollment->progress_percent : 0.00;

            // Xử lý quiz và cập nhật trạng thái
            $lessonIds = $course->lessons->pluck('id')->toArray();
            $quizQuestions = $this->quizRepository->getByLessonIds($lessonIds);
            
            $hasQuiz = $quizQuestions->isNotEmpty();
            $isPassed = false;
            $lastScore = null;
            $passingScore = 8;
            $canStart = false;

            if ($hasQuiz) {
                $questionIds = $quizQuestions->pluck('id')->toArray();
                $totalQuestionsCount = $quizQuestions->count();
                $canStart = ($completedCount === $totalLessons);

                $attemptsCount = $this->quizAttemptRepository->countAttemptsByUserAndQuizIds($dto->userId, $questionIds);
                if ($attemptsCount > 0 && $totalQuestionsCount > 0) {
                    $correctAttemptsCount = $this->quizAttemptRepository->countCorrectByUserAndQuizIds($dto->userId, $questionIds);
                    $lastScore = round(($correctAttemptsCount / $totalQuestionsCount) * 10, 1);
                    $isPassed = $lastScore >= $passingScore;
                }
            }

            $enrollmentStatusStr = match (true) {
                $enrollment === null                                           => 'not_started',
                $enrollment->status === CourseEnrollmentStatus::COMPLETED     => 'completed',
                $completedCount === $totalLessons && $hasQuiz && !$isPassed    => 'quiz_pending',
                $enrollment->status === CourseEnrollmentStatus::IN_PROGRESS   => 'in_progress',
                default                                                        => 'not_started',
            };

            // 8. Chuẩn hóa cấu trúc trả về theo format mới
            $result = [
                'course' => [
                    'id'           => (string) $course->id,
                    'title'        => $course->title,
                    'label'        => 'QUY TRÌNH HỌC TỰ CNTR',
                    'description'  => $course->description,
                    'thumbnailUrl' => $course->thumbnail,
                    'isMandatory'  => (bool) $course->is_required,
                    'learningRule' => [
                        'type'                  => 'sequential',
                        'canSkipLesson'         => false,
                        'requireWatchFullVideo'  => true,
                        'autoTrackProgress'     => true,
                    ],
                    'progress' => [
                        'status'           => $enrollmentStatusStr,
                        'percent'          => (int) $overallPercent,
                        'completedLessons' => $completedCount,
                        'totalLessons'     => $totalLessons,
                        'currentLessonId'  => $currentLessonId,
                    ],
                    'quiz' => [
                        'hasQuiz'      => $hasQuiz,
                        'isPassed'     => $isPassed,
                        'lastScore'    => $lastScore,
                        'passingScore' => $passingScore,
                        'canStart'     => $canStart,
                    ],
                    'canAccessPremiumLearning' => false,
                    'notice' => [
                        'type'    => 'warning',
                        'message' => 'Bạn cần xem hết thời lượng video trước khi chuyển sang bài tiếp theo. Hệ thống sẽ tự động ghi nhận tiến độ.',
                    ],
                    'lessons' => $mappedLessons,
                ],
            ];

            return $this->success(
                data: $result,
                message: 'Tải khóa học bắt buộc thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2 – Lỗi tải khóa học
            return ServiceReturn::error(
                message: 'Không thể tải thông tin khóa học.',
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
            $user = $this->authRepository->find($userId);
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
                    'status' => CourseEnrollmentStatus::IN_PROGRESS,
                    'progress_percent' => 0.00,
                ]);
            }

            // 5. Tải danh sách bài học đã hoàn thành
            $completedLessons = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);

            // 6. Tính toán trạng thái cho từng bài học (Đang học, Hoàn thành, Khóa)
            $lessons = $course->lessons;
            $mappedLessons = [];
            $hasUncompletedPreceding = false;

            foreach ($lessons as $lesson) {
                $isCompleted = in_array($lesson->id, $completedLessons);

                if ($isCompleted) {
                    $lessonStatus = LessonStatus::COMPLETED;
                } else {
                    // Bài học chưa hoàn thành đầu tiên sẽ ở trạng thái "Đang học"
                    if (!$hasUncompletedPreceding) {
                        $lessonStatus = LessonStatus::LEARNING;
                        $hasUncompletedPreceding = true; // Đánh dấu đã gặp bài học chưa hoàn thành
                    } else {
                        // Các bài học chưa hoàn thành tiếp theo sẽ bị "Khóa"
                        $lessonStatus = LessonStatus::LOCKED;
                    }
                }

                $mappedLessons[] = [
                    'id' => (string) $lesson->id,
                    'title' => $lesson->title,
                    'content' => $lesson->content,
                    'video_url' => $lesson->video_url,
                    'duration_seconds' => $lesson->duration_seconds,
                    'order' => $lesson->order,
                    'status' => strtolower($lessonStatus->name),
                    'status_label' => $lessonStatus->label(),
                ];
            }

            // 7. Chuẩn hóa cấu trúc trả về
            $courseDetails = [
                'id' => (string) $course->id,
                'title' => $course->title,
                'thumbnail' => $course->thumbnail,
                'description' => $course->description,
                'progress_percent' => (float) $enrollment->progress_percent,
                'status' => strtolower($enrollment->status->name),
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
            $user = $this->authRepository->find($userId);
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
            $progressRecord      = $this->courseEnrollmentRepository->getLessonProgressRecord($enrollment->id, $lesson->id);
            $currentWatchSeconds = $progressRecord ? $progressRecord->current_watch_seconds : 0;

            $completedLessons = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);

            // 7. Tính toán trạng thái các bài học để xác định trạng thái của bài học hiện tại
            $lessons = $course->lessons;
            $targetStatus = LessonStatus::LOCKED;
            $hasUncompletedPreceding = false;
            $nextLesson = null;
            $foundCurrent = false;

            foreach ($lessons as $item) {
                $isCompleted = in_array($item->id, $completedLessons);
                $itemStatus = LessonStatus::LOCKED;

                if ($isCompleted) {
                    $itemStatus = LessonStatus::COMPLETED;
                } else {
                    if (!$hasUncompletedPreceding) {
                        $itemStatus = LessonStatus::LEARNING;
                        $hasUncompletedPreceding = true;
                    }
                }

                if ($item->id === $lesson->id) {
                    $targetStatus = $itemStatus;
                    $foundCurrent = true;
                    continue;
                }

                // Nếu đã tìm thấy bài học hiện tại, bài tiếp theo đầu tiên sẽ là $nextLesson
                if ($foundCurrent && $nextLesson === null) {
                    $nextLesson = $item;
                }
            }

            // 8. A1 – Bài học chưa được mở khóa
            $this->validate($targetStatus !== LessonStatus::LOCKED, 'Vui lòng hoàn thành bài học trước để mở khóa.', 403);

            // 9. Xác định thông báo điều kiện mở khóa bài tiếp theo nếu có
            if ($nextLesson !== null) {
                $unlockCondition = 'Hoàn thành bài học này để mở khóa bài tiếp theo: ' . $nextLesson->title;
            } else {
                $unlockCondition = 'Hoàn thành bài học này để hoàn thành khóa học.';
            }

            // 10. A2 – Xử lý tài liệu đính kèm
            $attachments = $lesson->attachments ?? [];
            $attachmentMessage = empty($attachments) ? 'Không có tài liệu đính kèm.' : null;

            // Xử lý "Mô tả bài học" (Thêm trường description) và "Trạng thái bài học/Bài thi"
            $statusLabel = $targetStatus->label();
            
            $quizzes = $this->quizRepository->getByLessonId($lesson->id);
            if ($quizzes->isNotEmpty()) {
                $questionIds = $quizzes->pluck('id')->toArray();
                $attemptsCount = $this->quizAttemptRepository->countAttemptsByUserAndQuizIds($userId, $questionIds);
                
                if ($attemptsCount === 0) {
                    $statusLabel = 'Làm bài kiểm tra';
                } else {
                    $hasUngraded = $this->quizAttemptRepository->hasUngradedAttempts($userId, $questionIds);
                    if ($hasUngraded) {
                        $statusLabel = 'Đang chấm bài';
                    } else {
                        $correctAttemptsCount = $this->quizAttemptRepository->countCorrectByUserAndQuizIds($userId, $questionIds);
                        $totalQuestionsCount = $quizzes->count();
                        $score = ($correctAttemptsCount / $totalQuestionsCount) * 10;
                        if ($score >= 8.0) {
                            $statusLabel = 'Xem lại';
                        } else {
                            $statusLabel = 'Chưa đạt';
                        }
                    }
                }
            }

            $result = [
                'id' => (string) $lesson->id,
                'course_id' => (string) $lesson->course_id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'description' => $lesson->content, // Chiếu theo yêu cầu UC "Mô tả bài học"
                'video_url' => $lesson->video_url,
                'duration_seconds' => $lesson->duration_seconds,
                'order' => $lesson->order,
                'status' => strtolower($targetStatus->name),
                'status_label' => $statusLabel,
                'attachments' => $attachments,
                'attachment_message' => $attachmentMessage,
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
            $user = $this->authRepository->find($userId);
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
            $progressRecord = $this->courseEnrollmentRepository->getLessonProgressRecord($enrollment->id, $lesson->id);

            if (!$progressRecord) {
                $progressRecord = new \App\Modules\Learning\Models\LessonProgress([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => false,
                    'current_watch_seconds' => 0,
                ]);
            }

            // Thời lượng yêu cầu tính bằng giây (duration_seconds)
            $requiredSeconds = $lesson->duration_seconds ?? 0;

            // Giới hạn thời gian xem không vượt quá thời lượng của video
            if ($requiredSeconds > 0 && $watchTimeSeconds > $requiredSeconds) {
                $watchTimeSeconds = $requiredSeconds;
            }

            // A5 – Lưu tiến độ xem hiện tại của Employee
            $progressRecord->current_watch_seconds = $watchTimeSeconds;

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
                    $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);

                    $progressPercent = round(($completedLessonsCount / $totalLessonsCount) * 100, 2);
                    $enrollment->progress_percent = $progressPercent;

                    if ($completedLessonsCount === $totalLessonsCount) {
                        $enrollment->status = CourseEnrollmentStatus::COMPLETED;
                        $enrollment->completed_at = now();
                    } else {
                        $enrollment->status = CourseEnrollmentStatus::IN_PROGRESS;
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
                'course_status' => strtolower($enrollment->status->name),
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
     * Lấy danh sách câu hỏi kiểm tra (Quiz) của khóa học (UC-056).
     *
     * @param string $courseId
     * @param string $userId
     * @return ServiceReturn
     */
    public function getCourseQuiz(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin khóa học
            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 4. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            // 5. Employee cần hoàn thành toàn bộ bài học trước khi làm bài quiz khóa học
            $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);
            $this->validate($completedLessonsCount === $lessons->count(), 'Bạn cần hoàn thành tất cả bài học trước khi làm quiz.', 403);

            // 6. Lấy danh sách câu hỏi kiểm tra (Quiz) của khóa học
            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            $questionIds = $questions->pluck('id')->toArray();
            $draftAttempts = $this->quizAttemptRepository
                ->getDraftsByUserAndQuizIds($userId, $questionIds)
                ->keyBy('quiz_id');

            // Trả về danh sách câu hỏi không kèm đáp án đúng, kèm câu trả lời nháp đã lưu nếu có
            $quizQuestions = $questions->map(function ($item) use ($draftAttempts) {
                $draft = $draftAttempts->get($item->id);
                $mappedOptions = [];
                foreach ($item->options ?? [] as $idx => $content) {
                    $mappedOptions[] = [
                        'value' => $idx,
                        'label' => $content
                    ];
                }

                return [
                    'id' => (string) $item->id,
                    'type' => $item->type ?? \App\Modules\Learning\Models\Enums\CourseQuizType::MULTIPLE_CHOICE->value,
                    'order' => $item->order ?? 1,
                    'title' => $item->title ?? ('Câu hỏi ' . ($item->order ?? 1)),
                    'question' => $item->question,
                    'image_url' => $item->image_url,
                    'options' => $mappedOptions,
                    'placeholder' => $item->placeholder,
                    'draft_selected_option' => $draft ? $draft->selected_option : null,
                    'draft_essay_answer' => $draft ? $draft->essay_answer : null,
                ];
            })->toArray();

            return $this->success(
                data: [
                    'course_id' => (string) $course->id,
                    'course_title' => $course->title,
                    'quiz_title' => 'Bài kiểm tra kiến thức',
                    'time_limit_minutes' => 45, // Thời gian làm bài theo yêu cầu mới là 45 phút
                    'questions' => $quizQuestions,
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
     * Nộp kết quả làm bài kiểm tra trắc nghiệm khóa học (UC-056).
     *
     * @param string $courseId
     * @param array $answers
     * @param bool $isTimeout
     * @param string $userId
     * @return ServiceReturn
     */
    public function submitCourseQuiz(string $courseId, array $answers, bool $isTimeout, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $answers, $isTimeout, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin khóa học
            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 4. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            // 5. Employee cần hoàn thành toàn bộ bài học trước khi làm bài quiz khóa học
            $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);
            $this->validate($completedLessonsCount === $lessons->count(), 'Bạn cần hoàn thành tất cả bài học trước khi làm quiz.', 403);

            // 6. Lấy danh sách câu hỏi kiểm tra (Quiz) của khóa học từ DB
            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            // Tạo map câu trả lời đã gửi
            $submittedMap = [];
            $submittedEssayMap = [];
            foreach ($answers as $ans) {
                if (isset($ans['quiz_id'])) {
                    $submittedMap[(string) $ans['quiz_id']] = isset($ans['selected_option']) ? (int) $ans['selected_option'] : -1;
                    
                    if (isset($ans['essay_answer'])) {
                        $submittedEssayMap[(string) $ans['quiz_id']] = $ans['essay_answer'];
                    }
                }
            }

            // 8. A2 – Employee chưa trả lời đủ câu hỏi (không chấp nhận thiếu khi không phải timeout)
            if (!$isTimeout) {
                $answeredCount = 0;
                foreach ($questions as $q) {
                    $type = $q->type ?? \App\Modules\Learning\Models\Enums\CourseQuizType::MULTIPLE_CHOICE->value;
                    if ($type === \App\Modules\Learning\Models\Enums\CourseQuizType::ESSAY->value) {
                        if (isset($submittedEssayMap[(string) $q->id]) && trim($submittedEssayMap[(string) $q->id]) !== '') {
                            $answeredCount++;
                        }
                    } else {
                        if (isset($submittedMap[(string) $q->id]) && $submittedMap[(string) $q->id] !== -1) {
                            $answeredCount++;
                        }
                    }
                }
                $this->validate($answeredCount === $questions->count(), 'Vui lòng hoàn thành tất cả câu hỏi.', 422);
            }

            // Xóa lịch sử làm bài trước đó của nhân viên cho các câu hỏi này
            $questionIds = $questions->pluck('id')->toArray();
            $this->quizAttemptRepository->deleteByUserAndQuizIds($userId, $questionIds);

            $correctCount = 0;
            $multipleChoiceCount = 0;
            $totalCount = $questions->count();
            $details = [];

            foreach ($questions as $q) {
                $type = $q->type ?? \App\Modules\Learning\Models\Enums\CourseQuizType::MULTIPLE_CHOICE->value;
                $selectedOption = $submittedMap[(string) $q->id] ?? -1;
                $essayAnswer = $submittedEssayMap[(string) $q->id] ?? null;
                $isCorrect = null;

                if ($type === \App\Modules\Learning\Models\Enums\CourseQuizType::MULTIPLE_CHOICE->value) {
                    $multipleChoiceCount++;
                    $isCorrect = ($selectedOption !== -1 && $selectedOption === (int) $q->correct_option);
                    if ($isCorrect) {
                        $correctCount++;
                    }
                }

                // Lưu kết quả bài làm vào DB
                $this->quizAttemptRepository->create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'quiz_id' => $q->id,
                    'selected_option' => $selectedOption === -1 ? null : $selectedOption,
                    'essay_answer' => $essayAnswer,
                    'is_correct' => $isCorrect,
                ]);

                $mappedOptions = [];
                foreach ($q->options ?? [] as $idx => $content) {
                    $mappedOptions[] = [
                        'value' => $idx,
                        'label' => $content
                    ];
                }

                $details[] = [
                    'quiz_id' => (string) $q->id,
                    'type' => $type,
                    'order' => $q->order ?? 1,
                    'title' => $q->title,
                    'question' => $q->question,
                    'options' => $mappedOptions,
                    'selected_option' => $selectedOption === -1 ? null : $selectedOption,
                    'essay_answer' => $essayAnswer,
                    'correct_option' => $type === \App\Modules\Learning\Models\Enums\CourseQuizType::ESSAY->value ? null : (int) $q->correct_option,
                    'is_correct' => $isCorrect,
                ];
            }

            // Tính điểm số (%)
            if ($multipleChoiceCount > 0) {
                $score = round(($correctCount / $multipleChoiceCount) * 100, 2);
            } else {
                $score = 100.00; // Mặc định 100% nếu toàn câu tự luận
            }
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
            $user = $this->authRepository->find($userId);
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
            if ($enrollment->status === CourseEnrollmentStatus::COMPLETED) {
                return $this->success(
                    data: $enrollment,
                    message: 'Bạn đã hoàn thành khóa học.'
                );
            }

            // 5. A1 – Employee chưa hoàn thành toàn bộ bài học
            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học này không có bài học nào.', 400);

            $completedLessonIds = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);

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
                $quizQuestions = $this->quizRepository->getByLessonId($lastLesson->id);
                if ($quizQuestions->isNotEmpty()) {
                    $questionIds = $quizQuestions->pluck('id')->toArray();
                    $correctAttemptsCount = $this->quizAttemptRepository
                        ->countCorrectByUserAndQuizIds($userId, $questionIds);

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
            $enrollment->status = CourseEnrollmentStatus::COMPLETED;
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
            $user = $this->authRepository->find($userId);
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
            if ($enrollment === null || $enrollment->status !== CourseEnrollmentStatus::COMPLETED) {
                return ServiceReturn::error(
                    message: 'Bạn chưa hoàn thành khóa học.',
                    code: 403
                );
            }

            // 6. Tính toán điểm số của bài quiz cuối khóa (Quiz của bài học cuối cùng)
            $score = 10.00; // Mặc định điểm hoàn hảo nếu không có quiz nào
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
     * @param string $courseId ID khóa học
     * @param array $answers Danh sách câu trả lời nháp [{quiz_id, selected_option}]
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function saveCourseQuizDraft(string $courseId, array $answers, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $answers, $userId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Tìm thông tin khóa học
            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            // 4. Kiểm tra Preconditions: Nhân viên đã tham gia khóa học
            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            // 5. Employee cần hoàn thành toàn bộ bài học trước khi lưu nháp quiz
            $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);
            $this->validate($completedLessonsCount === $lessons->count(), 'Bạn cần hoàn thành tất cả bài học trước khi làm quiz.', 403);

            // 6. Lấy danh sách câu hỏi kiểm tra (Quiz) của khóa học
            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
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
            $this->quizAttemptRepository->deleteByUserAndQuizIds($userId, $questionIds);

            // Lưu các câu trả lời nháp hiện tại
            foreach ($questions as $q) {
                $selectedOption = $submittedMap[(string) $q->id] ?? null;
                if ($selectedOption !== null) {
                    $this->quizAttemptRepository->create([
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

    /**
     * Kiểm tra và xác thực xem người dùng có phải là Admin đang hoạt động hay không.
     *
     * @param string $adminId
     * @return void
     */
    private function validateAdmin(string $adminId): void
    {
        $admin = $this->authRepository->find($adminId);
        $this->validate($admin !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
        $this->validate($admin->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);
        $this->validate(
            in_array($admin->role, [
                \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN,
                \App\Modules\Auth\Models\Enums\UserRole::CEO,
                \App\Modules\Auth\Models\Enums\UserRole::DIRECTOR
            ], true),
            'Bạn không có quyền thực hiện chức năng này.',
            403
        );
    }

    /**
     * Tải danh sách khóa học cho Admin kèm tìm kiếm và lọc.
     */
    public function adminGetCourses(AdminViewCoursesDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($dto, $adminId) {
            $this->validateAdmin($adminId);
            $paginator = $this->courseRepository->searchAndFilter($dto->toArray(), $dto->perPage);

            $items = collect($paginator->items())->map(function ($course) {
                return [
                    'id' => (string) $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail' => $course->thumbnail,
                    'is_required' => (bool) $course->is_required,
                    'department' => $course->department,
                    'job_position' => $course->job_position,
                    'order' => (int) $course->order,
                    'is_active' => (bool) $course->is_active,
                    'has_certificate' => (bool) $course->has_certificate,
                    'lessons_count' => (int) $course->lessons_count,
                    'created_at' => $course->created_at?->toIso8601String(),
                ];
            });

            return $this->success(
                data: [
                    'list' => $items->toArray(),
                    'pagination' => [
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                    ]
                ],
                message: 'Tải danh sách khóa học thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * Lấy thông tin chi tiết một khóa học cho Admin.
     */
    public function adminGetCourseDetails(string $courseId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $adminId) {
            $this->validateAdmin($adminId);
            $course = $this->courseRepository->getCourseDetailsForAdmin($courseId);
            $this->validate($course !== null, 'Không tìm thấy khóa học.', 404);

            $lessons = $course->lessons->map(function ($lesson) {
                $quizzes = $lesson->quizzes->map(function ($quiz) {
                    return [
                        'id' => (string) $quiz->id,
                        'question' => $quiz->question,
                        'options' => $quiz->options,
                        'correct_option' => (int) $quiz->correct_option,
                    ];
                });

                return [
                    'id' => (string) $lesson->id,
                    'title' => $lesson->title,
                    'content' => $lesson->content,
                    'video_url' => $lesson->video_url,
                    'duration_seconds' => (int) $lesson->duration_seconds,
                    'order' => (int) $lesson->order,
                    'is_active' => (bool) $lesson->is_active,
                    'attachments' => $lesson->attachments,
                    'quizzes' => $quizzes->toArray(),
                ];
            });

            $data = [
                'id' => (string) $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail,
                'is_required' => (bool) $course->is_required,
                'department' => $course->department,
                'job_position' => $course->job_position,
                'order' => (int) $course->order,
                'is_active' => (bool) $course->is_active,
                'has_certificate' => (bool) $course->has_certificate,
                'lessons' => $lessons->toArray(),
            ];

            return $this->success(data: $data, message: 'Tải chi tiết khóa học thành công.');
        }, useTransaction: false);
    }

    /**
     * Tạo khóa học mới.
     */
    public function adminCreateCourse(AdminCreateCourseDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($dto, $adminId) {
            $this->validateAdmin($adminId);

            // A2 – Chưa có bài học
            $this->validate(!empty($dto->lessons), 'Vui lòng thêm ít nhất một bài học.', 422);

            // A5 – Khóa học chưa có quiz
            $totalQuizzes = 0;
            foreach ($dto->lessons as $lessonData) {
                if (!empty($lessonData['quizzes'])) {
                    $totalQuizzes += count($lessonData['quizzes']);
                }
            }
            $this->validate($totalQuizzes > 0, 'Khóa học chưa có quiz. Vui lòng tạo quiz cho khóa học.', 422);

            // Create Course
            $course = $this->courseRepository->create($dto->toArray());
            $this->validate($course !== null, 'Không thể tạo khóa học.', 500);

            // Create Lessons and Quizzes
            foreach ($dto->lessons as $lessonIndex => $lessonData) {
                $lesson = $this->lessonRepository->create([
                    'course_id' => $course->id,
                    'title' => $lessonData['title'],
                    'content' => $lessonData['content'] ?? null,
                    'video_url' => $lessonData['video_url'] ?? null,
                    'duration_seconds' => (int) $lessonData['duration_seconds'],
                    'order' => (int) ($lessonData['order'] ?? $lessonIndex + 1),
                    'is_active' => (bool) ($lessonData['is_active'] ?? true),
                    'attachments' => $lessonData['attachments'] ?? null,
                ]);

                $this->validate($lesson !== null, 'Không thể tạo khóa học.', 500);

                if (!empty($lessonData['quizzes'])) {
                    foreach ($lessonData['quizzes'] as $quizData) {
                        $quiz = $this->quizRepository->create([
                            'lesson_id' => $lesson->id,
                            'question' => $quizData['question'],
                            'options' => $quizData['options'],
                            'correct_option' => (int) $quizData['correct_option'],
                        ]);
                        $this->validate($quiz !== null, 'Không thể tạo khóa học.', 500);
                    }
                }
            }

            // Return full course structure
            $fullCourse = $this->courseRepository->getCourseDetailsForAdmin($course->id);
            return $this->success(data: $fullCourse, message: 'Tạo khóa học thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A6 – Lỗi tạo khóa học
            return ServiceReturn::error(
                message: $e->getCode() === 422 ? $e->getMessage() : 'Không thể tạo khóa học.',
                code: $e->getCode() === 422 ? 422 : 500
            );
        });
    }

    /**
     * Cập nhật thông tin khóa học.
     */
    public function adminUpdateCourse(string $courseId, AdminUpdateCourseDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            $this->validateAdmin($adminId);

            // A1 – Khóa học không tồn tại
            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            // A3 – Chưa có bài học
            $this->validate(!empty($dto->lessons), 'Vui lòng thêm ít nhất một bài học.', 422);

            // A6 – Khóa học chưa có quiz
            $totalQuizzes = 0;
            foreach ($dto->lessons as $lessonData) {
                if (!empty($lessonData['quizzes'])) {
                    $totalQuizzes += count($lessonData['quizzes']);
                }
            }
            $this->validate($totalQuizzes > 0, 'Khóa học chưa có quiz. Vui lòng tạo quiz cho khóa học.', 422);

            // Update course itself
            $updated = $this->courseRepository->updateById($courseId, $dto->toArray());
            $this->validate($updated !== false, 'Không thể cập nhật khóa học.', 500);

            // Get existing lessons of this course
            $existingLessons = $this->lessonRepository->getByCourseId($courseId);
            $existingLessonIds = $existingLessons->pluck('id')->toArray();

            $processedLessonIds = [];

            // Process lessons in payload
            foreach ($dto->lessons as $lessonIndex => $lessonData) {
                $lessonId = $lessonData['id'] ?? null;

                $lessonPayload = [
                    'course_id' => $courseId,
                    'title' => $lessonData['title'],
                    'content' => $lessonData['content'] ?? null,
                    'video_url' => $lessonData['video_url'] ?? null,
                    'duration_seconds' => (int) $lessonData['duration_seconds'],
                    'order' => (int) ($lessonData['order'] ?? $lessonIndex + 1),
                    'is_active' => (bool) ($lessonData['is_active'] ?? true),
                    'attachments' => $lessonData['attachments'] ?? null,
                ];

                if ($lessonId && in_array($lessonId, $existingLessonIds)) {
                    // Update existing lesson
                    $lesson = $this->lessonRepository->updateById($lessonId, $lessonPayload);
                    $this->validate($lesson !== false, 'Không thể cập nhật khóa học.', 500);
                    $processedLessonIds[] = $lessonId;
                } else {
                    // Create new lesson
                    $lesson = $this->lessonRepository->create($lessonPayload);
                    $this->validate($lesson !== null, 'Không thể cập nhật khóa học.', 500);
                    $lessonId = (string) $lesson->id;
                    $processedLessonIds[] = $lessonId;
                }

                // Process quizzes for this lesson
                $existingQuizzes = $this->quizRepository->getByLessonId($lessonId);
                $existingQuizIds = $existingQuizzes->pluck('id')->toArray();
                $processedQuizIds = [];

                if (!empty($lessonData['quizzes'])) {
                    foreach ($lessonData['quizzes'] as $quizData) {
                        $quizId = $quizData['id'] ?? null;
                        $quizPayload = [
                            'lesson_id' => $lessonId,
                            'question' => $quizData['question'],
                            'options' => $quizData['options'],
                            'correct_option' => (int) $quizData['correct_option'],
                        ];

                        if ($quizId && in_array($quizId, $existingQuizIds)) {
                            // Update existing quiz
                            $quiz = $this->quizRepository->updateById($quizId, $quizPayload);
                            $this->validate($quiz !== false, 'Không thể cập nhật khóa học.', 500);
                            $processedQuizIds[] = $quizId;
                        } else {
                            // Create new quiz
                            $quiz = $this->quizRepository->create($quizPayload);
                            $this->validate($quiz !== null, 'Không thể cập nhật khóa học.', 500);
                            $processedQuizIds[] = (string) $quiz->id;
                        }
                    }
                }

                // Delete quizzes of this lesson that are not in the payload
                $quizzesToDelete = array_diff($existingQuizIds, $processedQuizIds);
                foreach ($quizzesToDelete as $delQuizId) {
                    $this->quizRepository->deleteById($delQuizId);
                }
            }

            // Delete lessons (and cascade deletes quizzes) not in the payload
            $lessonsToDelete = array_diff($existingLessonIds, $processedLessonIds);
            foreach ($lessonsToDelete as $delLessonId) {
                $this->lessonRepository->deleteById($delLessonId);
            }

            // Return full course structure
            $fullCourse = $this->courseRepository->getCourseDetailsForAdmin($courseId);
            return $this->success(data: $fullCourse, message: 'Cập nhật khóa học thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A7 – Lỗi cập nhật khóa học
            $code = $e->getCode();
            $message = $e->getMessage();

            // Check if it's one of our validation exceptions
            if ($code === 404) {
                return ServiceReturn::error(message: $message, code: 404);
            }
            if ($code === 422) {
                return ServiceReturn::error(message: $message, code: 422);
            }

            return ServiceReturn::error(message: 'Không thể cập nhật khóa học.', code: 500);
        });
    }

    /**
     * Cập nhật trạng thái hoạt động (Khóa/Mở khóa) của khóa học (UC-072).
     */
    public function adminUpdateCourseStatus(string $courseId, AdminUpdateCourseStatusDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            $this->validateAdmin($adminId);

            // A1 – Khóa học không tồn tại
            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            $updated = $this->courseRepository->updateById($courseId, [
                'is_active' => $dto->isActive,
            ]);
            $this->validate($updated !== false, 'Không thể cập nhật trạng thái khóa học.', 500);

            return $this->success(
                data: $this->courseRepository->getCourseDetailsForAdmin($courseId),
                message: 'Cập nhật trạng thái khóa học thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A3 – Lỗi cập nhật trạng thái khóa học
            $code = $e->getCode();
            $message = $e->getMessage();

            if ($code === 404) {
                return ServiceReturn::error(message: $message, code: 404);
            }
            if ($code === 422) {
                return ServiceReturn::error(message: $message, code: 422);
            }

            return ServiceReturn::error(message: 'Không thể cập nhật trạng thái khóa học.', code: 500);
        });
    }

    /**
     * Tạo bài quiz cho khóa học (UC-073).
     */
    public function adminCreateCourseQuiz(string $courseId, AdminCreateCourseQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            // 1. Validate Admin
            $this->validateAdmin($adminId);

            // 2. A1 – Khóa học không tồn tại
            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            // Fetch course lessons to find the last lesson
            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học để tạo quiz.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();

            // 3. A2 – Khóa học đã có quiz
            $existingQuizCount = $this->quizRepository->countByLessonIds($lessonIds);
            $this->validate($existingQuizCount === 0, 'Khóa học đã có bài quiz.', 400);

            // 4. A5 – Điểm đạt yêu cầu không hợp lệ
            $this->validate($dto->passingScore >= 0 && $dto->passingScore <= 100, 'Điểm đạt yêu cầu không hợp lệ.', 422);

            // 5. A4 – Chưa có câu hỏi quiz
            $this->validate(!empty($dto->questions), 'Vui lòng thêm ít nhất một câu hỏi.', 422);

            // The quiz is attached to the last lesson of the course
            $lastLesson = $lessons->sortByDesc('order')->first();
            $this->validate($lastLesson !== null, 'Không tìm thấy bài học cuối cùng.', 500);

            // Insert questions into course_quizzes
            $createdQuizzes = [];
            foreach ($dto->questions as $q) {
                // Ensure correct_option index is valid for options
                $optionsCount = isset($q['options']) ? count($q['options']) : 0;
                $this->validate(
                    $optionsCount >= 2 && isset($q['correct_option']) && $q['correct_option'] >= 0 && $q['correct_option'] < $optionsCount,
                    'Vui lòng nhập đầy đủ thông tin bài quiz.',
                    422
                );

                $quiz = $this->quizRepository->create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'lesson_id' => $lastLesson->id,
                    'question' => $q['question'],
                    'options' => $q['options'],
                    'correct_option' => $q['correct_option'],
                ]);
                $createdQuizzes[] = $quiz;
            }

            return $this->success(
                data: $createdQuizzes,
                message: 'Tạo bài quiz thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [400, 403, 404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            // A6 – Lỗi tạo bài quiz
            return ServiceReturn::error(message: 'Không thể tạo bài quiz.', code: 500);
        });
    }

    /**
     * Cập nhật bài quiz cho khóa học (UC-074).
     */
    public function adminUpdateCourseQuiz(string $courseId, AdminUpdateCourseQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            // 1. Validate Admin
            $this->validateAdmin($adminId);

            // 2. Kiểm tra khóa học tồn tại
            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            // Fetch course lessons to find the last lesson
            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();

            // 3. A1 – Quiz không tồn tại
            // Check if there are any quizzes for this course
            $existingQuizzes = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($existingQuizzes->isNotEmpty(), 'Quiz không tồn tại.', 404);

            // 4. A4 – Điểm đạt yêu cầu không hợp lệ
            $this->validate($dto->passingScore >= 0 && $dto->passingScore <= 100, 'Điểm đạt yêu cầu không hợp lệ.', 422);

            // 5. A3 – Chưa có câu hỏi trong quiz
            $this->validate(!empty($dto->questions), 'Vui lòng thêm ít nhất một câu hỏi.', 422);

            // The quiz questions are attached to the last lesson of the course
            $lastLesson = $lessons->sortByDesc('order')->first();
            $this->validate($lastLesson !== null, 'Không tìm thấy bài học cuối cùng.', 500);

            // Fetch existing quiz ids to clean up / update
            $existingQuizIds = $existingQuizzes->pluck('id')->toArray();
            $processedQuizIds = [];

            // Update or create quiz questions
            foreach ($dto->questions as $q) {
                // Ensure correct_option index is valid for options
                $optionsCount = isset($q['options']) ? count($q['options']) : 0;
                $this->validate(
                    $optionsCount >= 2 && isset($q['correct_option']) && $q['correct_option'] >= 0 && $q['correct_option'] < $optionsCount,
                    'Thông tin bài quiz không hợp lệ.',
                    422
                );

                $quizId = $q['id'] ?? null;
                $quizPayload = [
                    'lesson_id' => $lastLesson->id,
                    'question' => $q['question'],
                    'options' => $q['options'],
                    'correct_option' => $q['correct_option'],
                ];

                if ($quizId && in_array($quizId, $existingQuizIds)) {
                    // Update existing
                    $updated = $this->quizRepository->updateById($quizId, $quizPayload);
                    $this->validate($updated !== false, 'Không thể cập nhật bài quiz.', 500);
                    $processedQuizIds[] = $quizId;
                } else {
                    // Create new
                    $quiz = $this->quizRepository->create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'lesson_id' => $lastLesson->id,
                        'question' => $q['question'],
                        'options' => $q['options'],
                        'correct_option' => $q['correct_option'],
                    ]);
                    $processedQuizIds[] = (string) $quiz->id;
                }
            }

            // Delete existing quizzes that are no longer in the payload
            $toDeleteQuizIds = array_diff($existingQuizIds, $processedQuizIds);
            if (!empty($toDeleteQuizIds)) {
                $this->quizRepository->deleteByIds($toDeleteQuizIds);
            }

            // Fetch the updated quizzes
            $updatedQuizzes = $this->quizRepository->getByLessonId($lastLesson->id);

            return $this->success(
                data: $updatedQuizzes,
                message: 'Cập nhật bài quiz thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [400, 403, 404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            // A5 – Lỗi cập nhật quiz
            return ServiceReturn::error(message: 'Không thể cập nhật bài quiz.', code: 500);
        });
    }

    /**
     * Xóa bài quiz của khóa học (UC-075).
     */
    public function adminDeleteCourseQuiz(string $courseId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $adminId) {
            // 1. Validate Admin
            $this->validateAdmin($adminId);

            // 2. Kiểm tra khóa học tồn tại
            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            // Fetch course lessons
            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();

            // 3. A1 – Quiz không tồn tại
            // Check if there are any quizzes for this course
            $existingQuizzes = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($existingQuizzes->isNotEmpty(), 'Quiz không tồn tại.', 404);

            // 4. A2 – Quiz đã có nhân viên làm bài
            $quizIds = $existingQuizzes->pluck('id')->toArray();
            $attemptsCount = $this->quizAttemptRepository->countByQuizIds($quizIds);
            $this->validate($attemptsCount === 0, 'Không thể xóa quiz đã có nhân viên làm bài.', 400);

            // 5. Xóa quiz (soft delete)
            $deleted = $this->quizRepository->deleteByIds($quizIds);
            $this->validate($deleted !== false, 'Không thể xóa quiz.', 500);

            return $this->success(
                data: null,
                message: 'Xóa quiz thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [400, 403, 404])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            // A4 – Lỗi xóa quiz
            return ServiceReturn::error(message: 'Không thể xóa quiz.', code: 500);
        });
    }

    /**
     * Xóa khóa học.
     */
    public function adminDeleteCourse(string $courseId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $adminId) {
            $this->validateAdmin($adminId);
            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Không tìm thấy khóa học.', 404);

            $deleted = $this->courseRepository->deleteById($courseId);
            $this->validate($deleted !== false, 'Không thể xóa khóa học.', 500);

            return $this->success(data: null, message: 'Xóa khóa học thành công.');
        }, useTransaction: true);
    }

    /**
     * Tạo bài học mới.
     */
    public function adminCreateLesson(AdminCreateLessonDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($dto, $adminId) {
            $this->validateAdmin($adminId);
            $course = $this->courseRepository->find($dto->courseId);
            $this->validate($course !== null, 'Không tìm thấy khóa học.', 404);

            $lesson = $this->lessonRepository->create($dto->toArray());
            return $this->success(data: $lesson, message: 'Tạo bài học thành công.');
        }, useTransaction: true);
    }

    /**
     * Cập nhật thông tin bài học.
     */
    public function adminUpdateLesson(string $lessonId, AdminUpdateLessonDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $dto, $adminId) {
            $this->validateAdmin($adminId);
            $lesson = $this->lessonRepository->find($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            $updated = $this->lessonRepository->updateById($lessonId, $dto->toArray());
            $this->validate($updated !== false, 'Không thể cập nhật bài học.', 500);

            $updatedLesson = $this->lessonRepository->find($lessonId);
            return $this->success(data: $updatedLesson, message: 'Cập nhật bài học thành công.');
        }, useTransaction: true);
    }

    /**
     * Xóa bài học.
     */
    public function adminDeleteLesson(string $lessonId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $adminId) {
            $this->validateAdmin($adminId);
            $lesson = $this->lessonRepository->find($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            $deleted = $this->lessonRepository->deleteById($lessonId);
            $this->validate($deleted !== false, 'Không thể xóa bài học.', 500);

            return $this->success(data: null, message: 'Xóa bài học thành công.');
        }, useTransaction: true);
    }

    /**
     * Tạo câu hỏi quiz mới cho bài học.
     */
    public function adminCreateQuiz(AdminCreateQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($dto, $adminId) {
            $this->validateAdmin($adminId);
            $lesson = $this->lessonRepository->find($dto->lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            $quiz = $this->quizRepository->create($dto->toArray());
            return $this->success(data: $quiz, message: 'Tạo câu hỏi quiz thành công.');
        }, useTransaction: true);
    }

    /**
     * Cập nhật câu hỏi quiz.
     */
    public function adminUpdateQuiz(string $quizId, AdminUpdateQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($quizId, $dto, $adminId) {
            $this->validateAdmin($adminId);
            $quiz = $this->quizRepository->find($quizId);
            $this->validate($quiz !== null, 'Câu hỏi quiz không tồn tại.', 404);

            $updated = $this->quizRepository->updateById($quizId, $dto->toArray());
            $this->validate($updated !== false, 'Không thể cập nhật câu hỏi quiz.', 500);

            $updatedQuiz = $this->quizRepository->find($quizId);
            return $this->success(data: $updatedQuiz, message: 'Cập nhật câu hỏi quiz thành công.');
        }, useTransaction: true);
    }

    /**
     * Xóa câu hỏi quiz.
     */
    public function adminDeleteQuiz(string $quizId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($quizId, $adminId) {
            $this->validateAdmin($adminId);
            $quiz = $this->quizRepository->find($quizId);
            $this->validate($quiz !== null, 'Câu hỏi quiz không tồn tại.', 404);

            $deleted = $this->quizRepository->deleteById($quizId);
            $this->validate($deleted !== false, 'Không thể xóa câu hỏi quiz.', 500);

            return $this->success(data: null, message: 'Xóa câu hỏi quiz thành công.');
        }, useTransaction: true);
    }

    /**
     * Xác nhận hoàn thành onboarding (khóa học) cho nhân viên.
     */
    public function adminConfirmOnboarding(string $courseId, string $userId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId, $adminId) {
            $this->validateAdmin($adminId);

            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Không tìm thấy khóa học.', 404);

            $enrollment = $this->courseEnrollmentRepository->findByUserAndCourse($userId, $courseId);

            // A6 - Nhân viên đã được xác nhận onboarding trước đó
            if ($enrollment !== null && $enrollment->status === \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::COMPLETED) {
                return ServiceReturn::error(
                    message: 'Nhân viên đã hoàn thành onboarding.',
                    code: 400
                );
            }

            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học này không có bài học nào.', 400);

            $completedLessonIds = [];
            if ($enrollment) {
                $completedLessonIds = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);
            }

            $totalLessonsCount = $lessons->count();
            $completedLessonsCount = count($completedLessonIds);

            if ($completedLessonsCount < $totalLessonsCount) {
                // Determine whether they missed video completion (A4) or course completion (A3)
                $incompleteLessons = $lessons->filter(function($l) use ($completedLessonIds) {
                    return !in_array($l->id, $completedLessonIds);
                });

                $hasIncompleteVideo = $incompleteLessons->contains(function($l) {
                    return !empty($l->video_url);
                });

                if ($hasIncompleteVideo) {
                    return ServiceReturn::error(
                        message: 'Nhân viên chưa hoàn thành video đào tạo.',
                        code: 400
                    );
                } else {
                    return ServiceReturn::error(
                        message: 'Nhân viên chưa hoàn thành khóa học onboarding.',
                        code: 400
                    );
                }
            }

            // Check quiz requirements (A5)
            $quizzes = $this->quizRepository->getByLessonIds($lessons->pluck('id'));
            if ($quizzes->isNotEmpty()) {
                $correctAttemptsCount = $this->quizAttemptRepository
                    ->countCorrectByUserAndQuizIds($userId, $quizzes->pluck('id')->toArray());

                $totalQuestionsCount = $quizzes->count();
                // Passing score is 80%
                if (($correctAttemptsCount / $totalQuestionsCount) < 0.8) {
                    return ServiceReturn::error(
                        message: 'Nhân viên chưa đạt điểm yêu cầu của bài quiz.',
                        code: 400
                    );
                }
            }

            // Perform actual completion updates
            if (!$enrollment) {
                $enrollment = $this->courseEnrollmentRepository->create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::COMPLETED,
                    'progress_percent' => 100.00,
                    'completed_at' => now(),
                ]);
            } else {
                $enrollment->status = \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::COMPLETED;
                $enrollment->progress_percent = 100.00;
                $enrollment->completed_at = now();
                $enrollment->save();
            }

            return $this->success(
                data: [
                    'id' => (string) $enrollment->id,
                    'user_id' => (string) $enrollment->user_id,
                    'course_id' => (string) $enrollment->course_id,
                    'status' => $enrollment->status->serialize(),
                    'progress_percent' => (float) $enrollment->progress_percent,
                    'completed_at' => $enrollment->completed_at?->toIso8601String(),
                ],
                message: 'Xác nhận hoàn thành onboarding cho nhân viên thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [400, 403, 404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            // A7 - Lỗi cập nhật trạng thái onboarding
            return ServiceReturn::error(message: 'Không thể cập nhật trạng thái onboarding.', code: 500);
        });
    }

    /**
     * Tải danh sách tiến độ onboarding của nhân viên.
     */
    public function adminGetOnboardingList(array $filters, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($filters, $adminId) {
            $this->validateAdmin($adminId);

            $enrollments = $this->courseEnrollmentRepository->getRequiredCourseOnboardingEnrollments($filters);

            if ($enrollments->isEmpty()) {
                return $this->success(
                    data: [],
                    message: 'Không có dữ liệu onboarding.'
                );
            }

            // Transform data & calculate quiz score
            $data = [];
            foreach ($enrollments as $enrollment) {
                $lessons = $this->lessonRepository->getByCourseId($enrollment->course_id);
                $quizzes = $this->quizRepository->getByLessonIds($lessons->pluck('id'));
                if ($quizzes->isEmpty()) {
                    $quizScore = null;
                } else {
                    $correctAttempts = $this->quizAttemptRepository
                        ->countCorrectByUserAndQuizIds((string) $enrollment->user_id, $quizzes->pluck('id')->toArray());
                    $quizScore = round(($correctAttempts / $quizzes->count()) * 10, 2);
                }

                $data[] = [
                    'id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'employee_name' => $enrollment->user->name ?? '',
                    'department' => $enrollment->user->department ?? '',
                    'course_id' => $enrollment->course_id,
                    'course_title' => $enrollment->course->title ?? '',
                    'progress_percent' => (float) $enrollment->progress_percent,
                    'status' => $enrollment->status instanceof \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus
                        ? $enrollment->status->serialize()
                        : $enrollment->status,
                    'quiz_score' => $quizScore,
                ];
            }

            // 3. Quiz Score Filter (e.g. filter by passing score / ranges or specific score)
            if (isset($filters['quiz_score'])) {
                $targetScore = (float) $filters['quiz_score'];
                $data = array_values(array_filter($data, function($item) use ($targetScore) {
                    return $item['quiz_score'] == $targetScore;
                }));
            }

            if (count($data) === 0) {
                return $this->success(
                    data: [],
                    message: 'Không tìm thấy nhân viên phù hợp.'
                );
            }

            return $this->success(
                data: $data,
                message: 'Tải danh sách onboarding thành công.'
            );
        });
    }

    /**
     * Tải chi tiết tiến độ onboarding của một nhân viên đối với khóa học.
     */
    public function adminGetOnboardingDetail(string $courseId, string $userId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId, $adminId) {
            $this->validateAdmin($adminId);

            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Không tìm thấy khóa học.', 404);

            $enrollment = $this->courseEnrollmentRepository->findByUserAndCourse($userId, $courseId);

            $lessons = $this->lessonRepository->getByCourseId($courseId);

            $lessonProgressDetails = [];
            foreach ($lessons as $lesson) {
                $progress = null;
                if ($enrollment) {
                    $progress = $this->courseEnrollmentRepository->getLessonProgressRecord($enrollment->id, $lesson->id);
                }

                $lessonProgressDetails[] = [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'video_url' => $lesson->video_url,
                    'duration_seconds' => $lesson->duration_seconds,
                    'is_completed' => $progress ? (bool) $progress->is_completed : false,
                    'completed_at' => $progress && $progress->completed_at ? $progress->completed_at->toIso8601String() : null,
                    'current_watch_seconds' => $progress ? (int) $progress->current_watch_seconds : 0,
                    'video_completed' => $progress ? (bool) $progress->is_completed : false,
                ];
            }

            // Quizzes
            $quizzes = $this->quizRepository->getByLessonIds($lessons->pluck('id'));
            $quizDetails = [];
            $correctAttempts = 0;
            $quizScore = null;
            $isPassed = false;

            if ($quizzes->isNotEmpty()) {
                $totalQuestions = $quizzes->count();
                foreach ($quizzes as $quiz) {
                    $attempt = $this->quizAttemptRepository->findByUserAndQuiz($userId, $quiz->id);

                    $selectedOption = $attempt ? $attempt->selected_option : null;
                    $isCorrect = $attempt ? (bool) $attempt->is_correct : false;

                    if ($isCorrect) {
                        $correctAttempts++;
                    }

                    $quizDetails[] = [
                        'quiz_id' => $quiz->id,
                        'question' => $quiz->question,
                        'options' => $quiz->options,
                        'selected_option' => $selectedOption,
                        'correct_option' => $quiz->correct_option,
                        'is_correct' => $isCorrect,
                    ];
                }

                $quizScore = round(($correctAttempts / $totalQuestions) * 10, 2);
                $isPassed = ($correctAttempts / $totalQuestions) >= 0.8;
            }

            $payload = [
                'user_id' => $userId,
                'employee_name' => $user->name,
                'department' => $user->department,
                'course_id' => $courseId,
                'course_title' => $course->title,
                'progress_percent' => $enrollment ? (float) $enrollment->progress_percent : 0.00,
                'status' => $enrollment
                    ? $enrollment->status->serialize()
                    : CourseEnrollmentStatus::NOT_STARTED->serialize(),
                'lessons' => $lessonProgressDetails,
                'quiz' => [
                    'score' => $quizScore,
                    'total_questions' => $quizzes->count(),
                    'correct_count' => $correctAttempts,
                    'is_passed' => $isPassed,
                    'details' => $quizDetails,
                ]
            ];

            return $this->success(
                data: $payload,
                message: 'Tải thông tin chi tiết onboarding thành công.'
            );
        });
    }
}


