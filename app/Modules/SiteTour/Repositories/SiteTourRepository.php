<?php

namespace App\Modules\SiteTour\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\SiteTour\Interfaces\SiteTourRepositoryInterface;
use App\Modules\SiteTour\Models\SiteTour;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Eloquent\Collection;
use App\Core\DTOs\FilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

final class SiteTourRepository extends BaseRepository implements SiteTourRepositoryInterface
{
    /**
     * Xác định class Model đại diện cho Repository này.
     *
     * @return string Tên lớp Model dạng chuỗi
     */
    public function getModel(): string
    {
        return SiteTour::class;
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
     * Tìm danh sách các hoạt động dẫn khách gần đây nhất của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return Collection
     */
    public function getRecentToursByUserId(string $userId, int $limit = 5): Collection
    {
        $query = $this->model->query();
        $query = $this->applyRoleScope($query, $userId);

        return $query
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Tìm danh sách lịch sử các hoạt động dẫn khách kèm bộ lọc của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param array $filters Bộ lọc tìm kiếm
     * @return Collection
     */
    public function getTourHistory(string $userId, array $filters = []): Collection
    {
        $query = $this->model->query();
        $query = $this->applyRoleScope($query, $userId);

        $query->with('project')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }

        return $query->get();
    }


    /**
     * Đếm số lượng hoạt động dẫn khách của các nhân viên.
     *
     * @param array|string $userIds ID của các nhân viên (UUID)
     * @param string|null $fromDate Ngày bắt đầu (format: 'Y-m-d')
     * @param string|null $toDate Ngày kết thúc (format: 'Y-m-d')
     * @return int Số lượng hoạt động dẫn khách
     */
    public function countSiteTours(array|string $userIds, ?string $fromDate, ?string $toDate): int
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

    /**
     * Đếm số lượng hoạt động dẫn khách của mỗi nhân viên.
     *
     * @param array $userIds ID của các nhân viên (UUID)
     * @param string|null $fromDate Ngày bắt đầu (format: 'Y-m-d')
     * @param string|null $toDate Ngày kết thúc (format: 'Y-m-d')
     * @return \Illuminate\Support\Collection Mảng chứa số lượng hoạt động dẫn khách của mỗi nhân viên
     */
    public function countSiteToursByUsers(array $userIds, ?string $fromDate, ?string $toDate): \Illuminate\Support\Collection
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

    /**
     * Tìm danh sách lịch sử các hoạt động dẫn khách của nhân viên.
     *
     * @param string $employeeId ID của nhân viên (UUID)
     * @param FilterDTO $filter Bộ lọc tìm kiếm
     * @return LengthAwarePaginator
     */
    public function getHistory(string $employeeId, FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->model->query();
        $query = $this->applyRoleScope($query, $employeeId);

        return $query->orderBy($filter->getSortBy() ?? 'created_at', $filter->getDirection())
                     ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
