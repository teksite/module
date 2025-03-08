<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Lareon\Modules\Page\Contract\MigrationContract;
use Lareon\Modules\Page\Services\BasicMigrator;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
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
