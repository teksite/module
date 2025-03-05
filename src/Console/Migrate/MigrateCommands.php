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

class MigrateCommands extends Command
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:migrate {module?} {--database=} {--force} {--realpath} {--pretend} {--seed} {--step} {--isolated}';

    protected $description = 'Run the migrations for a specific module or all modules';

    public function handle()
    {
        $module = $this->argument('module');

        if ($module) {
            $this->runMigrationsForModule($module);
        } else {
            $this->runMigrationsForAllModules();
        }
    }

    protected function runMigrationsForModule($module)
    {

        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) {
            $this->runMigrateCommand($module);
            return;
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->runMigrateCommand($suggestedName);
            return;
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }


    protected function runMigrationsForAllModules()
    {
        foreach (Module::all() as $module) {
            $this->info("Running migrations for module: " . $module);
            $this->runMigrationsForModule($module);
        }
    }

    protected function runMigrateCommand($module)
    {

        $options = [
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
            '--realpath' => $this->option('realpath'),
            '--pretend' => $this->option('pretend'),
            '--step' => $this->option('step'),
        ];

        $migrationsPath = module_path($module, 'Database/Migrations');
        $seedersPath = module_path($module, "Database/Seeders");

        $migration_list = File::allFiles($migrationsPath);

        $allFiles = [];
        foreach ($migration_list as $migrateFile) {
            $allFiles[] = str_replace(base_path(), '', $migrateFile);
        }
        $options['--path'] = $allFiles;

        Artisan::call('migrate', $options, $this->output);

        if ($this->option('seed') && File::exists($seedersPath)) {
            Artisan::call('module:seed', [
                '--module' => "$module"
            ], $this->output);
        }

    }
}
