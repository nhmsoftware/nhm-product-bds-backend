<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Core\DTOs\FilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface AreaRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy tổng số lượng khu đất trong hệ thống.
     *
     * @return int
     */
    public function countAll(): int;

    /**
     * Tìm khu đất theo ID và Project ID.
     *
     * @param string $areaId
     * @param string $projectId
     * @return \App\Modules\Area\Models\Area|null
     */
    public function findByIdAndProjectId(string $areaId, string $projectId): ?\App\Modules\Area\Models\Area;

    /**
     * Lấy các khu đất cần xóa trong dự án.
     *
     * @param string $projectId
     * @param array $keepAreaIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAreasToDelete(string $projectId, array $keepAreaIds): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lấy danh sách khu đất được phân quyền cho người dùng có phân trang và lọc.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getAssignedAreas(string $userId, FilterDTO $filter): LengthAwarePaginator;

    /**
     * Kiểm tra xem người dùng có được phân quyền truy cập khu đất này không.
     *
     * @param string $userId
     * @param string $areaId
     * @return bool
     */
    public function hasAssignment(string $userId, string $areaId): bool;

    /**
     * Tìm kiếm khu đất dựa trên từ khóa và phân quyền.
     *
     * @param string $userId
     * @param string $keyword
     * @param bool $isAdmin
     * @return \Illuminate\Support\Collection
     */
    public function searchAreas(string $userId, string $keyword, bool $isAdmin): \Illuminate\Support\Collection;
}

