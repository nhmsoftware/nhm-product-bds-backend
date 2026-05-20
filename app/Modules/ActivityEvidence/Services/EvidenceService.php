<?php

namespace App\Modules\ActivityEvidence\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\ActivityEvidence\DTO\UploadEvidenceDTO;
use App\Modules\ActivityEvidence\Interfaces\EvidenceServiceInterface;
use Illuminate\Support\Facades\Storage;

final class EvidenceService extends BaseService implements EvidenceServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Tải ảnh minh chứng lên hệ thống.
     *
     * @param UploadEvidenceDTO $dto Dữ liệu file tải lên
     * @return ServiceReturn Trả về kết quả chứa đường dẫn URL của ảnh minh chứng
     */
    public function uploadEvidence(UploadEvidenceDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không thể xác định thông tin tài khoản.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            // Kiểm tra quyền nhân viên
            $allowedRoles = ['admin', 'agent', 'broker'];
            $this->validate(
                in_array($user->role, $allowedRoles, true),
                'Bạn không có quyền tải lên minh chứng hoạt động.',
                403
            );

            // 2. Thực hiện tải file lên đĩa public, thư mục evidence
            $path = $dto->image->store('evidence', 'public');
            
            // A4 - Lỗi tải file
            $this->validate(
                $path !== false && $path !== null,
                'Không thể tải minh chứng.',
                500
            );

            $url = Storage::url($path);

            return $this->success([
                'url' => $url,
                'path' => $path,
            ], 'Tải minh chứng thành công.', 201);
        }, useTransaction: true);
    }
}
