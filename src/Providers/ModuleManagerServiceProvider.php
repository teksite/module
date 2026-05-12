<?php

namespace Teksite\Module\Providers;

use Illuminate\Support\ServiceProvider;
use Teksite\Module\Facade\Module;

class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSteward();
        $this->registerModules();
    }

    public function boot(): void
    {

    }

    /**
     * @return void
     */
    public function registerSteward(): void
    {
        if (isStewardInstalled()) {
            $providerClass = config('modules.steward.steward_provider', '\\Lareon\\Steward\\App\\Providers\\StewardServiceProvider');
            $this->app->register($providerClass);
        }
    }

    /**
     * @return void
     */
    public function registerModules(): void
    {
        $modules = Module::registeredModules();
        foreach ($modules as $module => $info) {
            $providerClass = $info['provider'];
            $type = $module['type'] ?? 'self';

            if (!class_exists($providerClass)) continue;

            if (config('modules.boot_all_modules', 1) === 1) {
                if ($info['active']) {
                    $this->app->register($providerClass);
                }
            } else {
                $this->app->register($providerClass);
            }
        }
    }
}
