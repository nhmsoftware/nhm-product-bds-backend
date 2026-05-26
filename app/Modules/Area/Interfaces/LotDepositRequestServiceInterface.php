<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\DTOs\ServiceReturn;
use App\Modules\Area\DTO\CreateLotDepositRequestDTO;

interface LotDepositRequestServiceInterface
{
    /**
     * Tạo yêu cầu đặt cọc lô đất.
     *
     * @param CreateLotDepositRequestDTO $dto
     * @return ServiceReturn
     */
    public function create(CreateLotDepositRequestDTO $dto): ServiceReturn;

    public function adminGetList(\App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto): ServiceReturn;

    public function adminGetDetail(string $id): ServiceReturn;

    public function adminApprove(string $id, \App\Modules\Auth\Models\User $user): ServiceReturn;

    public function adminReject(string $id, \App\Modules\Auth\Models\User $user, string $reason): ServiceReturn;

    public function adminConfirmTransaction(string $id, \App\Modules\Auth\Models\User $user): ServiceReturn;
}
