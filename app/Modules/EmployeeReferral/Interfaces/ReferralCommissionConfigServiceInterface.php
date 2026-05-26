<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Interfaces;

use App\Core\Services\ServiceReturn;

interface ReferralCommissionConfigServiceInterface
{
    /**
     * Lấy toàn bộ cấu hình hoa hồng referral.
     *
     * @param string $actorId
     * @return ServiceReturn
     */
    public function getConfigs(string $actorId): ServiceReturn;

    /**
     * Cập nhật danh sách cấu hình hoa hồng referral.
     *
     * @param string $actorId
     * @param array $configs
     * @return ServiceReturn
     */
    public function updateConfigs(string $actorId, array $configs): ServiceReturn;
}
