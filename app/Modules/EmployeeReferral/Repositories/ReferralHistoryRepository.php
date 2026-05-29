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

    public function countSuccessfulReferrals(
        ?int $month,
        ?int $quarter,
        ?int $year,
        ?string $area
    ): int {
        $query = $this->model->where('status', 2);

        if (!empty($area)) {
            $query->whereHas('referrer', function ($q) use ($area) {
                $q->where('area', $area);
            });
        }

        if ($year) {
            $query->whereYear('created_at', $year);
        }
        if ($month) {
            $query->whereMonth('created_at', $month);
        } elseif ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $query->whereMonth('created_at', '>=', $startMonth)
                  ->whereMonth('created_at', '<=', $endMonth);
        }

        return $query->count();
    }

    public function countSuccessfulReferralsForUsers(
        array|string $userIds,
        ?string $fromDate,
        ?string $toDate
    ): int {
        $userIdsArray = is_array($userIds) ? $userIds : [$userIds];
        $query = $this->model->whereIn('referrer_id', $userIdsArray)
            ->where('referral_type', 1)
            ->where('status', 2);

        if ($fromDate) {
            $query->whereDate('registered_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('registered_at', '<=', $toDate);
        }

        return $query->count();
    }

    public function countSuccessfulReferralsByUsers(
        array $userIds,
        ?string $fromDate,
        ?string $toDate
    ): \Illuminate\Support\Collection {
        $query = $this->model->whereIn('referrer_id', $userIds)
            ->where('referral_type', 1)
            ->where('status', 2);

        if ($fromDate) {
            $query->whereDate('registered_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('registered_at', '<=', $toDate);
        }

        return $query->selectRaw('referrer_id as user_id, count(*) as count')
            ->groupBy('referrer_id')
            ->pluck('count', 'user_id');
    }
}
