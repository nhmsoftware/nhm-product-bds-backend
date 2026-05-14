<?php


use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\News\Providers\NewsServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    DashboardServiceProvider::class,
    NewsServiceProvider::class,
];
