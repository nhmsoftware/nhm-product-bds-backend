<?php

namespace App\Modules\LegalVideo\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\LegalVideo\Models\LegalVideo;
use Illuminate\Pagination\LengthAwarePaginator;

interface LegalVideoRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách video pháp lý đã xuất bản có bộ lọc và phân trang.
     *
     * @param array $filters Các tham số lọc gồm 'category', 'search', 'per_page', 'page'.
     * @return LengthAwarePaginator Trang danh sách video pháp lý.
     */
    public function getPublishedVideos(array $filters): LengthAwarePaginator;

    /**
     * Tìm kiếm video pháp lý theo slug.
     *
     * @param string $slug Slug của video.
     * @return LegalVideo|null Trả về instance của LegalVideo hoặc null nếu không tìm thấy.
     */
    public function findBySlug(string $slug): ?LegalVideo;
}
