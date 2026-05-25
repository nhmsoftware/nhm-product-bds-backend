<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\LotLockRequestRepositoryInterface;
use App\Modules\Area\Models\LotLockRequest;

final class LotLockRequestRepository extends BaseRepository implements LotLockRequestRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return LotLockRequest::class;
    }
}
