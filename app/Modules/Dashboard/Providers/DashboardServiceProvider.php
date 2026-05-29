<?php

namespace App\Modules\Dashboard\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use App\Modules\Dashboard\Services\DashboardService;

class DashboardServiceProvider extends BaseModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardServiceInterface::class, DashboardService::class);
        $this->app->singleton(\App\Modules\Dashboard\Interfaces\SystemCommentRepositoryInterface::class, \App\Modules\Dashboard\Repositories\SystemCommentRepository::class);
        $this->app->singleton(\App\Modules\Dashboard\Interfaces\AdminCommentServiceInterface::class, \App\Modules\Dashboard\Services\AdminCommentService::class);
        $this->app->singleton(\App\Modules\Dashboard\Interfaces\EmployeeReportServiceInterface::class, \App\Modules\Dashboard\Services\EmployeeReportService::class);
        $this->app->singleton(\App\Modules\Dashboard\Interfaces\CompanyDashboardServiceInterface::class, \App\Modules\Dashboard\Services\CompanyDashboardService::class);
        $this->app->singleton(\App\Modules\Dashboard\Interfaces\RevenueReportServiceInterface::class, \App\Modules\Dashboard\Services\RevenueReportService::class);
    }

    protected function getModuleName(): string
    {
        return 'Dashboard';
    }
}
