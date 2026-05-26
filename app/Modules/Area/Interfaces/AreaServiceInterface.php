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

    /**
     * Tạo Area và danh sách Lots. Trả về Area model.
     * (Hàm này dùng nội bộ hoặc trong CUD qua Service khác orchestration).
     *
     * @param \App\Modules\Area\DTO\CreateAreaDTO $areaDto
     * @param \App\Modules\Area\DTO\CreateLotDTO[] $lotDtos
     * @return \App\Modules\Area\Models\Area
     */
    public function createAreaWithLots(\App\Modules\Area\DTO\CreateAreaDTO $areaDto, array $lotDtos): \App\Modules\Area\Models\Area;
    /**
     * [Admin] Khóa/Mở khóa lô đất.
     * 
     * @param string $userId
     * @param string $id
     * @param bool $isLocked
     * @return \App\Core\Services\ServiceReturn
     */
    public function lockUnlockLot(string $userId, string $id, bool $isLocked): \App\Core\Services\ServiceReturn;

    /**
     * Đồng bộ bảng hàng (Area & Lot) cho một dự án.
     * Cập nhật, tạo mới và xóa (nếu không có trong danh sách).
     *
     * @param string $projectId
     * @param array $areasData
     * @return void
     * @throws \App\Core\Services\ServiceException
     */
    public function bulkSyncAreasWithLots(string $projectId, array $areasData): void;
}
