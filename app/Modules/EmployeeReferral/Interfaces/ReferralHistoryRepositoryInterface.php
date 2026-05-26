<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\EmployeeReferral\Models\ReferralHistory;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReferralHistoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm bản ghi giới thiệu theo nhân viên và số điện thoại.
     *
     * @param string $referrerId
     * @param string $phone
     * @return ReferralHistory|null
     */
    public function findByReferrerAndPhone(string $referrerId, string $phone): ?ReferralHistory;

    /**
     * Lấy danh sách lịch sử giới thiệu của một nhân viên có phân trang và lọc.
     *
     * @param string $referrerId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getHistory(string $referrerId, FilterDTO $filter): LengthAwarePaginator;
}
