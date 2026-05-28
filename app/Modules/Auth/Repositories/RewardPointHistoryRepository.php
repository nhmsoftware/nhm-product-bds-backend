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
}
