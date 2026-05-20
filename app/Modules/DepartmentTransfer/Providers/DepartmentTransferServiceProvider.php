<?php

namespace App\Modules\DepartmentTransfer\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferRequestRepositoryInterface;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferServiceInterface;
use App\Modules\DepartmentTransfer\Repositories\DepartmentTransferRequestRepository;
use App\Modules\DepartmentTransfer\Services\DepartmentTransferService;

class DepartmentTransferServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module tương ứng để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'DepartmentTransfer';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(DepartmentTransferRequestRepositoryInterface::class, DepartmentTransferRequestRepository::class);
        $this->app->singleton(DepartmentTransferServiceInterface::class, DepartmentTransferService::class);
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
