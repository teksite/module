<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleNameValidator;

class SeedCommand extends BasicMigrator implements MigrationContract
{
    use ModuleNameValidator;

    protected $signature = 'module:seed
        {module? : The module to seed.}
    ';

    protected $description = 'Seed the module';

    protected $type = 'Seed';

    public function runTheCommand()
    {
        $this->seeding();
    }
}
