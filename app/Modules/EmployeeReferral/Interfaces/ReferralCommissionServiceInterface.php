<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\ServiceReturn;

interface ReferralCommissionServiceInterface
{
    /**
     * Lấy danh sách hoa hồng giới thiệu của nhân viên.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getCommissions(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Xem chi tiết một khoản hoa hồng giới thiệu.
     *
     * @param string $userId
     * @param string $commissionId
     * @return ServiceReturn
     */
    public function getDetail(string $userId, string $commissionId): ServiceReturn;

    /**
     * Lấy báo cáo hoa hồng referral cho GD hoặc Super Admin.
     *
     * @param \App\Modules\Auth\Models\User $actor
     * @param array $filters
     * @return ServiceReturn
     */
    public function getReport(\App\Modules\Auth\Models\User $actor, array $filters): ServiceReturn;
}
