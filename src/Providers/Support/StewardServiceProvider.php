<?php

namespace Teksite\Module\Providers\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StewardServiceProvider extends ServiceProvider
{

    /**
     * The name of the module.
     *
     * @var string
     */
    protected string $moduleName = 'Steward';

    /**
     * The lowercase version of the module name.
     *
     * @var string
     */
    protected string $lowerModuleName = 'steward';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [];

    /**
     * Register the service providers.
     */
    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }


    public function boot(): void
    {
            $this->bootCommands();
            $this->bootCommandSchedules();
            $this->bootTranslations();
            $this->bootConfig();
            $this->bootViews();
            $this->bootMigrations();
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function bootCommands(): void
    {
        $this->commands($this->commands ?? []);
    }

    /**
     * Register command Schedules.
     */
    protected function bootCommandSchedules(): void
    {
        if (!method_exists($this, 'configureSchedules')) return;

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $this->configureSchedules($schedule);
        });
    }

    /**
     * Define module schedules.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * boot translations.
     */
    protected function bootTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->lowerModuleName);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->lowerModuleName);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $moduleLangPath = steward_path(config('modules.steward.lang_path', 'lang'));
            $this->loadTranslationsFrom($moduleLangPath , $this->lowerModuleName);
            $this->loadJsonTranslationsFrom($moduleLangPath);
        }
    }

    /**
     * Register config.
     */
    protected function bootConfig(): void
    {
        $configPath = steward_path(config('modules.steward.config_path', 'config'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = str_replace(DIRECTORY_SEPARATOR, '.', $config);
                    $configKey = str_replace('.php', '', $configKey);

                    $segments = explode('.', $this->lowerModuleName . '.' . $configKey);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->lowerModuleName : implode('.', $normalized);
                    $publishPath = ($config === 'config.php') ? config_path($this->lowerModuleName . '.php') : config_path($config);
                    $this->publishes([$file->getPathname() => $publishPath], 'config');

                    $this->merge_config_from($file->getPathname(), $key);
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
        $viewPath = resource_path('views/modules/' . $this->lowerModuleName);
        $sourcePath = steward_path( config('modules.steward.view', 'resources/views'));

        $this->publishes([$sourcePath => $viewPath], ['views', $this->lowerModuleName . '-module-views']);
        $this->loadViewsFrom(array_merge($this->publishableViewPaths(), [$sourcePath]), $this->lowerModuleName);

        $componentNamespace = steward_namespace() . '\\App\\View\\Components';

        Blade::componentNamespace($componentNamespace, $this->lowerModuleName);
    }


    /**
     * Get the paths where the module views are published.
     */
    protected function publishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->lowerModuleName)) {
                $paths[] = $path . '/modules/' . $this->lowerModuleName;
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
        $generatorMigrationPath = config('modules.steward.migration_path') ?? 'database/migrations';
        $this->loadMigrationsFrom(steward_path($generatorMigrationPath));
    }
}
