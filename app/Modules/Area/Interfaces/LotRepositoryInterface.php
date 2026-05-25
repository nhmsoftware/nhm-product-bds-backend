<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Support\Collection;
use App\Modules\Area\Models\Lot;

interface LotRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách lô đất thuộc khu đất.
     *
     * @param string $areaId
     * @return Collection
     */
    public function getLotsByAreaId(string $areaId): Collection;

    /**
     * Lấy thông tin lô đất kèm tên khu đất.
     *
     * @param string $lotId
     * @return Lot|null
     */
    public function findLotWithArea(string $lotId): ?Lot;

    /**
     * Tìm kiếm lô đất dựa trên từ khóa và phân quyền.
     *
     * @param string $userId
     * @param string $keyword
     * @param bool $isAdmin
     * @return \Illuminate\Support\Collection
     */
    public function searchLots(string $userId, string $keyword, bool $isAdmin): \Illuminate\Support\Collection;
}
