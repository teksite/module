<?php

namespace Teksite\Module;

use Illuminate\Support\ServiceProvider;
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
use Teksite\Module\Console\Make\TrashControllerMakeCommand;
use Teksite\Module\Console\Make\ViewMakeCommand;
use Teksite\Module\Console\Migrate\FreshCommands;
use Teksite\Module\Console\Migrate\MigrateCommands;
use Teksite\Module\Console\Migrate\RefreshCommands;
use Teksite\Module\Console\Migrate\ResetCommands;
use Teksite\Module\Console\Migrate\RollbackCommands;
use Teksite\Module\Console\Migrate\SeedCommand;
use Teksite\Module\Console\Module\DeleteMakeCommand;
use Teksite\Module\Console\Module\ModuleDisableCommand;
use Teksite\Module\Console\Module\ModuleEnableCommand;
use Teksite\Module\Console\Module\ModuleMakeCommand;
use Teksite\Module\Providers\EventServiceProvider;
use Teksite\Module\Providers\ModuleManagerServiceProvider;
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
    }

    public function registerConfig(): void
    {
        $configPath = config_path('module.php');
        $this->mergeConfigFrom(file_exists($configPath) ? $configPath : __DIR__ . '/config/module.php', 'module');
    }

    public function registerFacades(): void
    {
        $this->app->singleton('Module', fn() => new ModuleServices());
    }

    public function registerProviders(): void
    {
        $this->app->register(ModuleManagerServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    public function registerStubPath(): void
    {
        $this->app->bind('module.stubs', function () {
            return __DIR__ . DIRECTORY_SEPARATOR . "stubs" . DIRECTORY_SEPARATOR;
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
            TrashControllerMakeCommand::class,


            /* Module -> Migration and Seeds */
            SeedCommand::class,
            MigrateCommands::class,
            RollbackCommands::class,
            FreshCommands::class,
            RefreshCommands::class,
            ResetCommands::class,


            /* Module -> Generator commands */
            ModuleMakeCommand::class,
            DeleteMakeCommand::class,
            ModuleEnableCommand::class,
            ModuleDisableCommand::class,

        ]);
    }

    public function publish(): void
    {
        $this->publishes([
            __DIR__ . '/config/module.php' => config_path('module.php')
        ], 'module');
    }
}
