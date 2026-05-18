<?php

namespace App\Modules\Consultation\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Consultation\Interfaces\ConsultationMessageRepositoryInterface;
use App\Modules\Consultation\Interfaces\ConsultationMessageServiceInterface;
use App\Modules\Consultation\Interfaces\ConsultationSettingRepositoryInterface;
use App\Modules\Consultation\Interfaces\ConsultationSettingServiceInterface;
use App\Modules\Consultation\Repositories\ConsultationMessageRepository;
use App\Modules\Consultation\Repositories\ConsultationSettingRepository;
use App\Modules\Consultation\Services\ConsultationMessageService;
use App\Modules\Consultation\Services\ConsultationSettingService;

class ConsultationServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên của Module này.
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'Consultation';
    }

    /**
     * Đăng ký các bindings.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(ConsultationSettingRepositoryInterface::class, ConsultationSettingRepository::class);
        $this->app->singleton(ConsultationSettingServiceInterface::class, ConsultationSettingService::class);
        $this->app->singleton(ConsultationMessageRepositoryInterface::class, ConsultationMessageRepository::class);
        $this->app->singleton(ConsultationMessageServiceInterface::class, ConsultationMessageService::class);
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
