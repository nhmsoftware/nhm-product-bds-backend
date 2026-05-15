<?php


use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\News\Providers\NewsServiceProvider;
use App\Modules\Planning\Providers\PlanningServiceProvider;
use App\Modules\Project\Providers\ProjectServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    DashboardServiceProvider::class,
    NewsServiceProvider::class,
    PlanningServiceProvider::class,
    ProjectServiceProvider::class,
];
