<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ResetCommands extends Command
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:migrate-reset {module?}
        ';

    protected $description = 'Rollback migrations for a specific module or all modules';

    public function handle()
    {
        $modules = $this->getModules();
         foreach ($modules as $module) {
             $correctedModule = $this->checkModule($module);
             if (!$correctedModule){
                 $this->error("The module '" . $module . "' does not exist.");
                 return 0;
             }
             $this->runTheCommand($correctedModule);

         }
    }

    protected function checkModule($module) : false|string
    {

        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) {
            return $module;
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            return $suggestedName;
        }
        return false;
    }

    protected function runTheCommand($module)
    {
        $this->info("Dropping all tables for module: " . $module);

        $migrationsPath = module_path($module, 'Database/Migrations');

        $migration_list = File::allFiles($migrationsPath);
        $allFiles = [];
        foreach ($migration_list as $migrateFile) {
            $absPath = $migrateFile->getPathname();
            $this->runAndCalculate(function () use ($absPath) {
                $class=$this->resolve($absPath);

            });
        }


    }

    public function resolve($file)
    {
        return include $file;
    }

    public function runAndCalculate(\Closure $closer, string $first = '', string $second = ''): void
    {
        $startTime = Carbon::now();
        $closer();
        $endTime = Carbon::now();

        $executionTime = $startTime->diffInMilliseconds($endTime);

        $this->components->twoColumnDetail($first, "$executionTime <fg=green;options=bold>DONE</>");

    }

    public function getModules()
    {
        $module = $this->argument('module');
        return $module ? [$module] : Module::all();
    }
}
