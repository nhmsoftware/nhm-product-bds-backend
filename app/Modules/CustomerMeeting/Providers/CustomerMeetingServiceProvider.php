<?php

namespace App\Modules\CustomerMeeting\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingRepositoryInterface;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingServiceInterface;
use App\Modules\CustomerMeeting\Repositories\CustomerMeetingRepository;
use App\Modules\CustomerMeeting\Services\CustomerMeetingService;

class CustomerMeetingServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module tương ứng để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'CustomerMeeting';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(CustomerMeetingRepositoryInterface::class, CustomerMeetingRepository::class);
        $this->app->singleton(CustomerMeetingServiceInterface::class, CustomerMeetingService::class);
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
