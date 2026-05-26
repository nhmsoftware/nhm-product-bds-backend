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
    }

    protected function getModuleName(): string
    {
        return 'Dashboard';
    }
}
