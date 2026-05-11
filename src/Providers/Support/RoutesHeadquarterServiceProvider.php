<?php

namespace Teksite\Module\Providers\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RoutesHeadquarterServiceProvider extends ServiceProvider
{

    /**
     * Define the routes for the module.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        if (file_exists(module_path($this->moduleName, '/routes/web.php'))) {
            Route::middleware('web')->group(module_path($this->moduleName, '/routes/web.php'));
        }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        if (file_exists(module_path($this->moduleName, '/routes/api.php'))) {
            Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->moduleName, '/routes/api.php'));
        }
    }
}
