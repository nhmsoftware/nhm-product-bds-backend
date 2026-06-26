<?php

namespace App\Modules\Leave\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Leave\Events\LeaveRequestApproved;
use App\Modules\Leave\Events\LeaveRequestCreated;
use App\Modules\Leave\Events\LeaveRequestRejected;
use App\Modules\Leave\Interfaces\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Interfaces\LeaveServiceInterface;
use App\Modules\Leave\Listeners\NotifyManagerOnLeaveRequestCreated;
use App\Modules\Leave\Listeners\NotifyEmployeeOnLeaveRequestApproved;
use App\Modules\Leave\Listeners\NotifyEmployeeOnLeaveRequestRejected;
use App\Modules\Leave\Repositories\LeaveRequestRepository;
use App\Modules\Leave\Services\LeaveService;
use Illuminate\Support\Facades\Event;

class LeaveServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Leave';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
        $this->app->singleton(LeaveServiceInterface::class, LeaveService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(LeaveRequestCreated::class, NotifyManagerOnLeaveRequestCreated::class);
        Event::listen(LeaveRequestApproved::class, NotifyEmployeeOnLeaveRequestApproved::class);
        Event::listen(LeaveRequestRejected::class, NotifyEmployeeOnLeaveRequestRejected::class);
    }
}
