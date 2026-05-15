<?php

namespace App\Modules\Planning\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Planning\Interfaces\PlanningRepositoryInterface;
use App\Modules\Planning\Interfaces\PlanningServiceInterface;
use App\Modules\Planning\Repositories\PlanningRepository;
use App\Modules\Planning\Services\PlanningService;

class PlanningServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Planning';
    }

    /**
     * Đăng ký các bindings.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(PlanningRepositoryInterface::class, PlanningRepository::class);
        $this->app->singleton(PlanningServiceInterface::class, PlanningService::class);
    }

    /**
     * Boot các service của module.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }
}
