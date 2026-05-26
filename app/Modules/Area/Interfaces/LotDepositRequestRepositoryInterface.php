<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Area\Models\LotDepositRequest;

interface LotDepositRequestRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Kiểm tra xem lô đất có yêu cầu đặt cọc nào đang xử lý không.
     *
     * @param string $lotId
     * @return bool
     */
    public function hasPendingDepositRequestForLot(string $lotId): bool;

    /**
     * Lấy danh sách yêu cầu đặt cọc cho Admin/Giám đốc
     *
     * @param \App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAdminList(\App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
