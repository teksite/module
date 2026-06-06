<?php

namespace Teksite\Module\Providers\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ModulesHeadquarterServiceProvider extends ServiceProvider
{

    protected array $cachedAvailableModule = [];

    public function register(): void
    {

    }

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
        if (!empty($this->cachedAvailableModule)) {
            return $this->cachedAvailableModule;
        }
        $modules = get_enabled_modules();
        $modules = array_filter($modules, function ($module) {
            return ($module['type'] ?? 'self') === 'steward';
        });
        $result = [];
        foreach (array_keys($modules) as $module) {
            $result[$module] = strtolower($module);
        }
        $this->cachedAvailableModule = $result;
        return $result;
    }

    /**
     * boot translations.
     */
    protected function bootTranslations(): void
    {
        foreach ($this->getAvailableModules() as $module => $name) {
            $langPath = resource_path('lang/modules/' . $name);

            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, $name);
                $this->loadJsonTranslationsFrom($langPath);
            } else {
                $moduleLangPath = module_path($name, config('modules.module.lang_path', 'lang'));
                $this->loadTranslationsFrom($moduleLangPath , $name);
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

            if (is_dir($configPath)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                        $configKey = str_replace(DIRECTORY_SEPARATOR, '.', $config);
                        $configKey = str_replace('.php', '', $configKey);

                        $segments = explode('.', $name . '.' . $configKey);

                        // Remove duplicated adjacent segments
                        $normalized = [];
                        foreach ($segments as $segment) {
                            if (end($normalized) !== $segment) {
                                $normalized[] = $segment;
                            }
                        }

                        $key = ($config === 'config.php') ? $name : implode('.', $normalized);
                        $publishPath = ($config === 'config.php') ? config_path($name . '.php') : config_path($config);
                        $this->publishes([$file->getPathname() => $publishPath], 'config');

                        $this->merge_config_from($file->getPathname(), $key);
                    }
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        if (app()->configurationIsCached()) {
            return;
        }

        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }

    /**
     * Register views.
     */
    protected function bootViews(): void
    {
        foreach ($this->getAvailableModules() as $module => $name) {

            $viewPath = resource_path('views/modules/' . $name);
            $sourcePath = module_path($module, config('modules.module.view', 'resources/views'));

            $this->publishes([$sourcePath => $viewPath], ['views', $name . '-module-views']);
            $this->loadViewsFrom(array_merge($this->publishableViewPaths($name), [$sourcePath]), $name);

            $componentNamespace = module_namespace($module) . '\\App\\View\\Components';

            Blade::componentNamespace($componentNamespace, $name);
        }
    }


    /**
     * Get the paths where the module views are published.
     */
    protected function publishableViewPaths(string $name): array
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
            $this->loadMigrationsFrom(module_path($name, $generatorMigrationPath));
        }
    }
}
