<?php

namespace App\Modules\Learning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\LessonProgress;
use Illuminate\Support\Collection;

/**
 * Class CourseEnrollmentRepository
 *
 * Triển khai các phương thức truy vấn cơ sở dữ liệu cho Model CourseEnrollment.
 *
 * @package App\Modules\Learning\Repositories
 */
final class CourseEnrollmentRepository extends BaseRepository implements CourseEnrollmentRepositoryInterface
{
    /**
     * Trả về tên class Model tương ứng.
     *
     * @return string
     */
    public function getModel(): string
    {
        return CourseEnrollment::class;
    }

    /**
     * Tìm kiếm thông tin enrollment của user cho một khóa học cụ thể.
     *
     * @param string $userId
     * @param string $courseId
     * @return CourseEnrollment|null
     */
    public function findByUserAndCourse(string $userId, string $courseId): ?CourseEnrollment
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * Lấy danh sách tiến độ bài học của một enrollment.
     *
     * @param string $enrollmentId
     * @return Collection
     */
    public function getLessonProgress(string $enrollmentId): Collection
    {
        return LessonProgress::where('enrollment_id', $enrollmentId)->get();
    }

    /**
     * Tạo bản ghi tiến độ bài học cho một enrollment.
     *
     * @param string $enrollmentId
     * @param string $lessonId
     * @param bool $isCompleted
     * @return mixed
     */
    public function createLessonProgress(string $enrollmentId, string $lessonId, bool $isCompleted = false)
    {
        return LessonProgress::create([
            'enrollment_id' => $enrollmentId,
            'lesson_id' => $lessonId,
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);
    }
}
