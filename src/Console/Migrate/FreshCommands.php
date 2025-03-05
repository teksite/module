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
        {--seed}
        {--force}
    ';

    protected $description = 'Drop all tables and re-run migrations for a specific module or all modules';

    public function handle()
    {
        $module = $this->argument('module');

        if ($module) {
            $this->freshMigrationsForModule($module);
        } else {
            $this->freshMigrationsForAllModules();
        }
    }

    protected function freshMigrationsForModule($module)
    {

        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) {
            $this->runMigrationsForModule($module);
            return;
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->runMigrationsForModule($suggestedName);
            return;
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }


    protected function freshMigrationsForAllModules()
    {
        foreach (Module::all() as $module) {
            $this->info("Dropping all tables and re-running migrations for module: " . $module);
            $this->runMigrationsForModule($module);
        }
    }

    protected function runMigrationsForModule($module)
    {

        $options = [
            '--database' => $this->option('database'),
            '--force' => true,
        ];

        $migrationsPath = module_path($module, 'Database/Migrations');
        $seedersPath = module_path($module, "Database/Seeders");

        $migration_list = File::allFiles($migrationsPath);

        $allFiles = [];
        foreach ($migration_list as $migrateFile) {
            $allFiles[] = str_replace(base_path(), '', $migrateFile);
        }
        $options['--path'] = $allFiles;

        Artisan::call('migrate:fresh', $options, $this->output);

        if ($this->option('seed') && File::exists($seedersPath)) {
            Artisan::call('module:seed', [
                '--module' => "$module"
            ], $this->output);
        }
    }
}
