<?php

namespace Teksite\Module\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleControllerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->moduleRegistration();
    }

    public function boot()
    {

    }

    private function moduleRegistration(){
        foreach (config('modules.modules', []) as $module=>$provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

}
