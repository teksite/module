<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\Migration\ModuleMigrationTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class BasicMigrator extends Command
{
    use ModuleNameValidator , ModuleMigrationTrait;

    /**
     * @return array
     */
    public function getModules(): array
    {
        $module = $this->option('module');
        return $module ? [$module] : Module::all();
    }


    /**
     * @param $moules
     * @return false|string
     */
    protected function checkModule($moules): false|string
    {
        [$isValid, $suggestedName] = $this->validateModuleName($moules);
        if ($isValid) {
            return $moules;
        }
        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            return $suggestedName;
        }
        return false;
    }


    public function handle()
    {
        $this->installMigrateTable();

        $modules = $this->getModules();
        if (count($modules)) {
            $mdls = [];
            foreach ($modules as $module) {
                $correctedModule = $this->checkModule($module);
                if (!$correctedModule) {
                    $this->error("The module '" . $module . "' does not exist.");
                    return 0;
                } else {
                    $mdls[] = $correctedModule;
                }
            }
            $this->runTheCommand();
        } else {
            $this->error("no module exist.");
            return 0;
        }
        $this->newLine();
    }


    /**
     * @param $file
     * @return mixed
     */
    public function resolve($file): mixed
    {
        return class_exists($file) ? new $file : include $file;
    }

    /**
     * @param string|array $module
     * @return void
     */
    public function down(): void
    {
        $this->downModules();
    }

    /**
     * @param $module
     * @return void
     */
    public function up(): void
    {

        $module = is_array($module) ? $module : [$module];
        $batch = DB::table('migrations')->max('batch') + 1;
        $migrationsRecords = $this->migrationTableRecord();

        foreach ($module as $mdl) {
            $this->warn("migrating all tables of module: " . $mdl);
            foreach ($this->migrationFiles($mdl) as $migration) {
                if (!in_array($migration['name'], $migrationsRecords)) {
                    $this->runAndCalculate(function () use ($batch, $migration) {
                        $class = $this->resolve($migration['path']);
                        $class->up();
                        $this->addToMigrationTable($migration['name'], $batch);
                    }, $migration['name']);
                }
            }
        }

    }

    public function rollback($module, int $step = 1): void
    {
        $modules = is_array($module) ? $module : [$module];

        $moduleMigrations = collect($modules)
            ->flatMap(fn($mdl) => collect($this->migrationFiles($mdl))->select(['path', 'name', 'path']))
            ->unique();

        if (empty($moduleMigrations->pluck('name'))) {
            $this->info("No migrations found for rollback.");
            return;
        }

        $records = DB::table('migrations')
            ->whereIn('migration', $moduleMigrations->pluck('name'))
            ->orderByDesc('batch')
            ->get(['migration', 'batch']);


        if ($records->isEmpty()) {
            $this->info("No matching migrations found in the database.");
            return;
        }


        $maxBatch = $records->max('batch');
        $batchThreshold = $maxBatch - $step;


        foreach ($moduleMigrations as $migration) {
            $this->runAndCalculate(function () use ($migration) {
                $class = $this->resolve($migration['path']);
                $class->down();
            }, $migration['name']);

        }

        $this->info("updating migrations database.");

        $this->runAndCalculate(function () use ($moduleMigrations, $batchThreshold) {
            DB::table('migrations')
                ->whereIn('migration', $moduleMigrations->pluck('name'))
                ->where('batch', '>', $batchThreshold)
                ->delete();
        }, 'updating migration table');


        $this->info("Rollback completed for modules: " . implode(', ', $modules) . " ($step steps)");
    }


    public function migrationFiles($module): array
    {
        $migrationsPath = module_path($module, 'Database/Migrations');
        $migration_list = File::allFiles($migrationsPath);
        $migrations = [];
        foreach ($migration_list as $key => $migrateFile) {
            $absPath = $migrateFile->getPathname();
            $fileName = $migrateFile->getFilename();
            $migrationName = str_replace('.php', '', $fileName);
            $migrations[$key]['path'] = $absPath;
            $migrations[$key]['file'] = $fileName;
            $migrations[$key]['name'] = $migrationName;
        }
        return $migrations;
    }

    public function removeFromMigrationTable(string $migrateFileName): void
    {
        DB::table('migrations')->where('migration', $migrateFileName)->delete();

    }

    public function addToMigrationTable(string $migrateFileName, $batch = 1): void
    {

        DB::table('migrations')->insert([
            'migration' => $migrateFileName,
            'batch' => $batch,
        ]);

    }

    /**
     * @return array
     */
    public function migrationTableRecord()
    {
        return DB::table('migrations')->orderBy('batch', 'desc')->pluck('migration')->toArray();
    }

    /**
     * @return void
     */
    public function installMigrateTable(): void
    {
        Schema::hasTable('migrations') ?: $this->call('migrate:install');
    }


    /**
     * @return void
     */
    public function seeding(): void
    {
        $modules = $this->getModules();
        foreach ($modules as $module) {
            $seedersPath = module_path($module, "Database/Seeders");
            if (File::exists($seedersPath)) {
                Artisan::call('module:seed', [
                    '--module' => "$module"
                ], $this->output);
            }
        }
    }

}
