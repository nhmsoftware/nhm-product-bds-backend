<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\LotCommentRepositoryInterface;
use App\Modules\Area\Models\LotComment;

final class LotCommentRepository extends BaseRepository implements LotCommentRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return LotComment::class;
    }

    /**
     * Lấy danh sách bình luận của một lô đất kèm thông tin người dùng.
     *
     * @param string $lotId
     * @return \Illuminate\Support\Collection
     */
    public function getCommentsByLotId(string $lotId)
    {
        return $this->model->where('lot_id', $lotId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
