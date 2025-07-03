<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class ModuleEnableCommand extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $signature = 'module:enable {name}';

    protected $description = 'enable the module';

    protected string $type = 'Module';

    public function handle()
    {
        $moduleName = Str::studly($this->argument('name'));

        $modulePath = $this->getModulePath($moduleName);
        if (!$this->moduleExists($modulePath)) {
            $this->error("the directory of the module ($moduleName) does not exists.");
            return;
        }

        $this->registerModule($moduleName);

        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module $moduleName created successfully.");
    }

    private function getModulePath(string $moduleName): string
    {
        return Module::modulePath($moduleName);
    }

    private function moduleExists(string $modulePath): bool
    {
        if (File::exists($modulePath)) return true;
        if (in_array($modulePath, Module::all())) return true;
        return false;
    }

    private function registerModule(string $moduleName): void
    {
        $bootstrapFile = module_bootstrap_path();
        $registeredModules = get_module_bootstrap();

        if (array_key_exists($moduleName, $registeredModules)) {
            $registeredModules[$moduleName]['active'] = true;
            File::put(
                $bootstrapFile,
                '<?php return ' . var_export_short($registeredModules, true) . ';'
            );
            $this->newLine();
            $this->components->twoColumnDetail("enabling: module <fg=cyan;options=bold>$moduleName</> is enabled" ,'<fg=green;options=bold>DONE</>' );
        } else {
            $this->newLine();
            $this->error("Module $moduleName is not registered in bootstrap/modules.php");
        }
    }

    private function dumpingComposer(): void
    {
        $this->info("wait to dump autoload of composer, it may take a while ...");

        Process::path(base_path())
            ->command('composer dump-autoload')
            ->run()->output();
    }
}
