<?php

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Dashboard\DTO\ViewDashboardDTO;

interface DashboardServiceInterface
{
    /**
     * Lấy dữ liệu tổng quan cho trang chủ.
     * 
     * @param ViewDashboardDTO $dto
     * @return ServiceReturn
     */
    public function getViewData(ViewDashboardDTO $dto): ServiceReturn;
}
