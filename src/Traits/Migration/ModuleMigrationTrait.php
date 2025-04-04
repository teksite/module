<?php

namespace Teksite\Module\Traits\Migration;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Teksite\Module\Facade\Module;

trait ModuleMigrationTrait
{
    /**
     * @return void
     */
    public function downModules(): void
    {
        $this->warn("Dropping all tables of module(s)");
        foreach ($this->getModules() as $mdl) {
            $this->runAndCalculate(function () use ($mdl) {
                foreach (array_reverse($this->moduleMigrationFiles($mdl)) as $migration) {
                    $class = $this->resolve($migration['path']);
                    $class->down();
                    $this->removeFromMigrationTable($migration['name']);
                }
            }, "$mdl tables");
        }
    }

    /**
     * @param int|null $batch
     * @return void
     */
    public function upModules(?int $batch = null): void
    {
        $batch = $batch ?? DB::table('migrations')->max('batch') + 1;

        $migrationsRecords = $this->migrationTableRecord();

        foreach ($this->getModules() as $mdl) {
            $this->warn("migrating all tables of module: " . $mdl);
            $migrationModuleFiles = $this->moduleMigrationFiles($mdl);
            if (count($migrationModuleFiles)) {
                foreach ($migrationModuleFiles as $migration) {
                    if (!in_array($migration['name'], $migrationsRecords)) {
                        $this->runAndCalculate(function () use ($batch, $migration) {
                            $class = $this->resolve($migration['path']);
                            $class->up();
                            $this->addToMigrationTable($migration['name'], $batch);
                        }, $migration['name']);
                    }
                }
            } else {
                $this->components->info('Nothing to migrate.');

            }
        }

    }

    /**
     * @param string|null $moduleName
     * @return array
     */
    public function moduleMigrationFiles(?string $moduleName = null): array
    {
        $modules = is_string($moduleName) ? [$moduleName] : $this->getModules();

            $migrationsPath = module_path($moduleName, 'Database/Migrations');
            $migration_list = File::allFiles($migrationsPath);
            foreach ($migration_list as $key=>$migrateFile) {
                $absPath = $migrateFile->getPathname();
                $fileName = $migrateFile->getFilename();
                $migrationName = str_replace('.php', '', $fileName);
                $migrations[$key]['path'] = $absPath;
                $migrations[$key]['file'] = $fileName;
                $migrations[$key]['name'] = $migrationName;
            }

        return $migrations;
    }

    /**
     * @param Collection $migrationTables
     * @param int $step
     * @return void
     */
    public function rollingBackMigration(Collection $migrationTables, int $step = 1): void
    {
        if (empty($migrationTables->pluck('name'))) {
            $this->info("No migrations found for rollback.");
            return;
        }

        $records = DB::table('migrations')
            ->whereIn('migration', $migrationTables->pluck('name'))
            ->orderBy('batch', 'desc')->get();
        if ($records->isEmpty()) {
            $this->info("No matching migrations found in the database.");
            return;
        }

        $batchThreshold = $records->max('batch') - $step;


        $selectingRecord = $records->where('batch', '>', $batchThreshold);

        foreach ($migrationTables as $migration) {
            if (in_array($migration['name'], $selectingRecord->pluck('migration')->toArray())) {
                $this->runAndCalculate(function () use ($migration) {
                    $class = $this->resolve($migration['path']);
                    $class->down();
                }, $migration['name']);
            }

        }

        $this->info("updating migrations database.");

        $this->runAndCalculate(function () use ($selectingRecord) {
            DB::table('migrations')
                ->whereIn('migration', $selectingRecord->pluck('migration')->toArray())->delete();
        }, 'updating migration table');

        $this->info("Rollback completed for lareon: ($step steps)");
    }

    /**
     * @return void
     */
    public function seedingModules(): void
    {
        $this->warn('seeding module(s)');

        $modules = $this->getModules();
        foreach ($modules as $module) {
            $mainSeeder = module_namespace($module, "Database\\Seeders\\" . $module . "DatabaseSeeder");
            if (class_exists($mainSeeder)) {
                $this->runAndCalculate(function () use ($mainSeeder) {
                    $this->call($mainSeeder);
                }, "seeding $module");
            }
        }
    }

    /**
     * @return array
     */
    public function migrationTableRecord(): array
    {
        return DB::table('migrations')->orderBy('batch', 'desc')->pluck('migration')->toArray();
    }


    /**
     * @param \Closure $closer
     * @param string $first
     * @param string $second
     * @return void
     */
    public function runAndCalculate(\Closure $closer, string $first = '', string $second = ''): void
    {
        $startTime = Carbon::now();

        $closer();

        $endTime = Carbon::now();

        $executionTime = $startTime->diffInMilliseconds($endTime);

        $this->components->twoColumnDetail($first, "$executionTime <fg=green;options=bold>DONE</>");

    }
}
