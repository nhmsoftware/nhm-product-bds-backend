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
    }

    protected function getModuleName(): string
    {
        return 'Dashboard';
    }
}
