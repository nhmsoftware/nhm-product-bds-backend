<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * Tên của module (ví dụ: Ride, Wallet)
     */
    protected abstract function getModuleName(): string;

    /**
     * Đường dẫn gốc của module
     */
    protected function getModulePath(): string
    {
        return app_path("Modules/" . $this->getModuleName());
    }

    protected function getControllerNamespace(): string
    {
        return "App\\Modules\\{$this->getModuleName()}\\Http\\Controllers";
    }

    public function boot(): void
    {
        $this->registerModuleRoutes();
        $this->registerModuleConfigs();
    }

    /**
     * Register module routes.
     * @return void
     */
    protected function registerModuleRoutes()
    {
        $moduleName = strtolower($this->getModuleName());
        $path = app_path("Modules/{$this->getModuleName()}/Routes/api.php");
        if (file_exists($path)) {
            Route::prefix("api") // VD: api/ride, api/wallet
                ->middleware('api')
                ->as("{$moduleName}.")         // VD: route('ride.store')
                ->namespace($this->getControllerNamespace()) // Quan trọng để nhận diện Controller
                ->group($path);
        }
    }


    /**
     * Register module configs.
     * @return void
     */
    protected function registerModuleConfigs()
    {
        $path = $this->getModulePath() . '/Config/config.php';
        if (file_exists($path)) {
            $this->mergeConfigFrom($path, strtolower($this->getModuleName()));
        }
    }
}
