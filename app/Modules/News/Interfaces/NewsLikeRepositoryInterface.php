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

    /**
     * Lấy danh sách ID các bài viết đã thích từ một danh sách bài viết.
     * 
     * @param string $userId
     * @param array $newsIds
     * @return array
     */
    public function getLikedNewsIds(string $userId, array $newsIds): array;
}
