<?php

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface SystemCommentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array $filters (keyword, type, project_id, area_id)
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getComments(array $filters, int $perPage = 15): LengthAwarePaginator;
}
