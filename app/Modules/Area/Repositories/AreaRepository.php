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
        $teamId = $user?->team_id;

        return function ($query) use ($userId, $teamId): void {
            $query->where('area_assignments.user_id', $userId)
                ->orWhere(function ($q) use ($userId): void {
                    $q->where('area_assignments.assignable_type', 'user')
                        ->where('area_assignments.assignable_id', $userId);
                });

            if ($teamId !== null) {
                $query->orWhere(function ($q) use ($teamId): void {
                    $q->where('area_assignments.assignable_type', 'team')
                        ->where('area_assignments.assignable_id', $teamId);
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
        $areasQuery = $this->model->newQuery()
            ->with(['lots'])
            ->where(function ($q) use ($userId): void {
                // User/role has an explicit assignment
                $q->whereExists(function ($subQuery) use ($userId): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->where($this->assignmentScope($userId))
                        ->whereNull('area_assignments.deleted_at');
                })
                // OR area has no assignments at all → public
                ->orWhereNotExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->whereNull('area_assignments.deleted_at');
                });
            });

        $filters = $filter->getFilters();
        if (isset($filters['is_featured'])) {
            $isFeatured = filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN);
            $areasQuery->where('areas.is_featured', $isFeatured);
        }

        $areas = $areasQuery->get();

        // Fetch standalone lots
        $standaloneLots = collect();
        if (!(isset($filters['is_featured']) && filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN))) {
            $standaloneLots = \App\Modules\Area\Models\Lot::query()
                ->whereNull('area_id')
                ->get();
        }

        $items = collect();
        foreach ($areas as $area) {
            $items->push([
                'id' => $area->id,
                'name' => $area->name,
                'cover_url' => $area->image,
                'image' => $area->image,
                'total_lots' => (int) $area->total_lots,
                'remaining_lots' => (int) $area->remaining_lots,
                'status' => $area->remaining_lots > 0 ? 1 : 2,
                'is_featured' => (bool) $area->is_featured,
                'is_locked' => (bool) $area->is_locked,
                'google_maps_url' => $area->google_maps_url,
                'location' => $area->location,
                'record_type' => 'area',
                'created_at' => $area->created_at ? $area->created_at->toIso8601String() : null,
            ]);
        }

        foreach ($standaloneLots as $lot) {
            $items->push([
                'id' => $lot->id,
                'name' => 'Lô lẻ ' . $lot->code,
                'cover_url' => $lot->image_url,
                'image' => $lot->image_url,
                'total_lots' => 1,
                'remaining_lots' => ($lot->status === \App\Modules\Area\Models\Enums\LotStatus::AVAILABLE->value || $lot->status === 1) ? 1 : 0,
                'status' => $lot->status instanceof \App\Modules\Area\Models\Enums\LotStatus ? $lot->status->value : (int) $lot->status,
                'is_featured' => false,
                'is_locked' => (bool) $lot->is_locked,
                'google_maps_url' => null,
                'location' => $lot->direction,
                'record_type' => 'lot',
                'created_at' => $lot->created_at ? $lot->created_at->toIso8601String() : null,
            ]);
        }

        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection() === 'asc' ? 'asc' : 'desc';

        if ($direction === 'asc') {
            $items = $items->sortBy($sortBy);
        } else {
            $items = $items->sortByDesc($sortBy);
        }

        $currentPage = (int) $filter->getPage();
        $perPage = (int) $filter->getPerPage();
        $slicedItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slicedItems->toArray(),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
            ->where(function ($q) use ($userId): void {
                $q->whereExists(function ($subQuery) use ($userId): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->where($this->assignmentScope($userId))
                        ->whereNull('area_assignments.deleted_at');
                })
                ->orWhereNotExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->whereNull('area_assignments.deleted_at');
                });
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
        $query = $this->model->newQuery()->with(['lots']);

        if (!$isAdmin) {
            $query->where(function ($q) use ($userId): void {
                $q->whereExists(function ($subQuery) use ($userId): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->where($this->assignmentScope($userId))
                        ->whereNull('area_assignments.deleted_at');
                })
                ->orWhereNotExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->whereNull('area_assignments.deleted_at');
                });
            });
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('areas.name', 'ilike', "%{$keyword}%")
                ->orWhere('areas.project_name', 'ilike', "%{$keyword}%")
                ->orWhere('areas.location', 'ilike', "%{$keyword}%");
        })->get();
    }

}

