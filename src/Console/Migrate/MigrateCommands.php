<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class MigrateCommands extends Command
{
    use ModuleNameValidator , ModuleCommandsTrait;

    protected $signature = 'module:migrate {module?} {--seed}';
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

        // Run migrations
        Artisan::call('migrate', [
            '--path' => "Lareon/modules/{$module}/Database/Migrations",
            '--force' => true
        ]);

        $this->info("Migrations executed for module: {$module}");

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
