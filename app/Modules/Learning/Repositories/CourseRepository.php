<?php

namespace App\Modules\Learning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Models\Course;
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
                $q->where('is_required', true);

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

    public function findLesson(string $lessonId): ?\App\Modules\Learning\Models\CourseLesson
    {
        return \App\Modules\Learning\Models\CourseLesson::find($lessonId);
    }

    /**
     * Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học.
     *
     * @param string $lessonId
     * @return Collection
     */
    public function getQuizQuestions(string $lessonId): Collection
    {
        return \App\Modules\Learning\Models\CourseQuiz::where('lesson_id', $lessonId)->get();
    }
}
