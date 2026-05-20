<?php

namespace App\Modules\Leave\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\ServiceReturn;
use App\Modules\Leave\DTO\CreateLeaveDTO;

/**
 * Interface cung cấp các dịch vụ nghiệp vụ liên quan đến quản lý nghỉ phép.
 */
interface LeaveServiceInterface
{
    /**
     * Tiếp nhận, kiểm tra tính hợp lệ và xử lý lưu yêu cầu xin nghỉ phép mới của nhân viên.
     *
     * @param CreateLeaveDTO $dto DTO chứa thông tin đơn xin nghỉ phép
     * @return ServiceReturn Chứa thông tin đơn đã lưu thành công hoặc thông tin báo lỗi tương ứng
     * @throws \App\Core\Services\ServiceException
     */
    public function createLeaveRequest(CreateLeaveDTO $dto): ServiceReturn;

    /**
     * Tải danh sách lịch sử yêu cầu nghỉ phép của nhân viên có phân trang và lọc.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     * @throws \App\Core\Services\ServiceException
     */
    public function getLeaveHistory(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Hủy yêu cầu nghỉ phép đang ở trạng thái chờ duyệt.
     *
     * @param string $userId ID của nhân viên thực hiện hủy
     * @param string $leaveRequestId ID của yêu cầu nghỉ phép cần hủy
     * @return ServiceReturn
     * @throws \App\Core\Services\ServiceException
     */
    public function cancelLeaveRequest(string $userId, string $leaveRequestId): ServiceReturn;

    /**
     * Tải danh sách yêu cầu nghỉ phép của nhân viên trong phòng ban (cho Team Leader).
     *
     * @param string $userId ID của Team Leader (Broker hoặc Admin)
     * @param FilterDTO $filter
     * @return ServiceReturn Chứa thông tin phân trang danh sách các yêu cầu nghỉ phép
     * @throws \App\Core\Services\ServiceException
     */
    public function getDepartmentLeaveRequests(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Phê duyệt đơn xin nghỉ phép của nhân viên trong phòng ban (cho Team Leader) (UC-047).
     *
     * @param string $userId ID của Team Leader thực hiện duyệt
     * @param string $leaveRequestId ID của yêu cầu nghỉ phép cần duyệt
     * @return ServiceReturn Chứa thông tin đơn nghỉ phép sau khi duyệt
     * @throws \App\Core\Services\ServiceException
     */
    public function approveLeaveRequest(string $userId, string $leaveRequestId): ServiceReturn;

    /**
     * Từ chối đơn xin nghỉ phép của nhân viên trong phòng ban (cho Team Leader) (UC-048).
     *
     * @param string $userId ID của Team Leader thực hiện từ chối
     * @param string $leaveRequestId ID của yêu cầu nghỉ phép cần từ chối
     * @param string $reason Lý do từ chối
     * @return ServiceReturn Chứa thông tin đơn nghỉ phép sau khi từ chối
     * @throws \App\Core\Services\ServiceException
     */
    public function rejectLeaveRequest(string $userId, string $leaveRequestId, string $reason): ServiceReturn;
}
