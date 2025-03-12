<?php

namespace Teksite\Module\Console\Migrate;

use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class MigrateCommands extends BasicMigrator implements MigrationContract
{

    protected $signature = 'module:migrate {module?}  {--seed}';

    protected $description = 'Run the migrations for a specific module or all modules';

    public function handle(): void
    {
       parent::handle();
       if ($this->option('seed')) $this->seeding();

    }

    public function runTheCommand(): void
    {
        $this->up();
    }

}
