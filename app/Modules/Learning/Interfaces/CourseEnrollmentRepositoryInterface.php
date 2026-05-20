<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Learning\Models\CourseEnrollment;
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
     * @param string $userId
     * @param string $courseId
     * @return CourseEnrollment|null
     */
    public function findByUserAndCourse(string $userId, string $courseId): ?CourseEnrollment;

    /**
     * Lấy danh sách tiến độ bài học của một enrollment.
     *
     * @param string $enrollmentId
     * @return Collection
     */
    public function getLessonProgress(string $enrollmentId): Collection;

    /**
     * Tạo bản ghi tiến độ bài học cho một enrollment.
     *
     * @param string $enrollmentId
     * @param string $lessonId
     * @param bool $isCompleted
     * @return mixed
     */
    public function createLessonProgress(string $enrollmentId, string $lessonId, bool $isCompleted = false);
}
