<?php

namespace App\Modules\News\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\News\Interfaces\NewsRepositoryInterface;
use App\Modules\News\Models\News;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Modules\Auth\Models\Enums\UserRole;

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
        $query = $this->model->where('is_published', true)
                             ->where('category', '!=', 'internal');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('summary', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderByRaw('CASE WHEN sort > 0 THEN sort ELSE 999999 END ASC')
                     ->orderBy('published_at', 'desc')
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
                           ->where('category', '!=', 'internal')
                           ->where('is_featured', true)
                           ->orderByRaw('CASE WHEN sort > 0 THEN sort ELSE 999999 END ASC')
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
                           ->where('category', '!=', 'internal')
                           ->where(function ($q) use ($keyword) {
                               $q->where('title', 'like', '%' . $keyword . '%')
                                 ->orWhere('summary', 'like', '%' . $keyword . '%')
                                 ->orWhere('content', 'like', '%' . $keyword . '%');
                           })
                           ->orderByRaw('CASE WHEN sort > 0 THEN sort ELSE 999999 END ASC')
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
                           ->where('category', '!=', 'internal')
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

        if (in_array($user->role?->name, ['employee', 'tp_kd'], true)) {
            // Employee or Manager: Chỉ xem bài viết thuộc phòng ban của mình
            $query->where('department', $user->department);
        } elseif ($user->role?->name === 'gdkd') {
            // Director: Xem bài viết của tất cả phòng ban thuộc chi nhánh mình quản lý
            $query->where('branch_id', $user->branch_id);
        } elseif (in_array($user->role?->name, ['ceo', 'super_admin'], true)) {
            // Toàn quyền
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->with('author')
                     ->withCount('comments')
                     ->orderBy('published_at', 'desc')
                     ->paginate($perPage);
    }

    /**
     * Lấy danh sách tin tức (dành cho Admin).
     * 
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getAdminList(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = $this->model->with('author:id,name,email');

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['isPublished']) && $filters['isPublished'] !== null) {
            $query->where('is_published', $filters['isPublished']);
        }

        if (!empty($filters['type'])) {
            if ($filters['type'] === 'public') {
                $query->whereNull('department')->whereNull('branch_id');
            } elseif ($filters['type'] === 'internal') {
                $query->where(function ($q) {
                    $q->whereNotNull('department')->orWhereNotNull('branch_id');
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    public function getLikedNews(string $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->whereHas('likes', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('is_published', true)
          ->with('author')
          ->orderBy('published_at', 'desc')
          ->paginate($perPage);
    }

    /**
     * Kiểm tra xem slug đã tồn tại chưa.
     * 
     * @param string $slug
     * @param string|null $ignoreId
     * @return bool
     */
    public function existsBySlug(string $slug, ?string $ignoreId = null): bool
    {
        $query = $this->model->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }
}
