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

class MigrateCommands extends  BasicMigrator implements MigrationContract
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:migrate {module?}  {--seed}';

    protected $description = 'Run the migrations for a specific module or all modules';

    public function handle()
    {
       parent::handle();
       if ($this->option('seed')) $this->seeding();

    }

    public function runTheCommand($module)
    {
        $this->up($module);
    }

}
