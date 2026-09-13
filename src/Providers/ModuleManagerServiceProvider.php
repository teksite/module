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

    public function boot(): void {}

    /**
     * @return void
     */
    public function registerSteward(): void
    {
        if (!isStewardInstalled()) return;

        $providerClass = config('modules.steward.steward_provider', '\\Lareon\\Steward\\App\\Providers\\StewardServiceProvider');
        $this->app->register($providerClass);
    }

    /**
     * @return void
     */
    public function registerModules(): void
    {
        $bootOnlyActiveModules = config('modules.boot_all_modules', 1);

        foreach (Module::availableModules() as $moduleName => $info) {

            $provider = $info['provider'] ?? null;

            if (!$provider || !class_exists($provider)) continue;

            $isActive = $info['active'] ?? false;

            if ($bootOnlyActiveModules && !$isActive) continue;

            $this->app->register($provider);
        }
    }
}
