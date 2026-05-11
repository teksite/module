<?php

namespace Teksite\Module\Console\Migrate;

use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class RollbackCommands extends BasicMigrator implements MigrationContract
{

    protected $signature = 'module:migrate-rollback {module?}
        {--step=1}
    ';

    protected $description = 'Rollback the migrations for a specific module or all modules';


    public function runTheCommand()
    {
        $this->rollback($this->option('step'));
    }

    protected function handler(array $modules): int
    {
        // TODO: Implement handler() method.
    }
}
