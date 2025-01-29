<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class DeleteMakeCommand extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $signature = 'module:delete {name}';

    protected $description = 'delete the specific module';

    protected $type = 'Module';

    public function handle()
    {
        $moduleNames = $this->argument('name');
        $modulesNamesArray = explode(",", $moduleNames);
        $existedModules = [];
        $wrongModules = [];
        foreach ($modulesNamesArray as $name) {
            $moduleName = Str::studly($name);
            $modulePath=Module::ModulePath($moduleName);
            if ($modulePath && $name===$moduleName) {
                $this->info("{$moduleName} module directory deleted successfully.");

                File::deleteDirectory($modulePath);
                $this->removeModuleFromConfig($moduleName);

                $existedModules[] = $moduleName;
            } else {
                $wrongModules[] = $name;
            }
        }
        if (count($existedModules)) {
            $deletedModules=implode(",", $existedModules);
            $this->info("{$deletedModules} module(s) deleted successfully.");

        }
        if (count($wrongModules)) {
            $notDeletedModules=implode(", ", $wrongModules);
            $this->error("{$notDeletedModules} module(s) are not deleted!. check them if they are exist");

        }
    }

    private function removeModuleFromConfig(string $moduleName): void
    {
        $configPath = config_path('modules.php');

        if (File::exists($configPath)) {
            $modules = require $configPath;

            if (isset($modules['modules'][$moduleName])) {
                unset($modules['modules'][$moduleName]);

                // به‌روزرسانی فایل config/modules.php
                File::put(
                    $configPath,
                    '<?php return ' . var_export($modules, true) . ';'
                );

                $this->info("Module {$moduleName} removed from config/modules.php.");
                $this->info("now wait to dump autoload of composer, it may take a while ...");
                exec("composer dump-autoload");
            } else {
                $this->warn("Module {$moduleName} was not found in config/modules.php.");
            }
        } else {
            $this->error("Config file config/modules.php does not exist!");
        }
    }


}
