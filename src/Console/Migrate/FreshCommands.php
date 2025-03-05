<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class FreshCommands extends Command
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:migrate-fresh {module?}
        {--database=}
        {--force}
        {--path=}
        {--realpath}
        {--pretend}
        {--step=1}
    ';

    protected $description = 'Rollback the migrations for a specific module or all modules';

    public function handle()
    {
        $module = $this->argument('module');

        if ($module) {
            $this->rollbackMigrationsForModule($module);
        } else {
            $this->rollbackMigrationsForAllModules();
        }
    }

    protected function rollbackMigrationsForModule($module)
    {

        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) {
            $this->runRollbackCommand($module);
            return;
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->runRollbackCommand($suggestedName);
            return;
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }


    protected function rollbackMigrationsForAllModules()
    {
        foreach (Module::all() as $module) {
            $this->info("Running migrations for module: " . $module);
            $this->rollbackMigrationsForModule($module);
        }
    }

    protected function runRollbackCommand($module)
    {

        $options = [
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
            '--realpath' => $this->option('realpath'),
            '--pretend' => $this->option('pretend'),
            '--step' => $this->option('step'),
        ];

        $migrationsPath = module_path($module, 'Database/Migrations');

        $migration_list = File::allFiles($migrationsPath);

        $allFiles = [];
        foreach ($migration_list as $migrateFile) {
            $allFiles[] = str_replace(base_path(), '', $migrateFile);
        }
        $options['--path'] = $allFiles;

        Artisan::call('migrate:rollback', $options, $this->output);


    }
}
