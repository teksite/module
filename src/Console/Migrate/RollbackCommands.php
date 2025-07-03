<?php

namespace Teksite\Module\Console\Migrate;

use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class RollbackCommands extends BasicMigrator implements MigrationContract
{
    use ModuleCommandsTrait;

    protected $signature = 'module:migrate-rollback {module?}
        {--step=1}
    ';

    protected $description = 'Rollback the migrations for a specific module or all modules';


    public function runTheCommand()
    {
        $this->rollback($this->option('step'));
    }
}
