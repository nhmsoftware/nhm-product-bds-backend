<?php

namespace App\Modules\Area\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Area\Interfaces\AreaRepositoryInterface;
use App\Modules\Area\Interfaces\AreaServiceInterface;
use App\Modules\Area\Repositories\AreaRepository;
use App\Modules\Area\Services\AreaService;

class AreaServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module tương ứng để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'Area';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(AreaRepositoryInterface::class, AreaRepository::class);
        $this->app->singleton(\App\Modules\Area\Interfaces\LotRepositoryInterface::class, \App\Modules\Area\Repositories\LotRepository::class);
        $this->app->singleton(\App\Modules\Area\Interfaces\LotCommentRepositoryInterface::class, \App\Modules\Area\Repositories\LotCommentRepository::class);
        $this->app->singleton(\App\Modules\Area\Interfaces\LotLockRequestRepositoryInterface::class, \App\Modules\Area\Repositories\LotLockRequestRepository::class);
        $this->app->singleton(AreaServiceInterface::class, AreaService::class);
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
