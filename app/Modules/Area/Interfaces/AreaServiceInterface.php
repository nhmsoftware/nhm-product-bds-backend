<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\ServiceReturn;

interface AreaServiceInterface
{
    /**
     * Tải danh sách khu đất/bảng hàng được phân quyền cho người dùng.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getAssignedLandAreas(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Xem sơ đồ bảng hàng của khu đất theo trạng thái từng lô.
     *
     * @param string $userId
     * @param string $areaId
     * @return ServiceReturn
     */
    public function getInventoryMap(string $userId, string $areaId): ServiceReturn;

    /**
     * Xem thông tin chi tiết lô đất.
     *
     * @param string $userId
     * @param string $lotId
     * @return ServiceReturn
     */
    public function getLotDetail(string $userId, string $lotId): ServiceReturn;

    /**
     * Thêm bình luận nội bộ mới cho lô đất.
     *
     * @param \App\Modules\Area\DTO\CreateLotCommentDTO $dto
     * @return ServiceReturn
     */
    public function addLotComment(\App\Modules\Area\DTO\CreateLotCommentDTO $dto): ServiceReturn;

    /**
     * Yêu cầu giữ chỗ (lock) lô đất.
     *
     * @param \App\Modules\Area\DTO\RequestLockLotDTO $dto
     * @return ServiceReturn
     */
    public function requestLockLot(\App\Modules\Area\DTO\RequestLockLotDTO $dto): ServiceReturn;

    /**
     * Tìm kiếm khu đất hoặc lô đất.
     *
     * @param string $userId
     * @param \App\Modules\Area\DTO\SearchInventoryDTO $dto
     * @return ServiceReturn
     */
    public function searchInventory(string $userId, \App\Modules\Area\DTO\SearchInventoryDTO $dto): ServiceReturn;
}

