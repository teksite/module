<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Teksite\Module\Facade\Lareon;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;
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
        foreach (Lareon::all() as $module) {
            $this->callSeeders($module);
        }
    }

    protected function callSeeders($module)
    {
        $mainSeeder = Lareon::moduleNamespace($module , "\\Database\\Seeders\\{$module}DatabaseSeeder");
            if (class_exists($mainSeeder)) {
                $this->line("Seeding: {$mainSeeder}");
                $startTime = Carbon::now(); // زمان شروع
                $this->call($mainSeeder);
                $endTime = Carbon::now(); // زمان پایان
                $executionTime = $startTime->diffInMilliseconds($endTime); // محاسبه زمان اجرا
                $this->line(sprintf('%s ................................................................................................ %dms DONE', $mainSeeder, $executionTime));
                $this->newLine();
            }
    }

}
