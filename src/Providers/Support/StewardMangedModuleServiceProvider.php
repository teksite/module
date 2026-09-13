<?php

namespace Teksite\Module\Providers\Support;

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
