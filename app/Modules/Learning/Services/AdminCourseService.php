<?php

declare(strict_types=1);

namespace App\Modules\Learning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
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
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;

final class AdminCourseService extends BaseService
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
                    'allowed_roles' => $course->allowed_roles,
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
                'allowed_roles' => $course->allowed_roles,
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

    public function adminCreateCourse(AdminCreateCourseDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($dto, $adminId) {
            $this->validateAdmin($adminId);

            $this->validate(!empty($dto->lessons), 'Vui lòng thêm ít nhất một bài học.', 422);

            $totalQuizzes = 0;
            foreach ($dto->lessons as $lessonData) {
                if (!empty($lessonData['quizzes'])) {
                    $totalQuizzes += count($lessonData['quizzes']);
                }
            }
            $this->validate($totalQuizzes > 0, 'Khóa học chưa có quiz. Vui lòng tạo quiz cho khóa học.', 422);

            $course = $this->courseRepository->create($dto->toArray());
            $this->validate($course !== null, 'Không thể tạo khóa học.', 500);

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

            $fullCourse = $this->courseRepository->getCourseDetailsForAdmin($course->id);
            return $this->success(data: $fullCourse, message: 'Tạo khóa học thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: $e->getCode() === 422 ? $e->getMessage() : 'Không thể tạo khóa học.',
                code: $e->getCode() === 422 ? 422 : 500
            );
        });
    }

    public function adminUpdateCourse(string $courseId, AdminUpdateCourseDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            $this->validateAdmin($adminId);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            $this->validate(!empty($dto->lessons), 'Vui lòng thêm ít nhất một bài học.', 422);

            $totalQuizzes = 0;
            foreach ($dto->lessons as $lessonData) {
                if (!empty($lessonData['quizzes'])) {
                    $totalQuizzes += count($lessonData['quizzes']);
                }
            }
            $this->validate($totalQuizzes > 0, 'Khóa học chưa có quiz. Vui lòng tạo quiz cho khóa học.', 422);

            $updated = $this->courseRepository->updateById($courseId, $dto->toArray());
            $this->validate($updated !== false, 'Không thể cập nhật khóa học.', 500);

            $existingLessons = $this->lessonRepository->getByCourseId($courseId);
            $existingLessonIds = $existingLessons->pluck('id')->toArray();

            $processedLessonIds = [];

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
                    $lesson = $this->lessonRepository->updateById($lessonId, $lessonPayload);
                    $this->validate($lesson !== false, 'Không thể cập nhật khóa học.', 500);
                    $processedLessonIds[] = $lessonId;
                } else {
                    $lesson = $this->lessonRepository->create($lessonPayload);
                    $this->validate($lesson !== null, 'Không thể cập nhật khóa học.', 500);
                    $lessonId = (string) $lesson->id;
                    $processedLessonIds[] = $lessonId;
                }

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
                            $quiz = $this->quizRepository->updateById($quizId, $quizPayload);
                            $this->validate($quiz !== false, 'Không thể cập nhật khóa học.', 500);
                            $processedQuizIds[] = $quizId;
                        } else {
                            $quiz = $this->quizRepository->create($quizPayload);
                            $this->validate($quiz !== null, 'Không thể cập nhật khóa học.', 500);
                            $processedQuizIds[] = (string) $quiz->id;
                        }
                    }
                }

                $quizzesToDelete = array_diff($existingQuizIds, $processedQuizIds);
                foreach ($quizzesToDelete as $delQuizId) {
                    $this->quizRepository->deleteById($delQuizId);
                }
            }

            $lessonsToDelete = array_diff($existingLessonIds, $processedLessonIds);
            foreach ($lessonsToDelete as $delLessonId) {
                $this->lessonRepository->deleteById($delLessonId);
            }

            $fullCourse = $this->courseRepository->getCourseDetailsForAdmin($courseId);
            return $this->success(data: $fullCourse, message: 'Cập nhật khóa học thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            return ServiceReturn::error(message: 'Không thể cập nhật khóa học.', code: 500);
        });
    }

    public function adminUpdateCourseStatus(string $courseId, AdminUpdateCourseStatusDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            $this->validateAdmin($adminId);

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
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            return ServiceReturn::error(message: 'Không thể cập nhật trạng thái khóa học.', code: 500);
        });
    }

    public function adminCreateCourseQuiz(string $courseId, AdminCreateCourseQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            $this->validateAdmin($adminId);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học để tạo quiz.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();

            $existingQuizCount = $this->quizRepository->countByLessonIds($lessonIds);
            $this->validate($existingQuizCount === 0, 'Khóa học đã có bài quiz.', 400);

            $this->validate($dto->passingScore >= 0 && $dto->passingScore <= 100, 'Điểm đạt yêu cầu không hợp lệ.', 422);

            $this->validate(!empty($dto->questions), 'Vui lòng thêm ít nhất một câu hỏi.', 422);

            $lastLesson = $lessons->sortByDesc('order')->first();
            $this->validate($lastLesson !== null, 'Không tìm thấy bài học cuối cùng.', 500);

            $createdQuizzes = [];
            foreach ($dto->questions as $q) {
                $optionsCount = isset($q['options']) ? count($q['options']) : 0;
                $this->validate(
                    $optionsCount >= 2 && isset($q['correct_option']) && $q['correct_option'] >= 1 && $q['correct_option'] <= $optionsCount,
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

            return ServiceReturn::error(message: 'Không thể tạo bài quiz.', code: 500);
        });
    }

    public function adminUpdateCourseQuiz(string $courseId, AdminUpdateCourseQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $dto, $adminId) {
            $this->validateAdmin($adminId);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();

            $existingQuizzes = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($existingQuizzes->isNotEmpty(), 'Quiz không tồn tại.', 404);

            $this->validate($dto->passingScore >= 0 && $dto->passingScore <= 100, 'Điểm đạt yêu cầu không hợp lệ.', 422);

            $this->validate(!empty($dto->questions), 'Vui lòng thêm ít nhất một câu hỏi.', 422);

            $lastLesson = $lessons->sortByDesc('order')->first();
            $this->validate($lastLesson !== null, 'Không tìm thấy bài học cuối cùng.', 500);

            $existingQuizIds = $existingQuizzes->pluck('id')->toArray();
            $processedQuizIds = [];

            foreach ($dto->questions as $q) {
                $optionsCount = isset($q['options']) ? count($q['options']) : 0;
                $this->validate(
                    $optionsCount >= 2 && isset($q['correct_option']) && $q['correct_option'] >= 1 && $q['correct_option'] <= $optionsCount,
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
                    $updated = $this->quizRepository->updateById($quizId, $quizPayload);
                    $this->validate($updated !== false, 'Không thể cập nhật bài quiz.', 500);
                    $processedQuizIds[] = $quizId;
                } else {
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

            $toDeleteQuizIds = array_diff($existingQuizIds, $processedQuizIds);
            if (!empty($toDeleteQuizIds)) {
                $this->quizRepository->deleteByIds($toDeleteQuizIds);
            }

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

            return ServiceReturn::error(message: 'Không thể cập nhật bài quiz.', code: 500);
        });
    }

    public function adminDeleteCourseQuiz(string $courseId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $adminId) {
            $this->validateAdmin($adminId);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Khóa học không tồn tại.', 404);

            $lessons = $this->lessonRepository->getByCourseId($courseId);
            $this->validate($lessons->isNotEmpty(), 'Khóa học chưa có bài học.', 400);

            $lessonIds = $lessons->pluck('id')->toArray();

            $existingQuizzes = $this->quizRepository->getByLessonIds($lessonIds);
            $this->validate($existingQuizzes->isNotEmpty(), 'Quiz không tồn tại.', 404);

            $quizIds = $existingQuizzes->pluck('id')->toArray();
            $attemptsCount = $this->quizAttemptRepository->countByQuizIds($quizIds);
            $this->validate($attemptsCount === 0, 'Không thể xóa quiz đã có nhân viên làm bài.', 400);

            $deleted = $this->quizRepository->deleteByIds($quizIds);
            $this->validate($deleted !== false, 'Không thể xóa quiz.', 500);

            return $this->success(
                data: null,
                message: 'Xóa quiz thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [400, 403, 404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            return ServiceReturn::error(message: 'Không thể xóa quiz.', code: 500);
        });
    }

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

    public function adminConfirmOnboarding(string $courseId, string $userId, string $adminId): ServiceReturn
    {
        return $this->execute(function () use ($courseId, $userId, $adminId) {
            $this->validateAdmin($adminId);

            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            $course = $this->courseRepository->find($courseId);
            $this->validate($course !== null, 'Không tìm thấy khóa học.', 404);

            $enrollment = $this->courseEnrollmentRepository->findByUserAndCourse($userId, $courseId);

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
                $incompleteLessons = $lessons->filter(function ($l) use ($completedLessonIds) {
                    return !in_array($l->id, $completedLessonIds);
                });

                $hasIncompleteVideo = $incompleteLessons->contains(function ($l) {
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

            $quizzes = $this->quizRepository->getByLessonIds($lessons->pluck('id'));
            if ($quizzes->isNotEmpty()) {
                $quizIds = $quizzes->pluck('id')->toArray();

                if ($this->quizAttemptRepository->hasUngradedAttempts($userId, $quizIds)) {
                    return ServiceReturn::error(
                        message: 'Nhân viên còn câu hỏi tự luận chưa được chấm. Vui lòng chấm bài trước khi duyệt onboarding.',
                        code: 400
                    );
                }

                $correctAttemptsCount = $this->quizAttemptRepository
                    ->countCorrectByUserAndQuizIds($userId, $quizIds);

                $totalQuestionsCount = $quizzes->count();
                if (($correctAttemptsCount / $totalQuestionsCount) < 0.8) {
                    return ServiceReturn::error(
                        message: 'Nhân viên chưa đạt điểm yêu cầu của bài quiz.',
                        code: 400
                    );
                }
            }

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
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) use ($courseId, $userId, $adminId) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if (in_array($code, [400, 403, 404, 422])) {
                return ServiceReturn::error(message: $message, code: $code);
            }

            \Illuminate\Support\Facades\Log::error('adminConfirmOnboarding failed', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'admin_id' => $adminId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Không thể cập nhật trạng thái onboarding. Chi tiết: ' . $e->getMessage(), code: 500);
        });
    }

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

            if (isset($filters['quiz_score'])) {
                $targetScore = (float) $filters['quiz_score'];
                $data = array_values(array_filter($data, function ($item) use ($targetScore) {
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
                    : \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::NOT_STARTED->serialize(),
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
