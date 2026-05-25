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

    /**
     * Tải danh sách khóa học cho Admin kèm tìm kiếm và lọc.
     *
     * @param \App\Modules\Learning\DTO\AdminViewCoursesDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminGetCourses(\App\Modules\Learning\DTO\AdminViewCoursesDTO $dto, string $adminId): ServiceReturn;

    /**
     * Lấy thông tin chi tiết một khóa học cho Admin.
     *
     * @param string $courseId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminGetCourseDetails(string $courseId, string $adminId): ServiceReturn;

    /**
     * Tạo khóa học mới.
     *
     * @param \App\Modules\Learning\DTO\AdminCreateCourseDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminCreateCourse(\App\Modules\Learning\DTO\AdminCreateCourseDTO $dto, string $adminId): ServiceReturn;

    /**
     * Cập nhật thông tin khóa học.
     *
     * @param string $courseId
     * @param \App\Modules\Learning\DTO\AdminUpdateCourseDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminUpdateCourse(string $courseId, \App\Modules\Learning\DTO\AdminUpdateCourseDTO $dto, string $adminId): ServiceReturn;

    /**
     * Cập nhật trạng thái hoạt động (Khóa/Mở khóa) của khóa học (UC-072).
     *
     * @param string $courseId
     * @param \App\Modules\Learning\DTO\AdminUpdateCourseStatusDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminUpdateCourseStatus(string $courseId, \App\Modules\Learning\DTO\AdminUpdateCourseStatusDTO $dto, string $adminId): ServiceReturn;

    /**
     * Tạo bài quiz cho khóa học (UC-073).
     *
     * @param string $courseId
     * @param \App\Modules\Learning\DTO\AdminCreateCourseQuizDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminCreateCourseQuiz(string $courseId, \App\Modules\Learning\DTO\AdminCreateCourseQuizDTO $dto, string $adminId): ServiceReturn;

    /**
     * Cập nhật bài quiz cho khóa học (UC-074).
     *
     * @param string $courseId
     * @param \App\Modules\Learning\DTO\AdminUpdateCourseQuizDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminUpdateCourseQuiz(string $courseId, \App\Modules\Learning\DTO\AdminUpdateCourseQuizDTO $dto, string $adminId): ServiceReturn;

    /**
     * Xóa bài quiz của khóa học (UC-075).
     *
     * @param string $courseId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminDeleteCourseQuiz(string $courseId, string $adminId): ServiceReturn;

    /**
     * Xóa khóa học.
     *
     * @param string $courseId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminDeleteCourse(string $courseId, string $adminId): ServiceReturn;

    /**
     * Tạo bài học mới.
     *
     * @param \App\Modules\Learning\DTO\AdminCreateLessonDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminCreateLesson(\App\Modules\Learning\DTO\AdminCreateLessonDTO $dto, string $adminId): ServiceReturn;

    /**
     * Cập nhật thông tin bài học.
     *
     * @param string $lessonId
     * @param \App\Modules\Learning\DTO\AdminUpdateLessonDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminUpdateLesson(string $lessonId, \App\Modules\Learning\DTO\AdminUpdateLessonDTO $dto, string $adminId): ServiceReturn;

    /**
     * Xóa bài học.
     *
     * @param string $lessonId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminDeleteLesson(string $lessonId, string $adminId): ServiceReturn;

    /**
     * Tạo câu hỏi quiz mới cho bài học.
     *
     * @param \App\Modules\Learning\DTO\AdminCreateQuizDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminCreateQuiz(\App\Modules\Learning\DTO\AdminCreateQuizDTO $dto, string $adminId): ServiceReturn;

    /**
     * Cập nhật câu hỏi quiz.
     *
     * @param string $quizId
     * @param \App\Modules\Learning\DTO\AdminUpdateQuizDTO $dto
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminUpdateQuiz(string $quizId, \App\Modules\Learning\DTO\AdminUpdateQuizDTO $dto, string $adminId): ServiceReturn;

    /**
     * Xóa câu hỏi quiz.
     *
     * @param string $quizId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminDeleteQuiz(string $quizId, string $adminId): ServiceReturn;

    /**
     * Xác nhận hoàn thành onboarding (khóa học) cho nhân viên.
     *
     * @param string $courseId
     * @param string $userId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminConfirmOnboarding(string $courseId, string $userId, string $adminId): ServiceReturn;

    /**
     * Tải danh sách tiến độ onboarding của nhân viên.
     *
     * @param array $filters
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminGetOnboardingList(array $filters, string $adminId): ServiceReturn;

    /**
     * Tải chi tiết tiến độ onboarding của một nhân viên đối với khóa học.
     *
     * @param string $courseId
     * @param string $userId
     * @param string $adminId
     * @return ServiceReturn
     */
    public function adminGetOnboardingDetail(string $courseId, string $userId, string $adminId): ServiceReturn;
}
