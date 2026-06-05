<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface AreaCommentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách bình luận của một khu đất kèm thông tin người dùng (có phân trang).
     *
     * @param string $areaId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCommentsByAreaId(string $areaId, int $perPage = 10);
}
