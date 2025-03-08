<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lareon\Modules\Page\Contract\MigrationContract;
use Lareon\Modules\Page\Services\BasicMigrator;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ResetCommands extends BasicMigrator implements MigrationContract
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:migrate-reset {module?}
        ';

    protected $description = 'Rollback migrations for a specific module or all modules';

    public function runTheCommand($module): void
    {
        $this->down($module);
    }
}
