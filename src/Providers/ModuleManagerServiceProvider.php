<?php

namespace Teksite\Module\Providers;

use Illuminate\Support\ServiceProvider;
use Teksite\Module\Facade\Module;


class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modules = Module::registeredModules();
        foreach ($modules as $module=>$info) {
            if ($info['active']){
                $providerClass = $info['provider'];
                $type = $module['type'] ?? 'self';
                if (class_exists($providerClass)) $this->app->register($providerClass);
            }
        }
    }

    public function boot(): void
    {

    }
}
