<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface ReferralCommissionConfigRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy toàn bộ cấu hình hoa hồng referral.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllConfigs();

    /**
     * Cập nhật cấu hình hoa hồng referral.
     *
     * @param int $referralType
     * @param string|int $amount
     * @return bool
     */
    public function updateConfig(int $referralType, $amount): bool;
}
