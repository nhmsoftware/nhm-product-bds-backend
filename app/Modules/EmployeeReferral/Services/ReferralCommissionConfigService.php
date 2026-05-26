<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionConfigRepositoryInterface;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionConfigServiceInterface;

final class ReferralCommissionConfigService extends BaseService implements ReferralCommissionConfigServiceInterface
{
    public function __construct(
        private readonly ReferralCommissionConfigRepositoryInterface $configRepository
    ) {
    }

    /**
     * Lấy toàn bộ cấu hình hoa hồng referral.
     *
     * @param string $actorId
     * @return ServiceReturn
     */
    public function getConfigs(string $actorId): ServiceReturn
    {
        return $this->execute(function () use ($actorId) {
            $actor = User::find($actorId);
            $this->validate($actor !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // Kiểm tra quyền (Chỉ General Director và Super Admin)
            $allowedRoles = [UserRole::CEO, UserRole::SUPER_ADMIN];
            $this->validate(
                in_array($actor->role, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $configs = $this->configRepository->getAllConfigs();

            if ($configs->isEmpty()) {
                return $this->error('Cấu hình hoa hồng không tồn tại.', 404);
            }

            return $this->success($configs, 'Tải cấu hình hoa hồng referral thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error('Không thể tải cấu hình hoa hồng referral.', 500);
        });
    }

    /**
     * Cập nhật danh sách cấu hình hoa hồng referral.
     *
     * @param string $actorId
     * @param array $configs
     * @return ServiceReturn
     */
    public function updateConfigs(string $actorId, array $configs): ServiceReturn
    {
        return $this->execute(function () use ($actorId, $configs) {
            $actor = User::find($actorId);
            $this->validate($actor !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // Kiểm tra quyền (Chỉ General Director và Super Admin)
            $allowedRoles = [UserRole::CEO, UserRole::SUPER_ADMIN];
            $this->validate(
                in_array($actor->role, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            foreach ($configs as $config) {
                $this->configRepository->updateConfig((int)$config['referral_type'], $config['amount']);
            }

            return $this->success(null, 'Cập nhật cấu hình hoa hồng thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error('Không thể cập nhật cấu hình hoa hồng referral.', 500);
        });
    }
}
