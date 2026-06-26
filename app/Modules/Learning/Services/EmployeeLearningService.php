<?php

declare(strict_types=1);

namespace App\Modules\Learning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Learning\DTO\ViewCoursesDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseLessonRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseQuizRepositoryInterface;
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use App\Modules\Learning\Models\Enums\LessonStatus;

final class EmployeeLearningService extends BaseService
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

    public function getMandatoryCourses(ViewCoursesDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $courses = $this->courseRepository->getMandatoryCourses(
                $dto->userId,
                $dto->department,
                $dto->jobPosition,
                $dto->role
            );

            if ($courses->isEmpty()) {
                return $this->success(data: null, message: 'Hiện chưa có khóa học bắt buộc.');
            }

            $mapCourse = function ($course) use ($dto): array {
                $course->load('lessons');
                $enrollment = $course->enrollments->first();

                $lessonProgressMap = collect();
                $completedLessonIds = [];

                if ($enrollment) {
                    $lessonProgressRecords = $this->courseEnrollmentRepository->getLessonProgress($enrollment->id);
                    $lessonProgressMap = $lessonProgressRecords->keyBy(fn ($lp) => (string) $lp->lesson_id);
                    $completedLessonIds = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);
                }

                $mappedLessons = [];
                $hasUncompletedPreceding = false;
                $completedCount = 0;
                $currentLessonId = null;

                foreach ($course->lessons as $lesson) {
                    $lessonId = (string) $lesson->id;
                    $isCompleted = in_array($lessonId, $completedLessonIds);
                    $progressRecord = $lessonProgressMap->get($lessonId);

                    if ($isCompleted) {
                        $lessonStatus = LessonStatus::COMPLETED;
                        $lessonProgressPercent = 100;
                        $completedCount++;
                    } elseif (!$hasUncompletedPreceding) {
                        $lessonStatus = LessonStatus::LEARNING;
                        $hasUncompletedPreceding = true;

                        if ($currentLessonId === null) {
                            $currentLessonId = $lessonId;
                        }

                        $durationSeconds = $lesson->duration_seconds ?? 0;
                        $watchedSeconds = $progressRecord ? (int) $progressRecord->current_watch_seconds : 0;
                        $lessonProgressPercent = $durationSeconds > 0
                            ? min(100, (int) round(($watchedSeconds / $durationSeconds) * 100))
                            : 0;
                    } else {
                        $lessonStatus = LessonStatus::LOCKED;
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

                $totalLessons = $course->lessons->count();
                $overallPercent = $enrollment ? (float) $enrollment->progress_percent : 0.00;

                $lessonIds = $course->lessons->pluck('id')->toArray();
                $quizQuestions = $this->quizRepository->getByLessonIds($lessonIds);

                $hasQuiz = $quizQuestions->isNotEmpty();
                $isPassed = false;
                $lastScore = null;
                $passingScore = 8;
                $canStart = false;
                $quizStatus = 'not_started';
                $quizActionText = 'Làm bài kiểm tra';

                if ($hasQuiz) {
                    $questionIds = $quizQuestions->pluck('id')->toArray();
                    $totalQuestionsCount = $quizQuestions->count();
                    $canStart = ($completedCount === $totalLessons);

                    $attemptsCount = $this->quizAttemptRepository->countAttemptsByUserAndQuizIds($dto->userId, $questionIds);
                    if ($attemptsCount > 0 && $totalQuestionsCount > 0) {
                        $hasUngraded = $this->quizAttemptRepository->hasUngradedAttempts($dto->userId, $questionIds);
                        if ($hasUngraded) {
                            $quizStatus = 'grading';
                            $quizActionText = 'Đang chấm bài';
                        } else {
                            $correctAttemptsCount = $this->quizAttemptRepository->countCorrectByUserAndQuizIds($dto->userId, $questionIds);
                            $lastScore = round(($correctAttemptsCount / $totalQuestionsCount) * 10, 1);
                            $isPassed = $lastScore >= $passingScore;

                            if ($isPassed) {
                                $quizStatus = 'passed';
                                $quizActionText = 'Xem lại';
                            } else {
                                $quizStatus = 'failed';
                                $quizActionText = 'Chưa đạt';
                            }
                        }
                    }
                }

                $enrollmentStatusStr = match (true) {
                    $enrollment === null                                           => 'not_started',
                    $enrollment->status === CourseEnrollmentStatus::COMPLETED     => 'completed',
                    $enrollment->status === CourseEnrollmentStatus::IN_PROGRESS   => 'in_progress',
                    default                                                        => 'not_started',
                };

                return [
                    'id'           => (string) $course->id,
                    'title'        => $course->title,
                    'label'        => 'QUY TRÌNH HỌC TỰ CNTR',
                    'description'  => $course->description,
                    'thumbnailUrl' => $course->thumbnail,
                    'isMandatory'  => (bool) $course->is_required,
                    'learningRule' => [
                        'type'                  => 'sequential',
                        'canSkipLesson'         => false,
                        'requireWatchFullVideo' => true,
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
                        'status'       => $quizStatus,
                        'actionText'   => $quizActionText,
                        'isPassed'     => $isPassed,
                        'lastScore'    => $lastScore,
                        'passingScore' => $passingScore,
                        'canStart'     => $canStart,
                    ],
                    'canAccessPremiumLearning' => $enrollment?->status === CourseEnrollmentStatus::COMPLETED,
                    'notice' => [
                        'type'    => 'warning',
                        'message' => 'Bạn cần xem hết thời lượng video trước khi chuyển sang bài tiếp theo. Hệ thống sẽ tự động ghi nhận tiến độ.',
                    ],
                    'lessons' => $mappedLessons,
                ];
            };

            $mappedCourses = $courses->map($mapCourse)->values();
            $coursePayload = $mappedCourses->first();

            $result = [
                'course' => $coursePayload,
                'courses' => $mappedCourses->all(),
            ];

            return $this->success(
                data: $result,
                message: 'Tải khóa học bắt buộc thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải thông tin khóa học.',
                code: 500
            );
        });
    }

    public function getCourseDetails(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            if (!$enrollment) {
                $enrollment = $this->courseEnrollmentRepository->create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => CourseEnrollmentStatus::IN_PROGRESS,
                    'progress_percent' => 0.00,
                ]);
            }

            $completedLessons = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);

            $lessons = $course->lessons;
            $mappedLessons = [];
            $hasUncompletedPreceding = false;

            foreach ($lessons as $lesson) {
                $isCompleted = in_array($lesson->id, $completedLessons);

                if ($isCompleted) {
                    $lessonStatus = LessonStatus::COMPLETED;
                } else {
                    if (!$hasUncompletedPreceding) {
                        $lessonStatus = LessonStatus::LEARNING;
                        $hasUncompletedPreceding = true;
                    } else {
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

    public function getLessonDetails(string $lessonId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            if (!$enrollment) {
                $enrollment = $this->courseEnrollmentRepository->create([
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'status' => CourseEnrollmentStatus::IN_PROGRESS,
                    'progress_percent' => 0.00,
                ]);
            }

            $progressRecord = $this->courseEnrollmentRepository->getLessonProgressRecord($enrollment->id, $lesson->id);
            $currentWatchSeconds = $progressRecord ? $progressRecord->current_watch_seconds : 0;

            $completedLessons = $this->courseEnrollmentRepository->getCompletedLessonIds($enrollment->id);

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

                if ($foundCurrent && $nextLesson === null) {
                    $nextLesson = $item;
                }
            }

            $this->validate($targetStatus !== LessonStatus::LOCKED, 'Vui lòng hoàn thành bài học trước để mở khóa.', 403);

            if ($nextLesson !== null) {
                $unlockCondition = 'Hoàn thành bài học này để mở khóa bài tiếp theo: ' . $nextLesson->title;
            } else {
                $unlockCondition = 'Hoàn thành bài học này để hoàn thành khóa học.';
            }

            $attachments = $lesson->attachments ?? [];
            $attachmentMessage = empty($attachments) ? 'Không có tài liệu đính kèm.' : null;

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
                        $statusLabel = $score >= 8.0 ? 'Xem lại' : 'Chưa đạt';
                    }
                }
            }

            $result = [
                'id' => (string) $lesson->id,
                'course_id' => (string) $lesson->course_id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'description' => $lesson->content,
                'video_url' => $lesson->video_url,
                'duration_seconds' => $lesson->duration_seconds,
                'order' => $lesson->order,
                'status' => strtolower($targetStatus->name),
                'status_label' => $statusLabel,
                'attachments' => $attachments,
                'attachment_message' => $attachmentMessage,
                'current_watch_seconds' => $currentWatchSeconds,
                'unlock_condition' => $unlockCondition,
                'next_lesson_id' => $nextLesson ? (string) $nextLesson->id : null,
            ];

            return $this->success(
                data: $result,
                message: 'Tải thông tin bài học thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải thông tin bài học.',
                code: 500
            );
        });
    }

    public function updateLessonProgress(string $lessonId, int $watchTimeSeconds, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($lessonId, $watchTimeSeconds, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $lesson = $this->courseRepository->findLesson($lessonId);
            $this->validate($lesson !== null, 'Bài học không tồn tại.', 404);

            $course = $this->courseRepository->getCourseDetails($lesson->course_id, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $this->validate(!empty($lesson->video_url), 'Video hiện không khả dụng.', 400);

            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

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

            $requiredSeconds = $lesson->duration_seconds ?? 0;

            if ($requiredSeconds > 0 && $watchTimeSeconds > $requiredSeconds) {
                $watchTimeSeconds = $requiredSeconds;
            }

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

            if ($newlyCompleted) {
                $lessons = $course->lessons;
                $totalLessonsCount = $lessons->count();

                if ($totalLessonsCount > 0) {
                    $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);

                    $progressPercent = round(($completedLessonsCount / $totalLessonsCount) * 100, 2);
                    $enrollment->progress_percent = $progressPercent;

                    $terminalStatuses = [
                        CourseEnrollmentStatus::COMPLETED,
                        CourseEnrollmentStatus::PENDING_ONBOARDING,
                        CourseEnrollmentStatus::PENDING_GRADING,
                    ];
                    if (!in_array($enrollment->status, $terminalStatuses, true)) {
                        if ($completedLessonsCount === $totalLessonsCount) {
                            if ($course->is_required) {
                                $enrollment->status = CourseEnrollmentStatus::IN_PROGRESS;
                            } else {
                                $enrollment->status = CourseEnrollmentStatus::COMPLETED;
                                $enrollment->completed_at = now();
                            }
                        } else {
                            $enrollment->status = CourseEnrollmentStatus::IN_PROGRESS;
                        }
                    }

                    $enrollment->save();
                }
            }

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
            return ServiceReturn::error(
                message: 'Không thể cập nhật tiến độ học tập.',
                code: 500
            );
        });
    }

    public function completeCourse(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            if ($enrollment->status === CourseEnrollmentStatus::COMPLETED) {
                return $this->success(
                    data: $enrollment,
                    message: 'Bạn đã hoàn thành khóa học.'
                );
            }

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

            $lastLesson = $lessons->sortByDesc('order')->first();
            if ($lastLesson !== null) {
                $quizQuestions = $this->quizRepository->getByLessonId($lastLesson->id);
                if ($quizQuestions->isNotEmpty()) {
                    $questionIds = $quizQuestions->pluck('id')->toArray();
                    $correctAttemptsCount = $this->quizAttemptRepository
                        ->countCorrectByUserAndQuizIds($userId, $questionIds);

                    $totalQuestionsCount = $quizQuestions->count();
                    $score = ($correctAttemptsCount / $totalQuestionsCount) * 10;

                    if ($score < 8.00) {
                        return ServiceReturn::error(
                            message: 'Bạn chưa đạt điểm yêu cầu để hoàn thành khóa học.',
                            code: 403
                        );
                    }
                }
            }

            $allLessonIds = $lessons->pluck('id')->toArray();
            $allQuizIds = $this->quizRepository->getByLessonIds($allLessonIds)->pluck('id')->toArray();
            if ($allQuizIds !== []) {
                $ungradedCount = $this->quizAttemptRepository->countUngradedEssaysByUserAndQuizIds($userId, $allQuizIds);
                if ($ungradedCount > 0) {
                    return ServiceReturn::error(
                        message: 'Vui lòng chờ quản lý chấm xong câu tự luận trước khi hoàn thành khóa học.',
                        code: 422
                    );
                }
            }

            $enrollment->progress_percent = 100.00;

            if ($course->is_required) {
                $enrollment->status = CourseEnrollmentStatus::PENDING_ONBOARDING;
            } else {
                $enrollment->status = CourseEnrollmentStatus::COMPLETED;
                $enrollment->completed_at = now();
            }

            $enrollment->save();

            $message = $course->is_required
                ? 'Bạn đã hoàn thành bài quiz. Vui lòng chờ quản lý duyệt hoàn thành khóa học.'
                : 'Bạn đã hoàn thành khóa học.';

            return $this->success(
                data: $enrollment,
                message: $message
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể cập nhật trạng thái khóa học.',
                code: 500
            );
        });
    }
}
