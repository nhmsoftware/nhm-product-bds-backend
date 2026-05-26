<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Repositories;

use App\Core\DTOs\FilterDTO;
use App\Core\Repository\BaseRepository;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface;
use App\Modules\EmployeeReferral\Models\ReferralHistory;
use Illuminate\Pagination\LengthAwarePaginator;

final class ReferralHistoryRepository extends BaseRepository implements ReferralHistoryRepositoryInterface
{
    public function getModel(): string
    {
        return ReferralHistory::class;
    }

    /**
     * Tìm bản ghi giới thiệu theo nhân viên và số điện thoại.
     *
     * @param string $referrerId
     * @param string $phone
     * @return ReferralHistory|null
     */
    public function findByReferrerAndPhone(string $referrerId, string $phone): ?ReferralHistory
    {
        return $this->model->where('referrer_id', $referrerId)
                           ->where('phone', $phone)
                           ->first();
    }

    /**
     * Lấy danh sách lịch sử giới thiệu của một nhân viên có phân trang và lọc.
     *
     * @param string $referrerId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getHistory(string $referrerId, FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->model->where('referrer_id', $referrerId);

        $filters = $filter->getFilters();

        if (!empty($filters['referral_type'])) {
            $query->where('referral_type', $filters['referral_type']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        return $query->orderBy($filter->getSortBy() ?? 'scanned_at', $filter->getDirection())
                     ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
