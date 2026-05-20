<?php

namespace App\Modules\CustomerMeeting\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\CustomerMeeting\Models\CustomerMeeting;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface Repository quản lý hoạt động gặp khách hàng.
 */
interface CustomerMeetingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm danh sách các hoạt động gặp khách hàng gần đây của nhân viên.
     *
     * @param string $userId ID của nhân viên
     * @param int $limit Số lượng bản ghi tối đa
     * @return Collection
     */
    public function getRecentMeetingsByUserId(string $userId, int $limit = 5): Collection;
}
