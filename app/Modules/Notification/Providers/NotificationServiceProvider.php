<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Notification\Services\NotificationService;

/**
 * Service Provider đăng ký và khởi tạo module Notification vào ứng dụng Laravel.
 */
class NotificationServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module để đăng ký các thành phần (config, routes...) tự động.
     *
     * @return string Tên Module
     */
    protected function getModuleName(): string
    {
        return 'Notification';
    }

    /**
     * Register các singleton bindings cho Repository và Service.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->singleton(NotificationServiceInterface::class, NotificationService::class);
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
