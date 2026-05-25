<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Core\DTOs\FilterDTO;
use App\Modules\Area\Interfaces\AreaRepositoryInterface;
use App\Modules\Area\Models\Area;
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
            ->join('area_assignments', 'areas.id', '=', 'area_assignments.area_id')
            ->where('area_assignments.user_id', $userId)
            ->whereNull('area_assignments.deleted_at')
            ->select('areas.*');

        $filters = $filter->getFilters();
        if (isset($filters['is_featured'])) {
            $isFeatured = filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN);
            $query->where('areas.is_featured', $isFeatured);
        }

        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();
        $query->orderBy("areas.{$sortBy}", $direction);

        return $query->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
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
        return $this->model->join('area_assignments', 'areas.id', '=', 'area_assignments.area_id')
            ->where('area_assignments.user_id', $userId)
            ->where('area_assignments.area_id', $areaId)
            ->whereNull('area_assignments.deleted_at')
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
        $query = $this->model;

        if (!$isAdmin) {
            $query = $query->join('area_assignments', 'areas.id', '=', 'area_assignments.area_id')
                ->where('area_assignments.user_id', $userId)
                ->whereNull('area_assignments.deleted_at')
                ->select('areas.*');
        }

        return $query->where('areas.name', 'like', "%{$keyword}%")->get();
    }
}

