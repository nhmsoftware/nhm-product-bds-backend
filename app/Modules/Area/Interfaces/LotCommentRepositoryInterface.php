<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface LotCommentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách bình luận của một lô đất kèm thông tin người dùng.
     *
     * @param string $lotId
     * @return \Illuminate\Support\Collection
     */
    public function getCommentsByLotId(string $lotId);
}
