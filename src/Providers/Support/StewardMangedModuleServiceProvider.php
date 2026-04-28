<?php

namespace Teksite\Module\Providers\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StewardMangedModuleServiceProvider extends BaseModuleServiceProvider
{

    /**
     *  module type (self|steward)
     *
     * @var string
     */
    protected string $type = "steward";


    public function boot(): void
    {

        $this->bootCommands();
        $this->bootCommandSchedules();
    }
}
