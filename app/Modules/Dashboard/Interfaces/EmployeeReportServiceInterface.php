<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Dashboard\DTO\ViewEmployeeReportDTO;

/**
 * Interface EmployeeReportServiceInterface
 * @package App\Modules\Dashboard\Interfaces
 */
interface EmployeeReportServiceInterface
{
    /**
     * Lấy báo cáo nhân viên (UC-109)
     * 
     * @param ViewEmployeeReportDTO $dto
     * @return ServiceReturn
     */
    public function getEmployeeReports(ViewEmployeeReportDTO $dto): ServiceReturn;

    /**
     * Lấy báo cáo phòng ban (UC-110)
     * 
     * @param \App\Modules\Dashboard\DTO\ViewDepartmentReportDTO $dto
     * @return ServiceReturn
     */
    public function getDepartmentReports(\App\Modules\Dashboard\DTO\ViewDepartmentReportDTO $dto): ServiceReturn;
}
