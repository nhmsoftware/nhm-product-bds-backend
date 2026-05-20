<?php

namespace App\Modules\ActivityEvidence\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\ActivityEvidence\Interfaces\EvidenceServiceInterface;
use App\Modules\ActivityEvidence\Services\EvidenceService;

class EvidenceServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module tương ứng để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'ActivityEvidence';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(EvidenceServiceInterface::class, EvidenceService::class);
    }

    /**
     * Thực thi các thiết lập boot của service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }
}
