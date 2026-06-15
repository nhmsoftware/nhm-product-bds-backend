<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface;
use App\Modules\Area\Models\LotDepositRequest;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;

final class LotDepositRequestRepository extends BaseRepository implements LotDepositRequestRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return LotDepositRequest::class;
    }

    public function hasPendingDepositRequestForLot(string $lotId): bool
    {
        return $this->model
            ->where('lot_id', $lotId)
            ->where('status', LotDepositRequestStatus::PENDING->value)
            ->exists();
    }

    public function findActiveByLotId(string $lotId): ?LotDepositRequest
    {
        return $this->model
            ->where('lot_id', $lotId)
            ->whereIn('status', [
                LotDepositRequestStatus::PENDING->value,
                LotDepositRequestStatus::APPROVED->value,
            ])
            ->latest('created_at')
            ->first();
    }

    public function getAdminList(\App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['lot.area.project', 'user']);

        if ($dto->status !== null) {
            $query->where('status', $dto->status);
        }

        if ($dto->employee_id !== null) {
            $query->where('user_id', $dto->employee_id);
        }

        if ($dto->project_id !== null || $dto->branch !== null) {
            $query->whereHas('lot.area.project', function ($q) use ($dto) {
                if ($dto->project_id !== null) {
                    $q->where('id', $dto->project_id);
                }
                if ($dto->branch !== null) {
                    $q->where('branch', $dto->branch);
                }
            });
        }

        if ($dto->search !== null) {
            $search = $dto->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'iLike', "%{$search}%")
                       ->orWhere('phone', 'iLike', "%{$search}%")
                       ->orWhere('email', 'iLike', "%{$search}%");
                })->orWhereHas('lot', function ($ql) use ($search) {
                    $ql->where('code', 'iLike', "%{$search}%");
                })->orWhereHas('lot.area.project', function ($qp) use ($search) {
                    $qp->where('name', 'iLike', "%{$search}%");
                });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($dto->per_page, ['*'], 'page', $dto->page);
    }

    public function getRevenueReportData(
        ?string $startDate,
        ?string $endDate,
        ?string $department,
        ?string $projectId,
        ?string $area
    ): \Illuminate\Database\Eloquent\Collection {
        $query = $this->model->select([
                'lot_deposit_requests.id',
                'lot_deposit_requests.created_at',
                'lots.price',
                'users.department',
                'users.area',
                'users.name as user_name',
                'users.id as user_id',
                'projects.name as project_name',
                'projects.id as project_id'
            ])
            ->join('lots', 'lot_deposit_requests.lot_id', '=', 'lots.id')
            ->join('areas', 'lots.area_id', '=', 'areas.id')
            ->leftJoin('projects', 'areas.project_id', '=', 'projects.id')
            ->join('users', 'lot_deposit_requests.user_id', '=', 'users.id')
            ->where('lot_deposit_requests.status', 2) // APPROVED
            ->whereNull('lot_deposit_requests.deleted_at');

        if (!empty($startDate)) {
            $query->whereDate('lot_deposit_requests.created_at', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('lot_deposit_requests.created_at', '<=', $endDate);
        }
        if (!empty($department)) {
            $query->where('users.department', $department);
        }
        if (!empty($projectId)) {
            $query->where('projects.id', $projectId);
        }
        if (!empty($area)) {
            $query->where('users.area', $area);
        }

        return $query->get();
    }

    public function getCompanyDashboardTransactionStats(
        ?int $month,
        ?int $quarter,
        ?int $year,
        ?string $area
    ): array {
        $query = $this->model->where('lot_deposit_requests.status', 2)
            ->join('lots', 'lot_deposit_requests.lot_id', '=', 'lots.id');

        if (!empty($area)) {
            $query->join('users', 'lot_deposit_requests.user_id', '=', 'users.id')
                  ->where('users.area', $area);
        }

        if ($year) {
            $query->whereYear('lot_deposit_requests.created_at', $year);
        }
        if ($month) {
            $query->whereMonth('lot_deposit_requests.created_at', $month);
        } elseif ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $query->whereMonth('lot_deposit_requests.created_at', '>=', $startMonth)
                  ->whereMonth('lot_deposit_requests.created_at', '<=', $endMonth);
        }

        // We can't clone a query with a join easily without issues in count vs sum depending on the driver, but we can do it directly.
        // Or we can just get the sum and count in one query:
        // SELECT COUNT(*) as total_transactions, SUM(lots.price) as total_revenue
        $result = $query->selectRaw('COUNT(lot_deposit_requests.id) as total_transactions, SUM(lots.price) as total_revenue')->first();

        return [
            'total_transactions' => (int) ($result->total_transactions ?? 0),
            'total_revenue' => (int) ($result->total_revenue ?? 0)
        ];
    }

    public function countCompletedTransactions(
        array|string $userIds,
        ?string $fromDate,
        ?string $toDate
    ): int {
        $userIdsArray = is_array($userIds) ? $userIds : [$userIds];
        $query = $this->model->whereIn('user_id', $userIdsArray)
            ->where('status', 4); // COMPLETED

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->count();
    }

    public function countCompletedTransactionsByUsers(
        array $userIds,
        ?string $fromDate,
        ?string $toDate
    ): \Illuminate\Support\Collection {
        $query = $this->model->whereIn('user_id', $userIds)
            ->where('status', 4);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');
    }
}
