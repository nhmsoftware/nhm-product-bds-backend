<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Interfaces\ServiceReturn;

interface CompanyDashboardServiceInterface
{
    /**
     * Lấy dữ liệu dashboard tổng quan công ty (UC-111)
     *
     * @param \App\Modules\Dashboard\DTO\ViewCompanyDashboardDTO $dto
     * @return ServiceReturn
     */
    public function getCompanyDashboard(\App\Modules\Dashboard\DTO\ViewCompanyDashboardDTO $dto): ServiceReturn;
}
