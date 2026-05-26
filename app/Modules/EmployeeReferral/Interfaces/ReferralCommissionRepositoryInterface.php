<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\EmployeeReferral\Models\ReferralCommission;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReferralCommissionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách hoa hồng của một nhân viên có phân trang và lọc,
     * đồng thời tính toán tổng số tiền hoa hồng (total commission).
     *
     * @param string $referrerId
     * @param FilterDTO $filter
     * @return array{paginator: LengthAwarePaginator, total_commission: string}
     */
    public function getCommissions(string $referrerId, FilterDTO $filter): array;

    /**
     * Lấy chi tiết một khoản hoa hồng.
     *
     * @param string $referrerId
     * @param string $commissionId
     * @return ReferralCommission|null
     */
    public function findCommission(string $referrerId, string $commissionId): ?ReferralCommission;

    /**
     * Lấy báo cáo hoa hồng referral cho GD hoặc Super Admin.
     *
     * @param \App\Modules\Auth\Models\User $actor
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getReport(\App\Modules\Auth\Models\User $actor, array $filters): \Illuminate\Pagination\LengthAwarePaginator;
}
