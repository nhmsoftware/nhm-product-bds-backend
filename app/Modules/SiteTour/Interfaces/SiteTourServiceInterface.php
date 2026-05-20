<?php

namespace App\Modules\SiteTour\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\SiteTour\DTO\CheckInSiteTourDTO;

/**
 * Interface Service quản lý các hoạt động dẫn khách tham quan (Site Tour).
 */
interface SiteTourServiceInterface
{
    /**
     * Thực hiện check-in hoạt động dẫn khách tham quan dự án/lô đất.
     *
     * @param CheckInSiteTourDTO $dto Dữ liệu check-in dẫn khách
     * @return ServiceReturn Trả về kết quả lưu hoạt động dẫn khách thành công hoặc thất bại
     */
    public function checkInSiteTour(CheckInSiteTourDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách các hoạt động dẫn khách gần đây nhất của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return ServiceReturn Trả về danh sách các hoạt động dẫn khách
     */
    public function getRecentTours(string $userId, int $limit = 5): ServiceReturn;

    /**
     * Lấy lịch sử dẫn khách tham quan kèm bộ lọc của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param array $filters Bộ lọc tìm kiếm
     * @return ServiceReturn Trả về danh sách lịch sử dẫn khách
     */
    public function getSiteTourHistory(string $userId, array $filters = []): ServiceReturn;
}
