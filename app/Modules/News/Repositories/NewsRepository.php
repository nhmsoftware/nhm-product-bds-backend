<?php

namespace App\Modules\News\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\News\Interfaces\NewsRepositoryInterface;
use App\Modules\News\Models\News;

use Illuminate\Pagination\LengthAwarePaginator;

final class NewsRepository extends BaseRepository implements NewsRepositoryInterface
{
    public function getModel(): string
    {
        return News::class;
    }

    /**
     * Lấy danh sách tin tức đã xuất bản với bộ lọc.
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPublishedNews(array $filters): LengthAwarePaginator
    {
        $query = $this->model->where('is_published', true);

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('summary', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('published_at', 'desc')
                     ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Lấy danh sách tin tức nổi bật.
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeaturedNews(int $limit = 5)
    {
        return $this->model->where('is_published', true)
                           ->where('is_featured', true)
                           ->orderBy('published_at', 'desc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * Tìm kiếm tin tức theo từ khóa.
     * 
     * @param string $keyword
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $keyword, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('is_published', true)
                           ->where(function ($q) use ($keyword) {
                               $q->where('title', 'like', '%' . $keyword . '%')
                                 ->orWhere('summary', 'like', '%' . $keyword . '%')
                                 ->orWhere('content', 'like', '%' . $keyword . '%');
                           })
                           ->orderBy('published_at', 'desc')
                           ->paginate($perPage);
    }

    /**
     * Tìm tin tức theo slug.
     * 
     * @param string $slug
     * @return News|null
     */
    public function findBySlug(string $slug): ?News
    {
        return $this->model->where('slug', $slug)
                           ->where('is_published', true)
                           ->first();
    }

    /**
     * Lấy danh sách tin tức liên quan.
     * 
     * @param News $news
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRelatedNews(News $news, int $limit = 4)
    {
        return $this->model->where('is_published', true)
                           ->where('category', $news->category)
                           ->where('id', '!=', $news->id)
                           ->orderBy('published_at', 'desc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * Lấy danh sách tin tức nội bộ phân trang theo scope quyền hạn của User.
     * 
     * @param \App\Modules\Auth\Models\User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInternalNewsFeed(\App\Modules\Auth\Models\User $user, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->where('is_published', true);

        if (in_array($user->role, ['agent', 'broker'], true)) {
            // Employee (agent) or Team Leader (broker): Chỉ xem bài viết thuộc phòng ban của mình
            $query->where('department', $user->department);
        } elseif ($user->role === 'admin') {
            // Director (admin): Xem bài viết của tất cả phòng ban thuộc khu vực mình quản lý
            $query->where('area', $user->area);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->with('author')
                     ->orderBy('published_at', 'desc')
                     ->paginate($perPage);
    }
}

