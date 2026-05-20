<?php

namespace App\Modules\ActivityEvidence\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\ActivityEvidence\DTO\UploadEvidenceDTO;

/**
 * Interface Service xử lý tải lên minh chứng hoạt động.
 */
interface EvidenceServiceInterface
{
    /**
     * Tải ảnh minh chứng lên hệ thống.
     *
     * @param UploadEvidenceDTO $dto Dữ liệu file tải lên
     * @return ServiceReturn Trả về kết quả chứa đường dẫn URL của ảnh minh chứng
     */
    public function uploadEvidence(UploadEvidenceDTO $dto): ServiceReturn;
}
