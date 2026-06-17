<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Core\DTOs\FilterDTO;
use App\Modules\Area\Interfaces\AreaRepositoryInterface;
use App\Modules\Area\Models\Area;
use App\Modules\Auth\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class AreaRepository extends BaseRepository implements AreaRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return Area::class;
    }


    private function assignmentScope(string $userId): \Closure
    {
        $user = User::query()->find($userId);
        $department = $user?->department;
        $branch = $user?->branch_id;

        return function ($query) use ($userId, $department, $branch): void {
            $query->where('area_assignments.user_id', $userId)
                ->orWhere(function ($q) use ($userId): void {
                    $q->where('area_assignments.assignable_type', 'user')
                        ->where('area_assignments.assignable_id', $userId);
                });

            if (!empty($department)) {
                $query->orWhere(function ($q) use ($department): void {
                    $q->where('area_assignments.assignable_type', 'department')
                        ->where('area_assignments.assignable_id', $department);
                });
            }

            if (!empty($branch)) {
                $query->orWhere(function ($q) use ($branch): void {
                    $q->where('area_assignments.assignable_type', 'branch')
                        ->where('area_assignments.assignable_id', $branch);
                });
            }
        };
    }

    /**
     * Lấy tổng số lượng khu đất trong hệ thống.
     *
     * @return int
     */
    public function countAll(): int
    {
        return $this->model->newQuery()->count();
    }

    /**
     * Lấy danh sách khu đất được phân quyền cho người dùng có phân trang và lọc.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getAssignedAreas(string $userId, FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->whereExists(function ($subQuery) use ($userId): void {
                $subQuery->selectRaw('1')
                    ->from('area_assignments')
                    ->whereColumn('area_assignments.area_id', 'areas.id')
                    ->where($this->assignmentScope($userId))
                    ->whereNull('area_assignments.deleted_at');
            });

        $filters = $filter->getFilters();
        if (isset($filters['is_featured'])) {
            $isFeatured = filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN);
            $query->where('areas.is_featured', $isFeatured);
        }

        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();
        $query->orderBy("areas.{$sortBy}", $direction);

        return $query->paginate($filter->getPerPage(), ['areas.*'], 'page', $filter->getPage());
    }

    /**
     * Kiểm tra xem người dùng có được phân quyền truy cập khu đất này không.
     *
     * @param string $userId
     * @param string $areaId
     * @return bool
     */
    public function hasAssignment(string $userId, string $areaId): bool
    {
        return $this->model->newQuery()
            ->where('areas.id', $areaId)
            ->whereExists(function ($subQuery) use ($userId): void {
                $subQuery->selectRaw('1')
                    ->from('area_assignments')
                    ->whereColumn('area_assignments.area_id', 'areas.id')
                    ->where($this->assignmentScope($userId))
                    ->whereNull('area_assignments.deleted_at');
            })
            ->exists();
    }

    /**
     * Tìm kiếm khu đất dựa trên từ khóa và phân quyền.
     *
     * @param string $userId
     * @param string $keyword
     * @param bool $isAdmin
     * @return \Illuminate\Support\Collection
     */
    public function searchAreas(string $userId, string $keyword, bool $isAdmin): \Illuminate\Support\Collection
    {
        $query = $this->model->newQuery();

        if (!$isAdmin) {
            $query->whereExists(function ($subQuery) use ($userId): void {
                $subQuery->selectRaw('1')
                    ->from('area_assignments')
                    ->whereColumn('area_assignments.area_id', 'areas.id')
                    ->where($this->assignmentScope($userId))
                    ->whereNull('area_assignments.deleted_at');
            });
        }

        return $query->where('areas.name', 'like', "%{$keyword}%")->get();
    }

}

