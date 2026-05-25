<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\LotRepositoryInterface;
use App\Modules\Area\Models\Lot;
use Illuminate\Support\Collection;

final class LotRepository extends BaseRepository implements LotRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return Lot::class;
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
                ->join('area_assignments', 'areas.id', '=', 'area_assignments.area_id')
                ->where('area_assignments.user_id', $userId)
                ->whereNull('area_assignments.deleted_at')
                ->whereNull('areas.deleted_at')
                ->select('lots.*');
        }

        return $query->where('lots.code', 'like', "%{$keyword}%")->get();
    }
}
