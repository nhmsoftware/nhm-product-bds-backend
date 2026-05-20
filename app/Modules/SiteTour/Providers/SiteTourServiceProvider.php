<?php

namespace App\Modules\SiteTour\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\SiteTour\Interfaces\SiteTourRepositoryInterface;
use App\Modules\SiteTour\Interfaces\SiteTourServiceInterface;
use App\Modules\SiteTour\Repositories\SiteTourRepository;
use App\Modules\SiteTour\Services\SiteTourService;

class SiteTourServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module tương ứng để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'SiteTour';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(SiteTourRepositoryInterface::class, SiteTourRepository::class);
        $this->app->singleton(SiteTourServiceInterface::class, SiteTourService::class);
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
