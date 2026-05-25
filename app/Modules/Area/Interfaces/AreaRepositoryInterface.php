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

