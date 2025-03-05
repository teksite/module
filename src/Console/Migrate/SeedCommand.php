<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleNameValidator;

class SeedCommand extends Command
{
    use ModuleNameValidator;

    protected $signature = 'module:seed
        {--module= : The module to seed.}
    ';

    protected $description = 'Seed the module';

    protected $type = 'Seed';

    public function handle()
    {
        $module = $this->option('module');
        if ($module) {
            [$isValid, $suggestedName] = $this->validateModuleName($module);
            if ($isValid) {
                $this->seedModule($module);
                return;
            }

            if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
                $this->input->setOption('module', $suggestedName);
                $this->seedModule($suggestedName);
                return;
            }
            $this->error("The module '" . $module . "' does not exist.");
            return 1;
        }
        $this->seedAllModules();

    }


    protected function seedModule(string $module)
    {
        $this->callSeeders($module);
    }

    protected function seedAllModules()
    {
        foreach (Module::all() as $module) {
            $this->callSeeders($module);
        }
    }

    protected function callSeeders($module)
    {
        $mainSeeder = Module::moduleNamespace($module , "Database\\Seeders\\{$module}DatabaseSeeder");
            if (class_exists($mainSeeder)) {
                $startTime = Carbon::now();
                $this->call($mainSeeder);
                $endTime = Carbon::now();
                $executionTime = $startTime->diffInMilliseconds($endTime);

                $this->components->twoColumnDetail("seeding: <fg=white;options=bold>$mainSeeder</>" ,"<fg=gray;options=bold>$executionTime</> <fg=green;options=bold>DONE</>" );
                $this->newLine();
            }
    }

}
