<?php

declare(strict_types=1);

namespace App\Modules\Auth\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface RewardPointHistoryRepositoryInterface extends BaseRepositoryInterface
{
    public function calculateCurrentMonthPoints(string $userId): int;
    
    public function calculateQuarterPoints(string $userId): int;
    
    public function getHistoriesPaginated(string $userId, ?string $fromDate, ?string $toDate, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
