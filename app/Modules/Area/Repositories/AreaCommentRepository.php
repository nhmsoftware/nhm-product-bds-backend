<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\AreaCommentRepositoryInterface;
use App\Modules\Area\Models\AreaComment;

final class AreaCommentRepository extends BaseRepository implements AreaCommentRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return AreaComment::class;
    }

    /**
     * Lấy danh sách bình luận của một khu đất kèm thông tin người dùng.
     *
     * @param string $areaId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCommentsByAreaId(string $areaId, int $perPage = 10)
    {
        return $this->model->where('area_id', $areaId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
