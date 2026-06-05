<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface AreaCommentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách bình luận của một khu đất kèm thông tin người dùng.
     *
     * @param string $areaId
     * @return \Illuminate\Support\Collection
     */
    public function getCommentsByAreaId(string $areaId);
}
