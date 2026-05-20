<?php

namespace App\Modules\CustomerMeeting\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingRepositoryInterface;
use App\Modules\CustomerMeeting\Models\CustomerMeeting;
use Illuminate\Database\Eloquent\Collection;

final class CustomerMeetingRepository extends BaseRepository implements CustomerMeetingRepositoryInterface
{
    /**
     * Xác định class Model đại diện cho Repository này.
     *
     * @return string Tên lớp Model dạng chuỗi
     */
    public function getModel(): string
    {
        return CustomerMeeting::class;
    }

    /**
     * Tìm danh sách các hoạt động gặp khách hàng gần đây của nhân viên.
     *
     * @param string $userId ID của nhân viên
     * @param int $limit Số lượng bản ghi tối đa
     * @return Collection
     */
    public function getRecentMeetingsByUserId(string $userId, int $limit = 5): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
