<?php

namespace App\Modules\Project\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Project\Interfaces\ProjectRepositoryInterface;
use App\Modules\Project\Interfaces\ProjectServiceInterface;
use App\Modules\Project\Repositories\ProjectRepository;
use App\Modules\Project\Services\ProjectService;

class ProjectServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Project';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->singleton(ProjectServiceInterface::class, ProjectService::class);
    }
}
