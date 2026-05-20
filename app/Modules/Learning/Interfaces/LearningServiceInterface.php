<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Learning\DTO\ViewCoursesDTO;

/**
 * Interface LearningServiceInterface
 *
 * Định nghĩa các phương thức nghiệp vụ xử lý học tập và khóa học.
 *
 * @package App\Modules\Learning\Interfaces
 */
interface LearningServiceInterface
{
    /**
     * Tải danh sách khóa học bắt buộc được phân bổ cho Employee (UC-053).
     *
     * @param ViewCoursesDTO $dto DTO chứa thông tin phòng ban, vị trí công việc
     * @return ServiceReturn Chứa danh sách khóa học và tiến độ hoàn thành
     */
    public function getMandatoryCourses(ViewCoursesDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin chi tiết một khóa học kèm tiến độ các bài học của Employee (UC-053).
     *
     * @param string $courseId ID khóa học cần xem
     * @param string $userId ID của nhân viên đang đăng nhập
     * @return ServiceReturn Chứa thông tin chi tiết khóa học, các bài học, thời lượng video và trạng thái từng bài học
     */
    public function getCourseDetails(string $courseId, string $userId): ServiceReturn;

    /**
     * Tải thông tin chi tiết của một bài học trong khóa học của Employee (UC-054).
     *
     * @param string $lessonId ID bài học cần xem
     * @param string $userId ID của nhân viên đang đăng nhập
     * @return ServiceReturn Chứa chi tiết bài học, trạng thái, tài liệu đính kèm và điều kiện mở khóa bài tiếp theo
     */
    public function getLessonDetails(string $lessonId, string $userId): ServiceReturn;

    /**
     * Cập nhật tiến độ xem video của bài học và tự động đánh giá hoàn thành (UC-055).
     *
     * @param string $lessonId ID bài học
     * @param int $watchTimeSeconds Thời lượng xem hiện tại (giây)
     * @param string $userId ID nhân viên
     * @return ServiceReturn Kết quả cập nhật, trạng thái hoàn thành và thông tin bài học tiếp theo nếu có
     */
    public function updateLessonProgress(string $lessonId, int $watchTimeSeconds, string $userId): ServiceReturn;

    /**
     * Lấy danh sách câu hỏi kiểm tra (Quiz) của bài học (UC-056).
     *
     * @param string $lessonId
     * @param string $userId
     * @return ServiceReturn
     */
    public function getLessonQuiz(string $lessonId, string $userId): ServiceReturn;

    /**
     * Nộp kết quả làm bài kiểm tra trắc nghiệm (UC-056).
     *
     * @param string $lessonId
     * @param array $answers Danh sách câu trả lời [{quiz_id, selected_option}]
     * @param bool $isTimeout True nếu tự động nộp do hết thời gian làm bài
     * @param string $userId
     * @return ServiceReturn
     */
    public function submitLessonQuiz(string $lessonId, array $answers, bool $isTimeout, string $userId): ServiceReturn;

    /**
     * Ghi nhận nhân viên hoàn thành khóa học (UC-057).
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function completeCourse(string $courseId, string $userId): ServiceReturn;

    /**
     * Lấy dữ liệu chứng nhận của khóa học (UC-058).
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function getCertificate(string $courseId, string $userId): ServiceReturn;

    /**
     * Tải file chứng nhận của khóa học (UC-058).
     *
     * @param string $courseId ID khóa học
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function downloadCertificate(string $courseId, string $userId): ServiceReturn;

    /**
     * Lưu tạm bài làm quiz (lưu bản nháp) (UC-059).
     *
     * @param string $lessonId ID bài học
     * @param array $answers Danh sách câu trả lời nháp [{quiz_id, selected_option}]
     * @param string $userId ID nhân viên
     * @return ServiceReturn
     */
    public function saveQuizDraft(string $lessonId, array $answers, string $userId): ServiceReturn;
}
