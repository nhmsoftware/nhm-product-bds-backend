<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionConfigRepositoryInterface;
use App\Modules\EmployeeReferral\Models\ReferralCommissionConfig;

class ReferralCommissionConfigRepository extends BaseRepository implements ReferralCommissionConfigRepositoryInterface
{
    /**
     * Tên model.
     *
     * @return string
     */
    public function getModel(): string
    {
        return ReferralCommissionConfig::class;
    }

    /**
     * Lấy toàn bộ cấu hình hoa hồng referral.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllConfigs()
    {
        return $this->model->all();
    }

    /**
     * Cập nhật cấu hình hoa hồng referral.
     *
     * @param int $referralType
     * @param string|int $amount
     * @return bool
     */
    public function updateConfig(int $referralType, $amount): bool
    {
        $config = $this->model->where('referral_type', $referralType)->first();

        if ($config) {
            $config->amount = $amount;
            return $config->save();
        }

        // Tạo mới nếu chưa có
        $newConfig = new $this->model();
        $newConfig->referral_type = $referralType;
        $newConfig->amount = $amount;
        return $newConfig->save();
    }
}
