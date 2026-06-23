<?php

namespace App\Modules\CustomerMeeting\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingRepositoryInterface;
use App\Modules\CustomerMeeting\Models\CustomerMeeting;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
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
     * Áp dụng phạm vi truy cập dữ liệu dựa trên vai trò của user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyRoleScope($query, string $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return $query->where('user_id', $userId);
        }

        if ($user->role === UserRole::SUPER_ADMIN || $user->role === UserRole::CEO) {
            return $query;
        }

        if ($user->role === UserRole::DIRECTOR) {
            if ($user->branch_id) {
                $userIds = User::where('branch_id', $user->branch_id)->pluck('id')->all();
                return $query->whereIn('user_id', $userIds);
            }
            return $query->where('user_id', $userId);
        }

        if ($user->role === UserRole::MANAGER) {
            if ($user->department_id) {
                $userIds = User::where('department_id', $user->department_id)->pluck('id')->all();
                return $query->whereIn('user_id', $userIds);
            }
            return $query->where('user_id', $userId);
        }

        return $query->where('user_id', $userId);
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
        $query = $this->model->query();
        $query = $this->applyRoleScope($query, $userId);

        return $query
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getHistory(string $employeeId, \App\Core\DTOs\FilterDTO $filter): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->query();
        $query = $this->applyRoleScope($query, $employeeId);

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
