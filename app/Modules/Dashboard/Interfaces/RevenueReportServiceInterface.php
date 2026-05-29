<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Interfaces\ServiceReturn;

interface RevenueReportServiceInterface
{
    /**
     * Lấy báo cáo doanh thu công ty (UC-112)
     *
     * @param \App\Modules\Dashboard\DTO\ViewRevenueReportDTO $dto
     * @return ServiceReturn
     */
    public function getRevenueReports(\App\Modules\Dashboard\DTO\ViewRevenueReportDTO $dto): ServiceReturn;
}
