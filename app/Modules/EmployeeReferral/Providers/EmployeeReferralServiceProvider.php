<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryServiceInterface;
use App\Modules\EmployeeReferral\Repositories\ReferralHistoryRepository;
use App\Modules\EmployeeReferral\Services\ReferralHistoryService;

class EmployeeReferralServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'EmployeeReferral';
    }

    public function register(): void
    {
        $this->app->singleton(
            ReferralHistoryRepositoryInterface::class,
            ReferralHistoryRepository::class
        );

        $this->app->singleton(
            \App\Modules\EmployeeReferral\Interfaces\ReferralCommissionRepositoryInterface::class,
            \App\Modules\EmployeeReferral\Repositories\ReferralCommissionRepository::class
        );

        $this->app->singleton(
            \App\Modules\EmployeeReferral\Interfaces\ReferralCommissionServiceInterface::class,
            \App\Modules\EmployeeReferral\Services\ReferralCommissionService::class
        );
    }
}
