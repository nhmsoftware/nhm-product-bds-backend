<?php

namespace App\Modules\Leave\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Leave\Interfaces\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Interfaces\LeaveServiceInterface;
use App\Modules\Leave\Repositories\LeaveRequestRepository;
use App\Modules\Leave\Services\LeaveService;

class LeaveServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module tương ứng để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'Leave';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
        $this->app->singleton(LeaveServiceInterface::class, LeaveService::class);
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
