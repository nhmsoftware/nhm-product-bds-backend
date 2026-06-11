<?php

namespace App\Modules\DepartmentTransfer\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface DepartmentTransferRequestRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Kiểm tra xem nhân viên đã có yêu cầu chuyển phòng ban nào đang chờ xử lý (pending) hay không.
     *
     * @param string $userId
     * @return bool
     */
    public function hasPendingRequest(string $userId): bool;

    /**
     * Lấy danh sách yêu cầu chuyển phòng ban có phân trang và lọc (UC-050).
     *
     * @param \App\Core\DTOs\FilterDTO $filter
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTransferRequests(\App\Core\DTOs\FilterDTO $filter): \Illuminate\Pagination\LengthAwarePaginator;

    /**
     * Lấy lịch sử yêu cầu chuyển phòng ban của một nhân viên.
     *
     * @param string $userId
     * @param \App\Core\DTOs\FilterDTO $filter
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserTransferRequests(string $userId, \App\Core\DTOs\FilterDTO $filter): \Illuminate\Pagination\LengthAwarePaginator;
}
