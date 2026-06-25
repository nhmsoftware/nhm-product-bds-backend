<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Learning\Models\Course;
use Illuminate\Support\Collection;

/**
 * Interface CourseRepositoryInterface
 *
 * Định nghĩa các phương thức truy vấn cơ sở dữ liệu liên quan đến Khóa học.
 *
 * @package App\Modules\Learning\Interfaces
 */
interface CourseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách khóa học bắt buộc được phân bổ theo phòng ban hoặc vị trí công việc của User.
     *
     * @param string $userId ID của người dùng (nhân viên)
     * @param string|null $department Phòng ban của nhân viên
     * @param string|null $jobPosition Vị trí công việc của nhân viên
     * @return Collection Danh sách khóa học kèm mối quan hệ enrollment của user đó (nếu có)
     */
    public function getMandatoryCourses(string $userId, ?string $department, ?string $jobPosition, ?int $role): Collection;

    /**
     * Lấy thông tin chi tiết của một khóa học kèm danh sách bài học và thông tin tiến độ của User.
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID của người dùng (nhân viên)
     * @return Course|null Đối tượng Course hoặc null nếu không tồn tại hoặc không active
     */
    public function getCourseDetails(string $courseId, string $userId): ?Course;

    /**
     * Tìm bài học theo ID.
     *
     * @param string $lessonId
     * @return \App\Modules\Learning\Models\CourseLesson|null
     */
    public function findLesson(string $lessonId): ?\App\Modules\Learning\Models\CourseLesson;

    /**
     * Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học.
     *
     * @param string $lessonId
     * @return Collection
     */
    public function getQuizQuestions(string $lessonId): Collection;

    /**
     * Tải danh sách khóa học cho Admin kèm tìm kiếm và lọc.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchAndFilter(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Lấy chi tiết khóa học cho Admin (không lọc theo is_active).
     *
     * @param string $courseId
     * @return Course|null
     */
    public function getCourseDetailsForAdmin(string $courseId): ?Course;
}
