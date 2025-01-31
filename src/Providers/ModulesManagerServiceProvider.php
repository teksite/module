<?php

namespace Teksite\Module\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ModulesManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerModules();
        $this->app->register(RoutesManagerServiceProvider::class);
        $this->registerConfigs();
    }

    public function boot()
    {
        $this->registerTranslations();
        $this->registerViews();
    }

    private function registerModules(): void
    {
        $modules = config('modules.modules', []);
        foreach ($modules ?? [] as $module => $provider) {
            $this->app->register($provider);
        }
    }

    private function registerConfigs(): void
    {
        $modules = config('modules.modules', []);

        $configsFromConfig = config('modules.configs', []);
        foreach ($modules ?? [] as $module => $provider) {
            if (!defined("$provider::TYPE") || $provider::TYPE !== 'self') {
                $loweModuleName = strtolower($module);

                foreach ($configsFromConfig as $config) {
                    $suggestedConfigPath = module_path($module, "config/$config");

                    $configName = str_replace('.php', '', $config);

                    if (file_exists($suggestedConfigPath)) {

                        $this->publishes([$suggestedConfigPath => config_path($loweModuleName . '.php')], $configName);

                        $this->mergeConfigFrom($suggestedConfigPath, $configName . '.' . $loweModuleName);

                    }
                }

            }
        }
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {

        $modules = config('modules.modules', []);
        $configsFromConfig = config('modules.configs', []);

        foreach ($modules ?? [] as $module => $provider) {
            if (!defined("$provider::TYPE") || $provider::TYPE !== 'self') {
                $loweModuleName = strtolower($module);

                foreach ($configsFromConfig as $config) {
                    $suggestedConfigPath = module_path($module, "config/$config");
                    $langPath = module_path($module, config('moduleconfigs.modules.lang_path', 'lang'));
                    if (is_dir($langPath)) {
                        $this->loadTranslationsFrom($langPath, $loweModuleName);
                        $this->loadJsonTranslationsFrom($langPath);
                    }
                }

            }
        }


    }


    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $modules = config('modules.modules', []);

        foreach ($modules ?? [] as $module => $provider) {
            if (!defined("$provider::TYPE") || $provider::TYPE !== 'self') {
                $loweModuleName = strtolower($module);

                // Path to the module's custom views directory
                $viewPath = resource_path('views/modules/'.$loweModuleName);

                // Path to the module's source views directory
                $sourcePath = module_path($module, 'resources/views');

                // Publish the module views to the main application views folder
                $this->publishes([$sourcePath => $viewPath], ['views', $loweModuleName.'-module-views']);

                // Merge the default view paths with the module's custom views
                $this->loadViewsFrom(array_merge($this->getPublishableViewPaths($loweModuleName), [$sourcePath]), $loweModuleName);

                // Optionally, register Blade components if your module uses them
                $componentNamespace = module_namespace(
                    $module,module_path($module, 'App/View/Components'));


                Blade::componentNamespace($componentNamespace, $loweModuleName);

            }
        }

    }

    private function getPublishableViewPaths($lowerModuleName): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$lowerModuleName)) {
                $paths[] = $path.'/modules/'.$lowerModuleName;
            }
        }
        return $paths;
    }

}
