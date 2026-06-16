<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\LotRepositoryInterface;
use App\Modules\Area\Models\Lot;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Collection;

final class LotRepository extends BaseRepository implements LotRepositoryInterface
{

    private function assignmentScope(string $userId): \Closure
    {
        $user = User::query()->find($userId);
        $department = $user?->department;
        $branch = $user?->area;

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
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return Lot::class;
    }

    public function findByIdAndAreaId(string $lotId, string $areaId): ?Lot
    {
        return $this->model->where('id', $lotId)->where('area_id', $areaId)->first();
    }

    public function getLotsToDelete(string $areaId, array $keepLotIds): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('area_id', $areaId)
            ->whereNotIn('id', $keepLotIds)
            ->get();
    }

    public function hasLockedLots(string $areaId): bool
    {
        return $this->model->where('area_id', $areaId)
            ->whereIn('status', [\App\Modules\Area\Models\Enums\LotStatus::SOLD, \App\Modules\Area\Models\Enums\LotStatus::RESERVED])
            ->exists();
    }

    /**
     * Lấy danh sách lô đất thuộc khu đất.
     *
     * @param string $areaId
     * @return Collection
     */
    public function getLotsByAreaId(string $areaId): Collection
    {
        return $this->model->where('area_id', $areaId)->get();
    }

    /**
     * Lấy thông tin lô đất kèm tên khu đất.
     *
     * @param string $lotId
     * @return Lot|null
     */
    public function findLotWithArea(string $lotId): ?Lot
    {
        return $this->model->where('id', $lotId)->with('area')->first();
    }

    /**
     * Tìm kiếm lô đất dựa trên từ khóa và phân quyền.
     *
     * @param string $userId
     * @param string $keyword
     * @param bool $isAdmin
     * @return \Illuminate\Support\Collection
     */
    public function searchLots(string $userId, string $keyword, bool $isAdmin): \Illuminate\Support\Collection
    {
        $query = $this->model->with('area');

        if (!$isAdmin) {
            $query = $query->join('areas', 'lots.area_id', '=', 'areas.id')
                ->whereNull('areas.deleted_at')
                ->whereExists(function ($subQuery) use ($userId): void {
                    $subQuery->selectRaw('1')
                        ->from('area_assignments')
                        ->whereColumn('area_assignments.area_id', 'areas.id')
                        ->where($this->assignmentScope($userId))
                        ->whereNull('area_assignments.deleted_at');
                })
                ->select('lots.*');
        }

        return $query->where('lots.code', 'like', "%{$keyword}%")->get();
    }
}
