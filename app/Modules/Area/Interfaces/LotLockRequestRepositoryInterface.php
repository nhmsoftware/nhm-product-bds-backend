<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Area\Models\LotLockRequest;

interface LotLockRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function findActiveByLotId(string $lotId): ?LotLockRequest;
}
