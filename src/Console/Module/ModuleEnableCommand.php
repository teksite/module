<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class ModuleEnableCommand extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $name = 'module:enable';

    protected $description = 'enable the module';

    protected string $type = 'Module';

    public function handle(): void
    {
        $moduleName = Str::studly($this->argument('name'));
        $modulePath = $this->getModulePath($moduleName);

        if (!$this->validating($moduleName, $modulePath)) return;

        $this->enableModule($moduleName);
        $this->newLine();

        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module $moduleName is enabled successfully.");
    }

    private function validating(string $moduleName, $modulePath): bool
    {
        if (!$this->isAllowedName($moduleName)) {
            $this->error("$moduleName is not allowed");
            return false;
        }

        if (!$this->isModuleDirectoryExists($modulePath)) {
            $this->error("directory of the module ($moduleName) does not exist");
            return false;
        }

        if (!$this->isModuleRegistered($moduleName)) {
            $this->error("the module ($moduleName) is not registered. run module:scan first to be registered in bootstrap/modules file");
            return false;
        }
        return true;
    }


    private function getModulePath(string $moduleName): string
    {
        return Module::modulePath($moduleName);
    }

    private function enableModule(string $moduleName): void
    {
        try {
            Module::enable($moduleName);
            $this->info("modules bootstrap file is updated.");

            return;
        }catch (\Exception $exception){
            Log::error($exception);
            $this->error("$moduleName is not enabled.");
            return;
        }
    }


}
