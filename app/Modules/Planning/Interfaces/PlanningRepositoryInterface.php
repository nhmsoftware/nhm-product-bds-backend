<?php

namespace App\Modules\Planning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Planning\DTO\PlanningListDTO;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface PlanningRepositoryInterface
 * 
 * @package App\Modules\Planning\Interfaces
 */
interface PlanningRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách quy hoạch có phân trang và lọc.
     *
     * @param PlanningListDTO $dto
     * @return LengthAwarePaginator
     */
    public function getList(PlanningListDTO $dto): LengthAwarePaginator;

    /**
     * Lấy danh sách các tỉnh/thành phố có quy hoạch.
     *
     * @return array
     */
    public function getAvailableCities(): array;
}
