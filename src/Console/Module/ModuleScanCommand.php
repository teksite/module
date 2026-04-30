<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;

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

            $this->registerModule($module, $type , false);

            $this->info("$module is registered");

        }
        $this->dumpingComposer();
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
        $ref = new ReflectionClass($serviceProviderNameNamespace);

        $defaultProperties = $ref->getDefaultProperties();

        $type = $defaultProperties['type'] ?? false;
        if (!(bool)$type) {
            $this->error("type property doesn't set in $serviceProviderNameNamespace, set steward or self ");
            return false;
        }
        return $type;
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
