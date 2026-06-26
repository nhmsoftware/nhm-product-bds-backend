<?php

declare(strict_types=1);

namespace App\Modules\Learning\Services;

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
use App\Modules\Learning\Interfaces\LearningServiceInterface;

/**
 * Thin delegator — routes each call to the appropriate sub-service.
 * The public API (LearningServiceInterface) is unchanged so all callers remain intact.
 */
final class LearningService implements LearningServiceInterface
{
    public function __construct(
        private readonly EmployeeLearningService $employeeLearningService,
        private readonly CourseQuizService $courseQuizService,
        private readonly CertificateService $certificateService,
        private readonly AdminCourseService $adminCourseService,
    ) {
    }

    // ── Employee methods ──────────────────────────────────────

    public function getMandatoryCourses(ViewCoursesDTO $dto): ServiceReturn
    {
        return $this->employeeLearningService->getMandatoryCourses($dto);
    }

    public function getCourseDetails(string $courseId, string $userId): ServiceReturn
    {
        return $this->employeeLearningService->getCourseDetails($courseId, $userId);
    }

    public function getLessonDetails(string $lessonId, string $userId): ServiceReturn
    {
        return $this->employeeLearningService->getLessonDetails($lessonId, $userId);
    }

    public function updateLessonProgress(string $lessonId, int $watchTimeSeconds, string $userId): ServiceReturn
    {
        return $this->employeeLearningService->updateLessonProgress($lessonId, $watchTimeSeconds, $userId);
    }

    public function completeCourse(string $courseId, string $userId): ServiceReturn
    {
        return $this->employeeLearningService->completeCourse($courseId, $userId);
    }

    // ── Quiz methods ──────────────────────────────────────────

    public function getCourseQuiz(string $courseId, string $userId): ServiceReturn
    {
        return $this->courseQuizService->getCourseQuiz($courseId, $userId);
    }

    public function submitCourseQuiz(string $courseId, array $answers, bool $isTimeout, string $userId): ServiceReturn
    {
        return $this->courseQuizService->submitCourseQuiz($courseId, $answers, $isTimeout, $userId);
    }

    public function getQuizResult(string $courseId, string $userId): ServiceReturn
    {
        return $this->courseQuizService->getQuizResult($courseId, $userId);
    }

    public function saveCourseQuizDraft(string $courseId, string $attemptId, int $remainingSeconds, array $answers, string $userId): ServiceReturn
    {
        return $this->courseQuizService->saveCourseQuizDraft($courseId, $attemptId, $remainingSeconds, $answers, $userId);
    }

    public function gradeEssayAttempt(string $attemptId, bool $isCorrect, string $gradedBy): ServiceReturn
    {
        return $this->courseQuizService->gradeEssayAttempt($attemptId, $isCorrect, $gradedBy);
    }

    // ── Certificate methods ───────────────────────────────────

    public function getCertificate(string $courseId, string $userId): ServiceReturn
    {
        return $this->certificateService->getCertificate($courseId, $userId);
    }

    public function downloadCertificate(string $courseId, string $userId): ServiceReturn
    {
        return $this->certificateService->downloadCertificate($courseId, $userId);
    }

    // ── Admin methods ─────────────────────────────────────────

    public function adminGetCourses(AdminViewCoursesDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminGetCourses($dto, $adminId);
    }

    public function adminGetCourseDetails(string $courseId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminGetCourseDetails($courseId, $adminId);
    }

    public function adminCreateCourse(AdminCreateCourseDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminCreateCourse($dto, $adminId);
    }

    public function adminUpdateCourse(string $courseId, AdminUpdateCourseDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminUpdateCourse($courseId, $dto, $adminId);
    }

    public function adminUpdateCourseStatus(string $courseId, AdminUpdateCourseStatusDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminUpdateCourseStatus($courseId, $dto, $adminId);
    }

    public function adminCreateCourseQuiz(string $courseId, AdminCreateCourseQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminCreateCourseQuiz($courseId, $dto, $adminId);
    }

    public function adminUpdateCourseQuiz(string $courseId, AdminUpdateCourseQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminUpdateCourseQuiz($courseId, $dto, $adminId);
    }

    public function adminDeleteCourseQuiz(string $courseId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminDeleteCourseQuiz($courseId, $adminId);
    }

    public function adminDeleteCourse(string $courseId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminDeleteCourse($courseId, $adminId);
    }

    public function adminCreateLesson(AdminCreateLessonDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminCreateLesson($dto, $adminId);
    }

    public function adminUpdateLesson(string $lessonId, AdminUpdateLessonDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminUpdateLesson($lessonId, $dto, $adminId);
    }

    public function adminDeleteLesson(string $lessonId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminDeleteLesson($lessonId, $adminId);
    }

    public function adminCreateQuiz(AdminCreateQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminCreateQuiz($dto, $adminId);
    }

    public function adminUpdateQuiz(string $quizId, AdminUpdateQuizDTO $dto, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminUpdateQuiz($quizId, $dto, $adminId);
    }

    public function adminDeleteQuiz(string $quizId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminDeleteQuiz($quizId, $adminId);
    }

    public function adminConfirmOnboarding(string $courseId, string $userId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminConfirmOnboarding($courseId, $userId, $adminId);
    }

    public function adminGetOnboardingList(array $filters, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminGetOnboardingList($filters, $adminId);
    }

    public function adminGetOnboardingDetail(string $courseId, string $userId, string $adminId): ServiceReturn
    {
        return $this->adminCourseService->adminGetOnboardingDetail($courseId, $userId, $adminId);
    }
}
