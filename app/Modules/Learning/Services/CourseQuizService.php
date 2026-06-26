<?php

declare(strict_types=1);

namespace App\Modules\Learning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseQuizRepositoryInterface;
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;

final class CourseQuizService extends BaseService
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepository,
        protected CourseEnrollmentRepositoryInterface $courseEnrollmentRepository,
        protected CourseQuizRepositoryInterface $quizRepository,
        protected AuthRepositoryInterface $authRepository,
        protected QuizAttemptRepositoryInterface $quizAttemptRepository,
    ) {
    }

    public function getCourseQuiz(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);
            $this->validate($completedLessonsCount === $lessons->count(), 'Bạn cần hoàn thành tất cả bài học trước khi làm quiz.', 403);

            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            $questionIds = $questions->pluck('id')->toArray();
            $draftAttempts = $this->quizAttemptRepository
                ->getDraftsByUserAndQuizIds($userId, $questionIds)
                ->keyBy('quiz_id');

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

            if (!$enrollment->quiz_attempt_id) {
                $enrollment->quiz_attempt_id = (string) \Illuminate\Support\Str::uuid();
                $enrollment->quiz_status = 'in_progress';
                $enrollment->quiz_started_at = now();
                $enrollment->quiz_expires_at = now()->addMinutes(45);
                $enrollment->quiz_remaining_seconds = 45 * 60;
                $enrollment->save();
            }

            return $this->success(
                data: [
                    'course_id' => (string) $course->id,
                    'course_title' => $course->title,
                    'quiz_title' => 'Bài kiểm tra kiến thức',
                    'time_limit_minutes' => 45,
                    'attempt' => [
                        'id' => $enrollment->quiz_attempt_id,
                        'status' => $enrollment->quiz_status ?? 'in_progress',
                        'started_at' => $enrollment->quiz_started_at ? $enrollment->quiz_started_at->toIso8601String() : null,
                        'expires_at' => $enrollment->quiz_expires_at ? $enrollment->quiz_expires_at->toIso8601String() : null,
                        'remaining_seconds' => $enrollment->quiz_remaining_seconds ?? (45 * 60),
                        'last_saved_at' => $enrollment->quiz_last_saved_at ? $enrollment->quiz_last_saved_at->toIso8601String() : null,
                    ],
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

    public function submitCourseQuiz(string $courseId, array $answers, bool $isTimeout, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $answers, $isTimeout, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);
            $this->validate($completedLessonsCount === $lessons->count(), 'Bạn cần hoàn thành tất cả bài học trước khi làm quiz.', 403);

            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

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
                    if (is_array($content)) {
                        $mappedOptions[] = [
                            'value' => isset($content['value']) ? (int) $content['value'] : $idx + 1,
                            'label' => $content['label'] ?? ''
                        ];
                    } else {
                        $mappedOptions[] = [
                            'value' => $idx + 1,
                            'label' => (string) $content
                        ];
                    }
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

            $maxScore = 10.0;
            if ($multipleChoiceCount > 0) {
                $score = round(($correctCount / $multipleChoiceCount) * $maxScore, 2);
            } else {
                $score = $maxScore;
            }
            $passingScore = 8.0;
            $isPassed = $score >= $passingScore;

            $message = $isPassed ? 'Chúc mừng! Bạn đã hoàn thành bài quiz đạt yêu cầu.' : 'Rất tiếc! Bạn chưa đạt điểm yêu cầu của bài quiz.';

            $enrollment->quiz_attempt_id = null;
            $enrollment->quiz_status = null;
            $enrollment->quiz_started_at = null;
            $enrollment->quiz_expires_at = null;
            $enrollment->quiz_remaining_seconds = null;
            $enrollment->quiz_last_saved_at = null;

            $hasEssayQuestions = $questions->contains(
                fn ($q) => ($q->type ?? '') === \App\Modules\Learning\Models\Enums\CourseQuizType::ESSAY->value
            );
            if ($hasEssayQuestions) {
                $enrollment->status = CourseEnrollmentStatus::PENDING_GRADING;
            }

            $enrollment->save();

            $responsePayload = [
                'status' => $isPassed ? 'passed' : 'failed',
                'score' => $score,
                'max_score' => $maxScore,
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
            return ServiceReturn::error(
                message: 'Không thể nộp bài kiểm tra.',
                code: 500
            );
        });
    }

    public function getQuizResult(string $courseId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            $questionIds = $questions->pluck('id')->toArray();
            $attempts = $this->quizAttemptRepository->getAttemptsByUserAndQuizIds($userId, $questionIds);

            $this->validate($attempts->isNotEmpty(), 'Bạn chưa nộp bài kiểm tra.', 404);

            $hasUngraded = $this->quizAttemptRepository->hasUngradedAttempts($userId, $questionIds);
            if ($hasUngraded) {
                return $this->success(
                    data: ['status' => 'grading'],
                    message: 'Bài thi đang được chấm.'
                );
            }

            $correctCount = 0;
            $multipleChoiceCount = 0;
            $totalCount = $questions->count();
            $details = [];

            $attemptMap = $attempts->keyBy('quiz_id');

            foreach ($questions as $q) {
                $attempt = $attemptMap->get($q->id);
                $type = $q->type ?? \App\Modules\Learning\Models\Enums\CourseQuizType::MULTIPLE_CHOICE->value;
                $isCorrect = $attempt ? $attempt->is_correct : null;

                if ($type === \App\Modules\Learning\Models\Enums\CourseQuizType::MULTIPLE_CHOICE->value) {
                    $multipleChoiceCount++;
                    if ($isCorrect) {
                        $correctCount++;
                    }
                }

                $mappedOptions = [];
                foreach ($q->options ?? [] as $idx => $content) {
                    if (is_array($content)) {
                        $mappedOptions[] = [
                            'value' => isset($content['value']) ? (int) $content['value'] : $idx + 1,
                            'label' => $content['label'] ?? ''
                        ];
                    } else {
                        $mappedOptions[] = [
                            'value' => $idx + 1,
                            'label' => (string) $content
                        ];
                    }
                }

                $details[] = [
                    'quiz_id' => (string) $q->id,
                    'type' => $type,
                    'order' => $q->order ?? 1,
                    'title' => $q->title,
                    'question' => $q->question,
                    'options' => $mappedOptions,
                    'selected_option' => $attempt ? $attempt->selected_option : null,
                    'essay_answer' => $attempt ? $attempt->essay_answer : null,
                    'correct_option' => $type === \App\Modules\Learning\Models\Enums\CourseQuizType::ESSAY->value ? null : (int) $q->correct_option,
                    'is_correct' => $isCorrect,
                ];
            }

            $maxScore = 10.0;
            if ($multipleChoiceCount > 0) {
                $score = round(($correctCount / $multipleChoiceCount) * $maxScore, 2);
            } else {
                $score = $maxScore;
            }
            $passingScore = 8.0;
            $isPassed = $score >= $passingScore;
            $status = $isPassed ? 'passed' : 'failed';

            $responsePayload = [
                'status' => $status,
                'score' => $score,
                'max_score' => $maxScore,
                'correct_count' => $correctCount,
                'total_questions' => $totalCount,
                'is_passed' => $isPassed,
                'passing_score' => $passingScore,
                'details' => $details,
            ];

            return $this->success(
                data: $responsePayload,
                message: 'Tải kết quả bài làm thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải kết quả bài làm.',
                code: 500
            );
        });
    }

    public function saveCourseQuizDraft(string $courseId, string $attemptId, int $remainingSeconds, array $answers, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $attemptId, $remainingSeconds, $answers, $userId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            $course = $this->courseRepository->getCourseDetails($courseId, $userId);
            $this->validate($course !== null, 'Không tìm thấy khóa học bắt buộc.', 404);

            $enrollment = $course->enrollments->first();
            $this->validate($enrollment !== null, 'Bạn chưa tham gia khóa học này.', 403);

            $lessons = $course->lessons;
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $completedLessonsCount = $this->courseEnrollmentRepository->countCompletedLessons($enrollment->id);
            $this->validate($completedLessonsCount === $lessons->count(), 'Bạn cần hoàn thành tất cả bài học trước khi làm quiz.', 403);

            $lessonIds = $lessons->pluck('id')->toArray();
            $questions = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($questions->isNotEmpty(), 'Bài quiz không khả dụng.', 404);

            $hasData = false;
            $submittedMap = [];
            foreach ($answers as $ans) {
                if (isset($ans['quiz_id'])) {
                    $submittedMap[(string) $ans['quiz_id']] = [
                        'selected_option' => isset($ans['selected_option']) ? (int) $ans['selected_option'] : null,
                        'essay_answer' => $ans['essay_answer'] ?? null,
                    ];
                    $hasData = true;
                }
            }

            if (!$hasData) {
                return ServiceReturn::error(
                    message: 'Không có dữ liệu để lưu.',
                    code: 422
                );
            }

            $questionIds = $questions->pluck('id')->toArray();
            $this->quizAttemptRepository->deleteByUserAndQuizIds($userId, $questionIds);

            foreach ($questions as $q) {
                $ansData = $submittedMap[(string) $q->id] ?? null;
                if ($ansData !== null) {
                    $this->quizAttemptRepository->create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'user_id' => $userId,
                        'quiz_id' => $q->id,
                        'selected_option' => $ansData['selected_option'],
                        'essay_answer' => $ansData['essay_answer'],
                        'is_correct' => null,
                        'is_draft' => true,
                    ]);
                }
            }

            $enrollment->quiz_attempt_id = $attemptId;
            $enrollment->quiz_remaining_seconds = $remainingSeconds;
            $enrollment->quiz_last_saved_at = now();
            if (empty($enrollment->quiz_started_at)) {
                $enrollment->quiz_started_at = now();
            }
            $enrollment->save();

            return $this->success(
                data: [
                    'attempt_id' => $attemptId,
                    'status' => 'draft',
                    'remaining_seconds' => $remainingSeconds,
                    'last_saved_at' => $enrollment->quiz_last_saved_at->toIso8601String(),
                ],
                message: 'Lưu bản nháp thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể lưu bản nháp.',
                code: 500
            );
        });
    }

    public function gradeEssayAttempt(string $attemptId, bool $isCorrect, string $gradedBy): ServiceReturn
    {
        return $this->execute(function () use ($attemptId, $isCorrect, $gradedBy) {
            $attempt = $this->quizAttemptRepository->find($attemptId);
            $this->validate($attempt !== null, 'Không tìm thấy bài làm.', 404);
            $this->validate($attempt->is_correct === null, 'Bài làm này đã được chấm điểm rồi.', 422);
            $this->validate($attempt->is_draft === false, 'Bài làm này chưa được nộp.', 422);

            $attempt->is_correct = $isCorrect;
            $attempt->graded_by = $gradedBy;
            $attempt->graded_at = now();
            $attempt->save();

            $quiz = $attempt->quiz()->with('lesson')->first();
            if ($quiz === null || $quiz->lesson === null) {
                return $this->success(data: $attempt, message: 'Chấm bài thành công.');
            }

            $lessonId = $quiz->lesson->id;
            $allLessonQuizIds = $this->quizRepository->getByLessonId($lessonId)->pluck('id')->toArray();

            $ungradedCount = $this->quizAttemptRepository
                ->countUngradedEssaysByUserAndQuizIds($attempt->user_id, $allLessonQuizIds);

            if ($ungradedCount === 0) {
                $courseId = $quiz->lesson->course_id ?? null;
                if ($courseId !== null) {
                    app(EmployeeLearningService::class)->completeCourse($courseId, $attempt->user_id);
                }
            }

            return $this->success(data: $attempt, message: 'Chấm bài thành công.');
        }, useTransaction: true);
    }
}
