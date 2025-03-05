<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class MigrateCommands extends Command
{
    use ModuleNameValidator , ModuleCommandsTrait;

    protected $signature = 'module:migrate {module?} {--seed}
            {--d|direction=asc : The direction of ordering}
            {--database= : The database connection to use}
            {--pretend : Dump the SQL queries that would be run}
            {--force : Force the operation to run when in production}
            {--seed : Indicates if the seed task should be re-run}
            {--subpath=  : Indicate a subpath to run your migrations fro},
    ';
    protected $description = 'Run migrations for a specific module or all modules';

    public function handle()
    {
        $module = $this->argument('module');
        $seed = $this->option('seed');

        $modulesPath = module_path();

        // If a specific module is provided, migrate it
        if ($module) {
            $this->migrateModule($modulesPath, $module, $seed);
        } else {
            // Otherwise, migrate all modules
            $this->migrateAllModules($modulesPath, $seed);
        }
    }

    protected function migrateModule($modulesPath, $module, $seed)
    {
        $migrationsPath = "{$modulesPath}/{$module}/Database/Migrations";
        $seedersPath = "{$modulesPath}/{$module}/Database/Seeders";

        if (!File::exists($migrationsPath)) {
            $this->error("Module '{$module}' does not have a Migrations directory.");
            return;
        }
        $migration_list=File::allFiles($migrationsPath);

        $allFiles = [];

        foreach ($migration_list as $path) {
            $files = pathinfo($path);
            $allFiles[] = $migrationsPath.'/'.$files['basename'];
        }


        // Run migrations
        Artisan::call('migrate', [
            '--path' => $allFiles,
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
            '--realpath' => true,
        ]);

        $this->components->twoColumnDetail("Running Migration <fg=green;options=bold>{$module}</> Module" ,'fdgdf');

        // Run seeders if --seed option is provided
        if ($seed && File::exists($seedersPath)) {
            Artisan::call('module:seed', [
                '--module' => "$module"
            ]);
            $this->info("Seeders executed for module: {$module}");
        }
    }

    protected function migrateAllModules($modulesPath, $seed)
    {
        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $module = basename($modulePath);
            $this->migrateModule($modulesPath, $module, $seed);
        }
    }
}
