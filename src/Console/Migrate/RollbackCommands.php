<?php

namespace Teksite\Module\Console\Migrate;

use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class RollbackCommands extends BasicMigrator implements MigrationContract
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:migrate-rollback {module?}
        {--step=1}
    ';

    protected $description = 'Rollback the migrations for a specific module or all modules';


    public function runTheCommand($module)
    {
        $this->rollback($module , $this->option('step'));
    }
}
