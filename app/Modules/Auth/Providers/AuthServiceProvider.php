<?php

namespace App\Modules\Auth\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;
use App\Modules\Auth\Repositories\AuthRepository;
use App\Modules\Auth\Repositories\RewardPointHistoryRepository;
use App\Modules\Auth\Services\AuthCoreService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\EmployeeProfileService;
use App\Modules\Auth\Services\KpiService;
use App\Modules\Auth\Services\ProfileService;
use App\Modules\Auth\Services\RewardPointService;
use App\Modules\Auth\Services\TeamService;

class AuthServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Auth';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->singleton(RewardPointHistoryRepositoryInterface::class, RewardPointHistoryRepository::class);
        $this->app->singleton(AuthCoreService::class);
        $this->app->singleton(ProfileService::class);
        $this->app->singleton(EmployeeProfileService::class);
        $this->app->singleton(RewardPointService::class);
        $this->app->singleton(TeamService::class);
        $this->app->singleton(KpiService::class);
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
