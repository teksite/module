<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Lareon\Modules\Service\Providers\ServiceServiceProvider;
use ReflectionClass;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class ModuleScanCommand extends Command
{

    use ModuleGeneratorCommandTrait;

    protected $name = 'module:scan';

    protected $description = 'scan and register widowed modules';

    protected string $type = 'Module';

    public function handle(): void
    {

        $widowedModules = $this->getWidowModules();

        if (empty($widowedModules)) {
            $this->line("<fg=yellow>** all modules is registered before, nothing to register</>");
            $this->newLine();
            return;
        }

        foreach ($widowedModules as $module) {

            $serviceProviderName = $module . "ServiceProvider";
            $serviceProviderNameNamespace = module_namespace($module) . '\\Providers\\' . $module . "ServiceProvider";

            $serviceProviderPath = $this->getServiceProviderPath($module, $serviceProviderName);
            if (!$serviceProviderPath) continue;

            $type = $this->getModuleType($serviceProviderPath, $serviceProviderNameNamespace);
            if (!$type) continue;

            $this->registerModule($module, $type);

            $this->line("<fg=yellow>$module</>is registered");

        }
    }

    private function getServiceProviderPath(string $moduleName, string $serviceProviderFileName): false|string
    {
        $servicePath = module_path($moduleName, "app/Providers/$serviceProviderFileName.php");
        if (!File::exists($servicePath)) {
            $this->error("$moduleName can't be registered: $serviceProviderFileName doesn't exist");
            return false;

        }
        return $servicePath;
    }

    private function getModuleType(string $serviceProviderPath, string $serviceProviderNameNamespace): false|string
    {
        require_once $serviceProviderPath;

        if (!class_exists($serviceProviderNameNamespace)) {
            $this->error("$serviceProviderNameNamespace doesn't exist in $serviceProviderPath file");
            return false;
        }
        $refClass = new ReflectionClass($serviceProviderNameNamespace);
        $props= $refClass->getProperties();
dd($props);
    }

    private function getWidowModules(): array
    {
        $modulesBasePath = module_path() ?? [];
        $directories = File::directories($modulesBasePath);
        $registeredModules = get_modules_name();


        $modulesNeedToBeRegistered = [];
        foreach ($directories as $dir) {
            $explodePath = explode('\\', $dir);
            $moduleName = array_last($explodePath);
            if (in_array($moduleName, $registeredModules)) continue;
            $modulesNeedToBeRegistered[] = $moduleName;
        }
        return $modulesNeedToBeRegistered;

    }


    protected function getArguments(): array
    {
        return [
        ];
    }


}
