<?php

namespace Teksite\Module\Providers\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Teksite\Module\Providers\Support\Concerns\PublishesModuleConfig;

class ModulesHeadquarterServiceProvider extends ServiceProvider
{
    use PublishesModuleConfig;

    protected ?array $cachedAvailableModule = null;

    public function register(): void {}

    public function boot(): void
    {
        if (!isStewardInstalled()) return;

        $this->bootTranslations();
        $this->bootConfig();
        $this->bootViews();
        $this->bootMigrations();

    }

    private function getAvailableModules(): array
    {
        if (!empty($this->cachedAvailableModule)) return $this->cachedAvailableModule;

        $modules = array_filter(get_enabled_modules(), function ($module,) {
            return ($module['type'] ?? 'self') === 'steward';
        });

        $result = [];
        foreach (array_keys($modules) as $module) {
            $result[$module] = strtolower($module);
        }
        return $this->cachedAvailableModule = $result;
    }

    /**
     * boot translations.
     */
    protected function bootTranslations(): void
    {
        foreach ($this->getAvailableModules() as $module => $name) {
            $langPath = resource_path('lang/modules/'.$name);

            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, $name);
                $this->loadJsonTranslationsFrom($langPath);
            } else {
                $moduleLangPath = module_path($module, config('modules.module.lang_path', 'lang'));
                $this->loadTranslationsFrom($moduleLangPath, $name);
                $this->loadJsonTranslationsFrom($moduleLangPath);
            }
        }
    }

    /**
     * Register config.
     */
    protected function bootConfig(): void
    {
        foreach ($this->getAvailableModules() as $module => $name) {
            $configPath = module_path($module, config('modules.module.config_path', 'config'));
            $this->publishModuleConfig($configPath, $name);
        }
    }

    /**
     * Register views.
     *
     * @throws \Exception
     */
    protected function bootViews(): void
    {
        foreach ($this->getAvailableModules() as $module => $name) {

            $viewPath = resource_path('views/modules/'.$name);
            $sourcePath = module_path($module, config('modules.module.view', 'resources/views'));

            $this->publishes([$sourcePath => $viewPath], ['views', $name.'-module-views']);
            $this->loadViewsFrom(array_merge($this->publishableViewPaths($name), [$sourcePath]), $name);

            $componentNamespace = module_namespace($module).'\\App\\View\\Components';

            Blade::componentNamespace($componentNamespace, $name);
        }
    }

    /**
     * Get the paths where the module views are published.
     */
    protected function publishableViewPaths(string $name,): array
    {
        $paths = [];

        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $name)) {
                $paths[] = $path . '/modules/' . $name;
            }
        }
        return $paths;
    }

    /**
     * boot migration file
     *
     * @return void
     */
    protected function bootMigrations(): void
    {
        foreach ($this->getAvailableModules() as $module => $name) {
            $generatorMigrationPath = config('modules.module.migration_path') ?? 'database/migrations';
            $this->loadMigrationsFrom(module_path($module, $generatorMigrationPath));
        }
    }
}
