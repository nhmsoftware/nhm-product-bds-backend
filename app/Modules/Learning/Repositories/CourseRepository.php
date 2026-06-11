<?php

namespace App\Modules\Learning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use Illuminate\Support\Collection;

/**
 * Class CourseRepository
 *
 * Triển khai các phương thức truy vấn cơ sở dữ liệu cho Model Course.
 *
 * @package App\Modules\Learning\Repositories
 */
final class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    /**
     * Trả về tên class Model tương ứng.
     *
     * @return string
     */
    public function getModel(): string
    {
        return Course::class;
    }

    /**
     * Lấy danh sách khóa học bắt buộc được phân bổ theo phòng ban hoặc vị trí công việc của User.
     *
     * @param string $userId
     * @param string|null $department
     * @param string|null $jobPosition
     * @return Collection
     */
    public function getMandatoryCourses(string $userId, ?string $department, ?string $jobPosition): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->where(function ($q) use ($department, $jobPosition) {
                // Khóa học bắt buộc chung cho toàn bộ nhân viên mới
                $q->where('is_required', true)
                    // Hoặc khóa học tự chọn dùng chung cho toàn bộ nhân viên
                    ->orWhere(function ($query) {
                        $query->where('is_required', false)
                            ->whereNull('department')
                            ->whereNull('job_position');
                    });

                // Hoặc khóa học được phân bổ theo phòng ban
                if (!empty($department)) {
                    $q->orWhere('department', $department);
                }

                // Hoặc khóa học được phân bổ theo vị trí công việc
                if (!empty($jobPosition)) {
                    $q->orWhere('job_position', $jobPosition);
                }
            })
            ->with(['enrollments' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Lấy thông tin chi tiết của một khóa học kèm danh sách bài học và thông tin tiến độ của User.
     *
     * @param string $courseId
     * @param string $userId
     * @return Course|null
     */
    public function getCourseDetails(string $courseId, string $userId): ?Course
    {
        return $this->query()
            ->where('id', $courseId)
            ->where('is_active', true)
            ->with([
                'lessons' => function ($q) {
                    $q->orderBy('order', 'asc');
                },
                'enrollments' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])
            ->first();
    }

    public function findLesson(string $lessonId): ?CourseLesson
    {
        return CourseLesson::find($lessonId);
    }

    /**
     * Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học.
     *
     * @param string $lessonId
     * @return Collection
     */
    public function getQuizQuestions(string $lessonId): Collection
    {
        return CourseQuiz::where('lesson_id', $lessonId)->get();
    }

    /**
     * Tải danh sách khóa học cho Admin kèm tìm kiếm và lọc.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchAndFilter(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->query()->withCount('lessons');

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_required']) && $filters['is_required'] !== null) {
            $query->where('is_required', $filters['is_required']);
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['job_position'])) {
            $query->where('job_position', $filters['job_position']);
        }

        return $query->orderBy('order', 'asc')
            ->paginate($perPage);
    }

    /**
     * Lấy chi tiết khóa học cho Admin (không lọc theo is_active).
     *
     * @param string $courseId
     * @return Course|null
     */
    public function getCourseDetailsForAdmin(string $courseId): ?Course
    {
        return $this->query()
            ->where('id', $courseId)
            ->with([
                'lessons' => function ($q) {
                    $q->orderBy('order', 'asc');
                },
                'lessons.quizzes'
            ])
            ->first();
    }
}
