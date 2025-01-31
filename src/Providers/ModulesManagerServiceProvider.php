<?php

namespace Teksite\Module\Providers;

use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Teksite\Module\Facade\Module;

class ModulesManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerModules();
        $this->app->register(RoutesManagerServiceProvider::class);
    }

    public function boot(): void
    {
        $this->registerConfigs();
        $this->registerTranslations();
        $this->registerViews();
        $this->loadModuleMigrations();
        $this->loadModuleSeeders();
    }

    /**
     * Registers all modules specified in the config.
     */
    private function registerModules(): void
    {
        $modules = config('modules.modules', []);
        foreach ($modules as $module => $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Publishes and merges the configuration files for all modules.
     */
    private function registerConfigs(): void
    {
        $modules = config('modules.modules', []);
        $configsFromConfig = config('modules.configs', []);

        foreach ($modules as $module => $provider) {
            if (!$this->isSelfProvider($provider)) {
                $this->processConfigs($module, $configsFromConfig);
            }
        }
    }

    /**
     * Determines if the module provider is of type 'self'.
     *
     * @param string $provider
     * @return bool
     */
    private function isSelfProvider(string $provider): bool
    {
        return defined("$provider::TYPE") && $provider::TYPE === 'self';
    }

    /**
     * Processes the configuration files for a given module.
     */
    private function processConfigs(string $module, array $configs): void
    {
        $lowerModuleName = strtolower($module);
        foreach ($configs as $config) {
            $this->publishAndMergeConfig($module, $config, $lowerModuleName);
        }
    }

    /**
     * Publishes and merges a configuration file.
     */
    private function publishAndMergeConfig(string $module, string $config, string $lowerModuleName): void
    {
        $suggestedConfigPath = module_path($module, "config/$config");
        $configName = str_replace('.php', '', $config);

        if (file_exists($suggestedConfigPath)) {
            $this->publishes([$suggestedConfigPath => config_path("$lowerModuleName.php")], $configName);
            $this->mergeConfigFrom($suggestedConfigPath, "$configName.$lowerModuleName");
        }
    }

    /**
     * Registers translations for modules.
     */
    public function registerTranslations(): void
    {
        $modules = config('modules.modules', []);

        foreach ($modules as $module => $provider) {
            if (!$this->isSelfProvider($provider)) {
                $this->processTranslations($module);
            }
        }
    }

    /**
     * Processes translation files for a given module.
     */
    private function processTranslations(string $module): void
    {
        $lowerModuleName = strtolower($module);
        $langPath = module_path($module, config('moduleconfigs.module.lang_path', 'lang'));
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $lowerModuleName);
            $this->loadJsonTranslationsFrom($langPath);
        }
    }

    /**
     * Registers views for modules.
     */
    public function registerViews(): void
    {
        $modules = config('modules.modules', []);

        foreach ($modules as $module => $provider) {
            if (!$this->isSelfProvider($provider)) {
                $this->processViews($module);
            }
        }
    }

    /**
     * Processes views for a given module.
     */
    private function processViews(string $module): void
    {
        $lowerModuleName = strtolower($module);
        $viewPath = resource_path('views/modules/' . $lowerModuleName);
        $sourcePath = module_path($module, 'resources/views');

        // Publish and load views
        $this->publishes([$sourcePath => $viewPath], ['views', $lowerModuleName . '-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths($lowerModuleName), [$sourcePath]), $lowerModuleName);

        // Register Blade components
        $this->registerBladeComponents($module, $lowerModuleName);
    }

    /**
     * Registers Blade components for the module.
     */
    private function registerBladeComponents(string $module, string $lowerModuleName): void
    {
        $componentNamespace = module_namespace($module, module_path($module, 'App/View/Components'));
        Blade::componentNamespace($componentNamespace, $lowerModuleName);
    }

    /**
     * Returns an array of publishable view paths for a given module.
     */
    private function getPublishableViewPaths(string $lowerModuleName): array
    {
        return array_filter(config('view.paths'), fn($path) => is_dir($path . '/modules/' . $lowerModuleName));
    }

    /**
     * Loads module migrations.
     */
    private function loadModuleMigrations(): void
    {
        $modules = config('modules.modules', []);

        foreach ($modules as $module => $provider) {
            if (!$this->isSelfProvider($provider)) {
                $this->loadMigrationsForModule($module);
            }
        }
    }

    /**
     * Loads migrations for a specific module.
     */
    private function loadMigrationsForModule(string $module): void
    {
        $migrationPath = Module::modulePath($module, config('moduleconfigs.module.database.migration_path', 'Database/Migrations'));
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Listens for migration events to run seeders.
     */
    private function loadModuleSeeders(): void
    {
        Event::listen(MigrationsEnded::class, fn() => $this->runModuleSeeders());
    }

    /**
     * Runs the seeders for all modules after migrations.
     */
    private function runModuleSeeders(): void
    {
        $commands = $_SERVER['argv'] ?? [];
        if (in_array('db:seed', $commands) || in_array('--seed', $commands)) {
            $this->seedModules();
        }
    }

    /**
     * Seeds all modules that have seeders defined.
     */
    private function seedModules(): void
    {
        $modules = config('modules.modules', []);
        foreach ($modules as $module => $provider) {
            if (!$this->isSelfProvider($provider)) {
                $this->runSeederForModule($module);
            }
        }
    }

    /**
     * Runs the seeder for a specific module.
     */
    private function runSeederForModule(string $module): void
    {
        $fullClassName = "Lareon\\Modules\\{$module}\\Database\\Seeders\\{$module}DatabaseSeeder";
        $mainSeederPath = Module::modulePath($module, "Database/Seeders/{$module}DatabaseSeeder.php");

        if (file_exists($mainSeederPath) && class_exists($fullClassName)) {
            Artisan::call('db:seed', ['--class' => $fullClassName]);
        }
    }
}
