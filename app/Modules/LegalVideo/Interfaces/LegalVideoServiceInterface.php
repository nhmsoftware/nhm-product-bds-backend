<?php

namespace App\Modules\LegalVideo\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\LegalVideo\DTO\GetLegalVideoListDTO;

interface LegalVideoServiceInterface
{
    /**
     * Lấy danh sách video kiến thức và pháp lý bất động sản.
     *
     * @param GetLegalVideoListDTO $dto DTO chứa các tham số bộ lọc và phân trang.
     * @return ServiceReturn Đối tượng chứa danh sách video hoặc lỗi nếu xảy ra sự cố.
     */
    public function getList(GetLegalVideoListDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin chi tiết và phát video.
     *
     * @param string $idOrSlug UUID hoặc Slug của video pháp lý cần xem.
     * @return ServiceReturn Đối tượng chứa thông tin chi tiết của video hoặc lỗi nếu không còn khả dụng.
     */
    public function getDetail(string $idOrSlug): ServiceReturn;
}
