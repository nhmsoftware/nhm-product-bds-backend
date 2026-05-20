<?php

namespace App\Modules\News\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\News\Models\News;
use Illuminate\Pagination\LengthAwarePaginator;

interface NewsRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách tin tức đã xuất bản với bộ lọc.
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPublishedNews(array $filters): LengthAwarePaginator;

    /**
     * Lấy danh sách tin tức nổi bật.
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeaturedNews(int $limit = 5);

    /**
     * Tìm kiếm tin tức theo từ khóa.
     * 
     * @param string $keyword
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $keyword, int $perPage = 10): LengthAwarePaginator;

    /**
     * Tìm tin tức theo slug.
     * 
     * @param string $slug
     * @return News|null
     */
    public function findBySlug(string $slug): ?News;

    /**
     * Lấy danh sách tin tức liên quan.
     * 
     * @param News $news
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRelatedNews(News $news, int $limit = 4);

    /**
     * Lấy danh sách tin tức nội bộ phân trang theo scope quyền hạn của User.
     * 
     * @param \App\Modules\Auth\Models\User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInternalNewsFeed(\App\Modules\Auth\Models\User $user, int $perPage = 10): LengthAwarePaginator;
}

