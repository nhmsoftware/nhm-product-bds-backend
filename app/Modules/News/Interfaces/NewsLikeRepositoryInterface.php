<?php

namespace App\Modules\News\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface NewsLikeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm bản ghi like theo newsId và userId.
     * 
     * @param string $newsId
     * @param string $userId
     * @return mixed
     */
    public function findLike(string $newsId, string $userId);
}
