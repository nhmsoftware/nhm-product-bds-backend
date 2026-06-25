<?php

namespace App\Modules\LegalVideo\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\LegalVideo\Interfaces\LegalVideoRepositoryInterface;
use App\Modules\LegalVideo\Models\LegalVideo;
use Illuminate\Pagination\LengthAwarePaginator;

final class LegalVideoRepository extends BaseRepository implements LegalVideoRepositoryInterface
{
    /**
     * Xác định class Model tương ứng cho Repository.
     *
     * @return string Tên class Model.
     */
    public function getModel(): string
    {
        return LegalVideo::class;
    }

    /**
     * Lấy danh sách video pháp lý đã xuất bản có bộ lọc và phân trang.
     *
     * @param array $filters Các tham số lọc gồm 'category', 'search', 'per_page', 'page'.
     * @return LengthAwarePaginator Trang danh sách video pháp lý.
     */
    public function getPublishedVideos(array $filters): LengthAwarePaginator
    {
        $query = $this->query()
            ->with('legalTopic')
            ->where('is_active', true);

        if (!empty($filters['category'])) {
            $category = $filters['category'];
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                  ->orWhere('legal_topic_id', $category)
                  ->orWhereHas('legalTopic', function ($sq) use ($category) {
                      $sq->where('slug', $category);
                  });
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            // Ánh xạ từ khóa tiếng Việt sang giá trị danh mục tương ứng
            $matchedCategories = [];
            $categoriesMap = [
                'project_legal' => ['pháp lý', 'dự án', 'phap ly', 'du an'],
                'contract' => ['hợp đồng', 'hop dong'],
                'planning' => ['quy hoạch', 'quy hoach'],
                'transaction_process' => ['quy trình', 'giao dịch', 'quy trinh', 'giao dich'],
                'other' => ['khác', 'khac'],
            ];

            foreach ($categoriesMap as $catId => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_stripos($search, $keyword) !== false) {
                        $matchedCategories[] = $catId;
                        break;
                    }
                }
            }

            $query->where(function ($q) use ($search, $matchedCategories) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('legalTopic', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
                
                if (!empty($matchedCategories)) {
                    $q->orWhereIn('category', $matchedCategories);
                } else {
                    $q->orWhere('category', 'like', "%{$search}%");
                }
            });
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Tìm kiếm video pháp lý theo slug.
     *
     * @param string $slug Slug của video.
     * @return LegalVideo|null Trả về instance của LegalVideo hoặc null nếu không tìm thấy.
     */
    public function findBySlug(string $slug): ?LegalVideo
    {
        return $this->query()
            ->where('slug', $slug)
            ->first();
    }
}
