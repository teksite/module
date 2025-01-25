<?php

namespace Teksite\Module;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider;
use Teksite\Module\Console\Make\CastMakeCommand;
use Teksite\Module\Console\Make\ChannelMakeCommand;
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
use Teksite\Module\Console\Module\DeleteMakeCommand;
use Teksite\Module\Console\Module\ModuleMakeCommand;
use Teksite\Module\Providers\ModuleControllerServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->app->bind('lareon.stub', function () {
            return __DIR__ . '/stubs/';
        });
        $this->registerProviders();
    }

    public function boot(): void
    {
        $this->bootCommands();
        $this->publish();
        $this->bootTranslations();

    }

    protected function loadHelpers()
    {

        $helpers = __DIR__ . '/helpers/functions.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }
    }

    public function registerConfig(): void
    {
        $lareonConfigPath = config_path('lareon.php'); // Path to the published file
        $modulesConfigPath = config_path('modules.php'); // Path to the published file
        $this->mergeConfigFrom(
            file_exists($lareonConfigPath) ? $lareonConfigPath : __DIR__ . '/config/lareon.php', 'lareon');

//        $this->mergeConfigFrom(
//            file_exists($modulesConfigPath) ? $modulesConfigPath : __DIR__ . '/config/modules.php', 'modules');


        $this->app->extend(MigrationCreator::class, function ($creator, $app) {
            return new MigrationCreator($app['files'], __DIR__ . '/stubs');
        });
    }

    public function registerProviders(): void
    {
        $this->app->register(ModuleControllerServiceProvider::class);
    }


    public function bootCommands(): void
    {
        $this->commands([

            CastMakeCommand::class,
            ChannelMakeCommand::class,
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


            /* Module -> Generator commands */
            ModuleMakeCommand::class,
            DeleteMakeCommand::class,
        ]);
    }

    public function publish(): void
    {
        $this->publishes([
            __DIR__ . '/config/lareon.php' => config_path('lareon.php')
        ], 'lareon');
        $this->publishes([
            __DIR__ . '/config/modules.php' => config_path('modules.php')
        ], 'modules');

    }

    public function bootTranslations(): void
    {
        $langPath = __DIR__ . '/lang/';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'lareon');
            $this->loadJsonTranslationsFrom($langPath);
        }
    }
}
