<?php

namespace App\Modules\News\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface NewsCommentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách bình luận của một bài viết kèm thông tin người dùng.
     * 
     * @param string $newsId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCommentsByNewsId(string $newsId);
}
