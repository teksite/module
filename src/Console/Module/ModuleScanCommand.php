<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;
use Teksite\Module\Facade\Module;

class ModuleScanCommand extends Command
{

    use ModuleGeneratorCommandTrait;

    protected $name = 'module:scan';

    protected $description = 'scan and register widowed modules';

    protected string $type = 'Module';

    public function handle(): void
    {
        $this->newLine();
        $widowedModules = $this->getWidowModules();

        if (empty($widowedModules)) {
            $this->line("<fg=yellow>** all modules has been registered already, nothing to scan **</>");
            $this->newLine();
            return;
        }

        $widowedString = implode(',',$widowedModules);

        $this->line("** some directories has been found: $widowedString");
        $this->line("** now we are trying to validate and register them");
        $this->newLine();

        foreach ($widowedModules as $module) {
            $this->line("scan <fg=cyan;options=bold>{$module}</> ...");

            $serviceProviderNameNamespace = module_namespace($module) . '\\App\\Providers\\' . $module . "ServiceProvider";

            $serviceProviderName = $module . "ServiceProvider";

            $serviceProviderPath = $this->getServiceProviderPath($module, $serviceProviderName);

            if (!$serviceProviderPath) continue;

            $type = $this->getModuleType($serviceProviderPath, $serviceProviderNameNamespace);

            if (!$type) continue;

            $this->registerModule($module, $type, $this->option('active') === 'true');

            $this->components->twoColumnDetail("<fg=green> └─ $module is registered</>", '<fg=green;options=bold>✓ DONE</>');

        }
        $this->newLine();
        $this->dumpingComposer();
    }

    private function getServiceProviderPath(string $moduleName, string $serviceProviderFileName): false|string
    {
        $servicePath = module_path($moduleName, "app/Providers/$serviceProviderFileName.php");
        if (!File::exists($servicePath)) {
            $this->components->twoColumnDetail("$serviceProviderFileName.php", "<fg=red>✗file can not be found</>");
            return false;

        }
        return $servicePath;
    }

    private function getModuleType(string $serviceProviderPath, string $serviceProviderNameNamespace): false|string
    {
        require_once $serviceProviderPath;

        if (!class_exists($serviceProviderNameNamespace)) {
            $this->components->twoColumnDetail("$serviceProviderNameNamespace", "<fg=red>✗class is not exists</>");
            return false;
        }


        $ref = new ReflectionClass($serviceProviderNameNamespace);

        $defaultProperties = $ref->getDefaultProperties();

        $type = $defaultProperties['type'] ?? null;
        if (!in_array($type, ['steward', 'self'])) {
            $this->components->twoColumnDetail("$serviceProviderNameNamespace", "<fg=red>✗module type is not valid or not set</>");
            $this->line("<fg=gray>module should be self(manged bt itself) or steward (managed by steward)</>");
            return false;
        }
        return $type;
    }

    private function getWidowModules(): array
    {
        $modulesBasePath = module_path();

        if (!File::isDirectory($modulesBasePath)) return [];

        $directories = File::directories($modulesBasePath);
        $registeredModules = get_modules_name();

        $modulesNeedToBeRegistered = [];

        foreach ($directories as $dir) {
            $moduleName = basename($dir);
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


    protected function getOptions(): array
    {
        return [
            ['active', null, InputOption::VALUE_OPTIONAL, 'active module as scan or not (true or false | default : false)'],
        ];
    }


}
