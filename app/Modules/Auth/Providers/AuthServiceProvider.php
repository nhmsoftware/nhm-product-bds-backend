<?php

namespace App\Modules\Auth\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;
use App\Modules\Auth\Repositories\AuthRepository;
use App\Modules\Auth\Repositories\RewardPointHistoryRepository;
use App\Modules\Auth\Services\AuthService;

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
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(RewardPointHistoryRepositoryInterface::class, RewardPointHistoryRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
