<?php

namespace App\Modules\SiteTour\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface Repository quản lý hoạt động dẫn khách tham quan (Site Tour).
 */
interface SiteTourRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy lịch sử dẫn khách của một nhân viên có phân trang.
     *
     * @param string $employeeId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getHistory(string $employeeId, FilterDTO $filter): LengthAwarePaginator;

    /**
     * Đếm số lượt dẫn khách theo userIds và khoảng thời gian.
     */
    public function countSiteTours(array|string $userIds, ?string $fromDate, ?string $toDate): int;

    /**
     * Tìm danh sách các hoạt động dẫn khách gần đây nhất của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return Collection
     */
    public function getRecentToursByUserId(string $userId, int $limit = 5): Collection;

    /**
     * Tìm danh sách lịch sử các hoạt động dẫn khách kèm bộ lọc của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param array $filters Bộ lọc tìm kiếm
     * @return Collection
     */
    public function getTourHistory(string $userId, array $filters = []): Collection;
}
