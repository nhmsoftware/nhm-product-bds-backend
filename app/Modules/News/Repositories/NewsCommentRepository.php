<?php

namespace App\Modules\News\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\News\Interfaces\NewsCommentRepositoryInterface;
use App\Modules\News\Models\NewsComment;

final class NewsCommentRepository extends BaseRepository implements NewsCommentRepositoryInterface
{
    public function getModel(): string
    {
        return NewsComment::class;
    }

    /**
     * Lấy danh sách bình luận của một bài viết kèm thông tin người dùng.
     * 
     * @param string $newsId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCommentsByNewsId(string $newsId)
    {
        return $this->model->where('news_id', $newsId)
                           ->with('user')
                           ->orderBy('created_at', 'desc')
                           ->get();
    }
}
