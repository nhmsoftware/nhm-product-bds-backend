<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;
use App\Modules\Auth\Models\RewardPointHistory;

final class RewardPointHistoryRepository extends BaseRepository implements RewardPointHistoryRepositoryInterface
{
    public function getModel(): string
    {
        return RewardPointHistory::class;
    }

    public function calculateCurrentMonthPoints(string $userId): int
    {
        return (int) $this->model->where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('points_changed');
    }

    public function calculateQuarterPoints(string $userId): int
    {
        return (int) $this->model->where('user_id', $userId)
            ->whereBetween('created_at', [now()->firstOfQuarter(), now()->lastOfQuarter()])
            ->sum('points_changed');
    }

    public function getHistoriesPaginated(string $userId, ?string $fromDate, ?string $toDate, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
