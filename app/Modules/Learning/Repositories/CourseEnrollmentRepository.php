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
 * Mọi bộ lọc (filter) đều phải được thực hiện tại đây, không được để lên tầng Service.
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
     * @param string $userId ID của người dùng
     * @param string $courseId ID của khóa học
     * @return CourseEnrollment|null Bản ghi enrollment hoặc null nếu không tìm thấy
     */
    public function findByUserAndCourse(string $userId, string $courseId): ?CourseEnrollment
    {
        return $this->query()
            ->withTrashed()  // Bao gồm cả bản ghi đã soft-delete để tránh duplicate constraint
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * Lấy toàn bộ danh sách tiến độ bài học của một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @return Collection Tập hợp các bản ghi LessonProgress
     */
    public function getLessonProgress(string $enrollmentId): Collection
    {
        return LessonProgress::where('enrollment_id', $enrollmentId)->get();
    }

    /**
     * Tạo bản ghi tiến độ bài học cho một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @param string $lessonId ID của bài học
     * @param bool $isCompleted Trạng thái hoàn thành
     * @return mixed Bản ghi LessonProgress vừa tạo
     */
    public function createLessonProgress(string $enrollmentId, string $lessonId, bool $isCompleted = false)
    {
        return LessonProgress::create([
            'enrollment_id' => $enrollmentId,
            'lesson_id'     => $lessonId,
            'is_completed'  => $isCompleted,
            'completed_at'  => $isCompleted ? now() : null,
        ]);
    }

    /**
     * Lấy bản ghi tiến độ của một bài học cụ thể trong enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @param string $lessonId ID của bài học cần tra cứu
     * @return LessonProgress|null Bản ghi LessonProgress hoặc null nếu không tìm thấy
     */
    public function getLessonProgressRecord(string $enrollmentId, string $lessonId): ?LessonProgress
    {
        return LessonProgress::where('enrollment_id', $enrollmentId)
            ->where('lesson_id', $lessonId)
            ->first();
    }

    /**
     * Lấy danh sách lesson_id đã hoàn thành trong một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @return array Mảng các lesson_id (dạng string) đã hoàn thành
     */
    public function getCompletedLessonIds(string $enrollmentId): array
    {
        return LessonProgress::where('enrollment_id', $enrollmentId)
            ->where('is_completed', true)
            ->pluck('lesson_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    /**
     * Đếm số bài học đã hoàn thành trong một enrollment.
     *
     * @param string $enrollmentId ID của enrollment
     * @return int Số lượng bài học đã hoàn thành
     */
    public function countCompletedLessons(string $enrollmentId): int
    {
        return LessonProgress::where('enrollment_id', $enrollmentId)
            ->where('is_completed', true)
            ->count();
    }

    public function getRequiredCourseOnboardingEnrollments(array $filters): Collection
    {
        $query = $this->model
            ->join('users', 'course_enrollments.user_id', '=', 'users.id')
            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
            ->where('courses.is_required', true)
            ->select('course_enrollments.*')
            ->with(['user', 'course']);

        if (!empty($filters['department'])) {
            $query->where('users.department', 'like', '%' . $filters['department'] . '%');
        }

        if (isset($filters['status'])) {
            $query->where('course_enrollments.status', $filters['status']);
        }

        return $query->get();
    }
}
