<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Repositories;

use App\Core\DTOs\FilterDTO;
use App\Core\Repository\BaseRepository;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionRepositoryInterface;
use App\Modules\EmployeeReferral\Models\ReferralCommission;

final class ReferralCommissionRepository extends BaseRepository implements ReferralCommissionRepositoryInterface
{
    public function getModel(): string
    {
        return ReferralCommission::class;
    }

    /**
     * Lấy danh sách hoa hồng của một nhân viên có phân trang và lọc,
     * đồng thời tính toán tổng số tiền hoa hồng (total commission).
     *
     * @param string $referrerId
     * @param FilterDTO $filter
     * @return array{paginator: \Illuminate\Pagination\LengthAwarePaginator, total_commission: string}
     */
    public function getCommissions(string $referrerId, FilterDTO $filter): array
    {
        $query = $this->model->with(['referralHistory.referee'])
                             ->where('referrer_id', $referrerId);

        $filters = $filter->getFilters();

        // Lọc theo trạng thái thanh toán
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Lọc theo loại referral (thông qua bảng referral_histories)
        if (!empty($filters['referral_type'])) {
            $query->whereHas('referralHistory', function ($q) use ($filters) {
                $q->where('referral_type', $filters['referral_type']);
            });
        }

        // Tìm kiếm theo tên hoặc sđt của người được giới thiệu
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('referralHistory', function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        // Tính tổng hoa hồng của truy vấn hiện tại (trước khi phân trang)
        // Clone query để không ảnh hưởng query phân trang
        $totalCommission = (clone $query)->sum('amount');

        $paginator = $query->orderBy($filter->getSortBy() ?? 'created_at', $filter->getDirection())
                           ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());

        return [
            'paginator' => $paginator,
            'total_commission' => (string) $totalCommission,
        ];
    }

    /**
     * Lấy chi tiết một khoản hoa hồng.
     *
     * @param string $referrerId
     * @param string $commissionId
     * @return ReferralCommission|null
     */
    public function findCommission(string $referrerId, string $commissionId): ?ReferralCommission
    {
        return $this->model->with(['referralHistory.referee'])
                           ->where('referrer_id', $referrerId)
                           ->where('id', $commissionId)
                           ->first();
    }

    /**
     * Lấy báo cáo hoa hồng referral cho GD hoặc Super Admin.
     *
     * @param \App\Modules\Auth\Models\User $actor
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getReport(\App\Modules\Auth\Models\User $actor, array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->join('referral_histories', 'referral_commissions.referral_history_id', '=', 'referral_histories.id')
            ->join('users', 'referral_commissions.referrer_id', '=', 'users.id')
            ->selectRaw('
                users.id as referrer_id,
                users.name as referrer_name,
                referral_histories.referral_type,
                COUNT(referral_commissions.id) as referral_count,
                SUM(referral_commissions.amount) as total_commission
            ')
            ->groupBy('users.id', 'users.name', 'referral_histories.referral_type');

        // Phân quyền: DIRECTOR chỉ xem dữ liệu trong chi nhánh (area) của mình
        if ($actor->role?->name === 'gdkd') {
            $query->where('users.area', $actor->area);
        }

        // Lọc theo nhân viên
        if (!empty($filters['referrer_id'])) {
            $query->where('users.id', $filters['referrer_id']);
        }

        // Lọc theo loại referral
        if (!empty($filters['referral_type'])) {
            $query->where('referral_histories.referral_type', $filters['referral_type']);
        }

        // Lọc theo thời gian (dựa trên created_at của commission)
        if (!empty($filters['date_from'])) {
            $query->whereDate('referral_commissions.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('referral_commissions.created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);
    }
}
