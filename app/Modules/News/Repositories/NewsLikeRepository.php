<?php

namespace App\Modules\News\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\News\Interfaces\NewsLikeRepositoryInterface;
use App\Modules\News\Models\NewsLike;

final class NewsLikeRepository extends BaseRepository implements NewsLikeRepositoryInterface
{
    public function getModel(): string
    {
        return NewsLike::class;
    }

    /**
     * Tìm bản ghi like theo newsId và userId.
     * 
     * @param string $newsId
     * @param string $userId
     * @return \App\Modules\News\Models\NewsLike|null
     */
    public function findLike(string $newsId, string $userId)
    {
        return $this->model->where('news_id', $newsId)
                           ->where('user_id', $userId)
                           ->first();
    }

    /**
     * Lấy danh sách ID các bài viết đã thích từ một danh sách bài viết.
     * 
     * @param string $userId
     * @param array $newsIds
     * @return array
     */
    public function getLikedNewsIds(string $userId, array $newsIds): array
    {
        if (empty($newsIds)) {
            return [];
        }

        return $this->model->where('user_id', $userId)
                           ->whereIn('news_id', $newsIds)
                           ->pluck('news_id')
                           ->toArray();
    }
}
