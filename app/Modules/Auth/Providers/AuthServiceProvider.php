<?php

namespace App\Modules\Auth\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\AuthRepository;

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
        $this->app->singleton(\App\Modules\Auth\Interfaces\AuthServiceInterface::class, \App\Modules\Auth\Services\AuthService::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
