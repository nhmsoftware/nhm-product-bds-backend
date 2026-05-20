<?php

namespace App\Modules\CustomerMeeting\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\CustomerMeeting\DTO\CheckInMeetCustomerDTO;

/**
 * Interface Service quản lý các hoạt động gặp khách hàng.
 */
interface CustomerMeetingServiceInterface
{
    /**
     * Thực hiện check-in hoạt động gặp khách hàng tại dự án.
     *
     * @param CheckInMeetCustomerDTO $dto Dữ liệu check-in gặp khách
     * @return ServiceReturn Trả về kết quả lưu hoạt động gặp khách thành công hoặc thất bại
     */
    public function checkInMeetCustomer(CheckInMeetCustomerDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách các hoạt động gặp khách hàng gần đây của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return ServiceReturn Trả về danh sách các hoạt động
     */
    public function getRecentMeetings(string $userId, int $limit = 5): ServiceReturn;

    /**
     * Lấy chi tiết hoạt động gặp khách hàng.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param string $id ID của hoạt động gặp khách hàng (UUID)
     * @return ServiceReturn Trả về chi tiết hoạt động gặp khách hàng
     */
    public function getMeetingDetails(string $userId, string $id): ServiceReturn;
}
