<?php

namespace Teksite\Module\Providers;

use Illuminate\Support\Facades\Route;
use Teksite\Module\Facade\Lareon;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RoutesManagerServiceProvider extends ServiceProvider
{

    public function boot()
    {
        parent::boot();
    }

    public function map(): void
    {
        $routes = config('modules.routes', []);
        foreach ($modules ?? [] as $module => $provider) {
            if (!defined("$provider::TYPE") || $provider::TYPE !== 'self') {
                foreach ($routes as $route) {
                    $suggestedPath = Lareon::modulePath($module, 'routes' . DIRECTORY_SEPARATOR . $route['path']);
                    if (file_exists($suggestedPath)) {
                        Route::prefix($route['prefix'] ?? '')
                            ->name($route['name'] ?? '')
                            ->middleware($route['middleware'] ?? [])
                            ->group($suggestedPath);

                    }
                }
            }
        }
    }

}
