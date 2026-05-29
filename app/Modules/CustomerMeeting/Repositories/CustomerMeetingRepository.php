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

    public function getHistory(string $employeeId, \App\Core\DTOs\FilterDTO $filter): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $employeeId);
        return $query->orderBy($filter->getSortBy() ?? 'met_at', $filter->getDirection())
                     ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }

    public function countCustomerMeetings(array|string $userIds, ?string $fromDate, ?string $toDate): int
    {
        $userIdsArray = is_array($userIds) ? $userIds : [$userIds];
        $query = $this->model->whereIn('user_id', $userIdsArray);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->count();
    }

    public function countCustomerMeetingsByUsers(array $userIds, ?string $fromDate, ?string $toDate): \Illuminate\Support\Collection
    {
        $query = $this->model->whereIn('user_id', $userIds);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');
    }
}
