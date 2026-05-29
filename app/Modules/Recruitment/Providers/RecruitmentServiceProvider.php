<?php

namespace App\Modules\Recruitment\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RecruitmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Đăng ký các Interface và Repository/Service
        $this->app->bind(
            \App\Modules\Recruitment\Interfaces\RecruitmentPostRepositoryInterface::class,
            \App\Modules\Recruitment\Repositories\RecruitmentPostRepository::class
        );
        
        $this->app->bind(
            \App\Modules\Recruitment\Interfaces\RecruitmentPostServiceInterface::class,
            \App\Modules\Recruitment\Services\RecruitmentPostService::class
        );
    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        if (file_exists(__DIR__.'/../Routes/api.php')) {
            Route::prefix('api')
                 ->middleware('api')
                 ->group(__DIR__.'/../Routes/api.php');
        }
    }
}
