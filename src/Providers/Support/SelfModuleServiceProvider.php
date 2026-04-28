<?php

namespace Teksite\Module\Providers\Support;

class SelfModuleServiceProvider extends BaseModuleServiceProvider
{

    /**
     *  module type (self|steward)
     *
     * @var string
     */
    protected string $type = "self";


    public function boot(): void
    {

        $this->bootCommands();
        $this->bootCommandSchedules();
        $this->bootTranslations();
        $this->bootConfig();
        $this->bootViews();
        $this->bootMigrations();
    }


}
