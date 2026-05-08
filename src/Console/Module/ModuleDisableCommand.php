<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;
use Teksite\Module\Facade\Module;

class ModuleDisableCommand extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $name = 'module:disable';

    protected $description = 'disable the module';

    protected string $type = 'Module';

    public function handle(): void
    {
        $moduleName = Str::studly($this->argument('name'));

        $modulePath = $this->getModulePath($moduleName);

        $this->newLine();

        if (!$this->validating($moduleName, $modulePath)) return;

        $this->disableModule($moduleName);

        $this->newLine();

        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module $moduleName is disabled successfully.");
        $this->newLine();
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

    private function disableModule(string $moduleName): void
    {
        $this->warn("diabling $moduleName");

        try {
            Module::disable($moduleName);
            $this->components->twoColumnDetail("<fg=gray>  └─ updating bootstrap file</>", "<fg=green>✓ DONE</>");

            return;
        }catch (\Exception $exception){
            Log::error($exception);
            $this->components->twoColumnDetail("<fg=gray>  └─ updating bootstrap file</>", "<fg=red>✗  failed</>");
            return;
        }
    }
}
