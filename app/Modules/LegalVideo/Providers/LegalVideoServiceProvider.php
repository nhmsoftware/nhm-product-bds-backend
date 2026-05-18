<?php

namespace App\Modules\LegalVideo\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\LegalVideo\Interfaces\LegalVideoRepositoryInterface;
use App\Modules\LegalVideo\Interfaces\LegalVideoServiceInterface;
use App\Modules\LegalVideo\Repositories\LegalVideoRepository;
use App\Modules\LegalVideo\Services\LegalVideoService;

class LegalVideoServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        $this->app->singleton(LegalVideoRepositoryInterface::class, LegalVideoRepository::class);
        $this->app->singleton(LegalVideoServiceInterface::class, LegalVideoService::class);
    }

    /**
     * Get the module name.
     */
    protected function getModuleName(): string
    {
        return 'LegalVideo';
    }
}
