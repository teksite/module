<?php

namespace Teksite\Module\Providers\Support;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RoutesHeadquarterServiceProvider extends ServiceProvider
{

    /**
     * Define the routes for the module.
     */
    public function map(): void
    {
        if (!isStewardInstalled()) return;

        $modules = collect(get_modules())->filter(function ($module) {
            return (($module['type'] ?? null) === 'steward' && ($module['active'] ?? false) === true);
        })->keys()->toArray();
        $routsArray = config('modules.hq', []);

        $this->mappingRoutes($routsArray['steward']['routes'] ?? [], 'steward');
        $this->mappingBroadcastChannels($routsArray['steward']['broadcast'] ?? [], 'steward');

        foreach ($modules as $module) {
            $this->mappingRoutes($routsArray['modules']['routes'] ?? [], $module);
            $this->mappingBroadcastChannels($routsArray['modules']['broadcast'] ?? [], $module);
        }
    }

    protected function mappingRoutes(array $routsArray, $module): void
    {
        foreach ($routsArray as $route) {

            if (empty($route['path'])) continue;

            $file = $module === 'steward'
                ? steward_path('routes' . DIRECTORY_SEPARATOR . $route['path'])
                : module_path($module, 'routes' . DIRECTORY_SEPARATOR . $route['path']);

            if (!file_exists($file)) continue;

            $middleware = $route['middleware'] ?? [];

            Route::middleware($middleware)
                 ->prefix($route['prefix'] ?? '')
                 ->name($route['name'] ?? '')
                 ->namespace($route['namespace'] ?? null)
                 ->domain($route['domain'] ?? null)
                 ->group($file);
        }
    }


    protected function mappingBroadcastChannels(array $channelFiles, string $module): void
    {
        if (!isBroadcastingInstalled()) return;

        foreach ($channelFiles as $channelFile) {
            $file = $module === 'steward'
                ? steward_path('routes' . DIRECTORY_SEPARATOR . $channelFile)
                : module_path($module, 'routes' . DIRECTORY_SEPARATOR . $channelFile);

            if (file_exists($file)) require $file;

        }
    }
}
