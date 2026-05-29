<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\LessonProgress;
use Illuminate\Support\Collection;

/**
 * Interface CourseEnrollmentRepositoryInterface
 *
 * Định nghĩa các phương thức nghiệp vụ CSDL cho CourseEnrollment và LessonProgress.
 *
 * @package App\Modules\Learning\Interfaces
 */
interface CourseEnrollmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm kiếm thông tin enrollment của user cho một khóa học cụ thể.
     *
     * @param string $userId ID của người dùng
     * @param string $courseId ID của khóa học
     * @return CourseEnrollment|null Bản ghi enrollment hoặc null nếu không tìm thấy
     */
    public function findByUserAndCourse(string $userId, string $courseId): ?CourseEnrollment;

    /**
     * Lấy toàn bộ danh sách tiến độ bài học của một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @return Collection Tập hợp các bản ghi LessonProgress
     */
    public function getLessonProgress(string $enrollmentId): Collection;

    /**
     * Tạo bản ghi tiến độ bài học cho một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @param string $lessonId ID của bài học
     * @param bool $isCompleted Trạng thái hoàn thành
     * @return mixed Bản ghi LessonProgress vừa tạo
     */
    public function createLessonProgress(string $enrollmentId, string $lessonId, bool $isCompleted = false);

    /**
     * Lấy bản ghi tiến độ của một bài học cụ thể trong enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @param string $lessonId ID của bài học cần tra cứu
     * @return LessonProgress|null Bản ghi LessonProgress hoặc null nếu không tìm thấy
     */
    public function getLessonProgressRecord(string $enrollmentId, string $lessonId): ?LessonProgress;

    /**
     * Lấy danh sách lesson_id đã hoàn thành trong một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @return array Mảng các lesson_id (dạng string) đã hoàn thành
     */
    public function getCompletedLessonIds(string $enrollmentId): array;

    /**
     * Đếm số bài học đã hoàn thành trong một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @return int Số lượng bài học đã hoàn thành
     */
    public function countCompletedLessons(string $enrollmentId): int;

    public function getRequiredCourseOnboardingEnrollments(array $filters): Collection;
}
