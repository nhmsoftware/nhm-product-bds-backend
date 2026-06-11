<?php

namespace App\Modules\DepartmentTransfer\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\ServiceReturn;
use App\Modules\DepartmentTransfer\DTO\StoreDepartmentTransferRequestDTO;

interface DepartmentTransferServiceInterface
{
    /**
     * Tạo yêu cầu chuyển phòng ban.
     *
     * @param StoreDepartmentTransferRequestDTO $dto
     * @return ServiceReturn
     */
    public function createDepartmentTransferRequest(StoreDepartmentTransferRequestDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách yêu cầu chuyển phòng ban của nhân viên có phân trang và lọc (UC-050).
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getDepartmentTransferRequests(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Lấy lịch sử yêu cầu chuyển phòng ban của nhân viên đang đăng nhập.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getEmployeeDepartmentTransferHistory(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Phê duyệt yêu cầu chuyển phòng ban của nhân viên (UC-051).
     *
     * @param string $userId ID của Director thực hiện duyệt
     * @param string $requestId ID của yêu cầu chuyển phòng ban cần duyệt
     * @return ServiceReturn
     */
    public function approveDepartmentTransferRequest(string $userId, string $requestId): ServiceReturn;

    /**
     * Từ chối yêu cầu chuyển phòng ban của nhân viên (UC-052).
     *
     * @param string $userId ID của Director thực hiện từ chối
     * @param string $requestId ID của yêu cầu chuyển phòng ban cần từ chối
     * @param string $reason Lý do từ chối yêu cầu
     * @return ServiceReturn
     */
    public function rejectDepartmentTransferRequest(string $userId, string $requestId, string $reason): ServiceReturn;
}
