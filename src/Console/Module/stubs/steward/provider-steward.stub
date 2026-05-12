<?php

namespace Lareon\Steward\App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Teksite\Module\Providers\Support\StewardServiceProvider as ServiceProvider;

class StewardServiceProvider extends ServiceProvider
{

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [];

    /**
     * Define module schedules.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // ...
    }

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RoutesHeadquarterServiceProvider::class,
        ModulesHeadquarterServiceProvider::class,
    ];


    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * register the application events.
     */
    public function register(): void
    {
        parent::register();
    }
}
