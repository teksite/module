<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\OutputInterface;
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
        $module = $this->argument('module');
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
     * @return void
     */
    public function up(): void
    {
        $batch = DB::table('migrations')->max('batch') + 1;
        $this->upModules($batch);

    }

    public function rollback(int $step = 1): void
    {
        $migrationTables = collect($this->moduleMigrationFiles())->select(['path', 'name', 'path']);
        $this->rollingBackMigration($migrationTables, $step);
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
       $this->seedingModules();
    }

}
