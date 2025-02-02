<?php

namespace Teksite\Module;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider;
use Teksite\Module\Console\Installer\InstallerCommand;
use Teksite\Module\Console\Make\CastMakeCommand;
use Teksite\Module\Console\Make\ChannelMakeCommand;
use Teksite\Module\Console\Make\ClassMakeCommand;
use Teksite\Module\Console\Make\CommandMakeCommand;
use Teksite\Module\Console\Make\ComponentMakeCommand;
use Teksite\Module\Console\Make\ControllerMakeCommand;
use Teksite\Module\Console\Make\EnumMakeCommand;
use Teksite\Module\Console\Make\EventMakeCommand;
use Teksite\Module\Console\Make\ExceptionMakeCommand;
use Teksite\Module\Console\Make\FactoryMakeCommand;
use Teksite\Module\Console\Make\InterfaceMakeCommand;
use Teksite\Module\Console\Make\JobMakeCommand;
use Teksite\Module\Console\Make\JobMiddlewareMakeCommand;
use Teksite\Module\Console\Make\ListenerMakeCommand;
use Teksite\Module\Console\Make\LogicMakeCommand;
use Teksite\Module\Console\Make\MailMakeCommand;
use Teksite\Module\Console\Make\MiddlewareMakeCommand;
use Teksite\Module\Console\Make\MigrationMakeCommand;
use Teksite\Module\Console\Make\ModelMakeCommand;
use Teksite\Module\Console\Make\NotificationMakeCommand;
use Teksite\Module\Console\Make\ObserverMakeCommand;
use Teksite\Module\Console\Make\PolicyMakeCommand;
use Teksite\Module\Console\Make\ProviderMakeCommand;
use Teksite\Module\Console\Make\RequestMakeCommand;
use Teksite\Module\Console\Make\ResourceMakeCommand;
use Teksite\Module\Console\Make\RuleMakeCommand;
use Teksite\Module\Console\Make\ScopeMakeCommand;
use Teksite\Module\Console\Make\SeederMakeCommand;
use Teksite\Module\Console\Make\TestMakeCommand;
use Teksite\Module\Console\Make\TraitMakeCommand;
use Teksite\Module\Console\Make\ViewMakeCommand;
use Teksite\Module\Console\Migrate\SeedCommand;
use Teksite\Module\Console\Module\DeleteMakeCommand;
use Teksite\Module\Console\Module\ModuleMakeCommand;
use Teksite\Module\Providers\ModulesManagerServiceProvider;
use Teksite\Module\Providers\RoutesManagerServiceProvider;
use Teksite\Module\Services\ManagerServices;
use Teksite\Module\Services\ModuleServices;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerFacades();
        $this->registerProviders();
        $this->registerStubPath();
    }

    public function boot(): void
    {
        $this->bootCommands();
        $this->publish();
        $this->bootTranslations();

    }

    public function registerConfig(): void
    {
        //Module configuration
        $configPath = config_path('moduleconfigs.php'); // Path to the published file
        $this->mergeConfigFrom(
            file_exists($configPath) ? $configPath : __DIR__ . '/config/moduleconfigs.php', 'moduleconfigs');

        //Modules Priority
        $modulesConfigPath = config_path('modules.php'); // Path to the published file
        $this->mergeConfigFrom(
            file_exists($modulesConfigPath) ? $modulesConfigPath : __DIR__ . '/config/modules.php', 'modules');
    }

    public function registerFacades()
    {
        $this->app->singleton('Module', function () {
            return new ModuleServices();
        });
        $this->app->singleton('ModuleManager', function () {
            return new ManagerServices();
        });
    }

    public function registerProviders(): void
    {
        $this->app->register(ModulesManagerServiceProvider::class);
        $this->app->register(RoutesManagerServiceProvider::class);
    }

    public function registerStubPath(): void
    {
        $this->app->bind('module.stubs', function () {
            return config('moduleconfigs.', __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR);
        });

    }

    public function bootCommands(): void
    {
        $this->commands([
            CastMakeCommand::class,
            ChannelMakeCommand::class,
            ClassMakeCommand::class,
            CommandMakeCommand::class,
            ComponentMakeCommand::class,
            ControllerMakeCommand::class,
            EnumMakeCommand::class,
            EventMakeCommand::class,
            ExceptionMakeCommand::class,
            FactoryMakeCommand::class,
            InterfaceMakeCommand::class,
            JobMakeCommand::class,
            JobMiddlewareMakeCommand::class,
            ListenerMakeCommand::class,
            LogicMakeCommand::class,
            MailMakeCommand::class,
            MiddlewareMakeCommand::class,
            MigrationMakeCommand::class,
            ModelMakeCommand::class,
            NotificationMakeCommand::class,
            ObserverMakeCommand::class,
            PolicyMakeCommand::class,
            ProviderMakeCommand::class,
            RequestMakeCommand::class,
            ResourceMakeCommand::class,
            RuleMakeCommand::class,
            ScopeMakeCommand::class,
            SeederMakeCommand::class,
            TestMakeCommand::class,
            TraitMakeCommand::class,
            ViewMakeCommand::class,


            /* Module -> Migration and Seeds */
            SeedCommand::class,


            /* Module -> Generator commands */
            ModuleMakeCommand::class,
            DeleteMakeCommand::class,

            InstallerCommand::class,
        ]);
    }

    public function publish(): void
    {
        $this->publishes([
            __DIR__ . '/config/moduleconfigs.php' => config_path('moduleconfigs.php')
        ], 'moduleconfigs');

        $this->publishes([
            __DIR__ . '/config/modules.php' => config_path('modules.php')
        ], 'modules');

    }

    public function bootTranslations(): void
    {
        $langPath = __DIR__ . '/lang/';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'module');
            $this->loadJsonTranslationsFrom($langPath);
        }
    }
}
