<?php

namespace Teksite\Module\Console\Module\traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Input\InputArgument;
use Teksite\Module\Facade\Module;

trait ModuleGeneratorCommandTrait
{
    private function getModulePath(string $moduleName): string
    {
        return Module::modulePath($moduleName);
    }

    protected function isModuleDirectoryExists(string $modulePath): bool
    {
        if (File::exists($modulePath)) return true;
        if (in_array($modulePath, Module::all())) return true;
        return false;
    }

    protected function isModuleRegistered(string $module): bool
    {
        return Module::isRegistered($module);

    }

    protected function notAllowedModuleName(): array
    {
        return [
            'steward',
            'Steward',
        ];
    }

    protected function isAllowedName(string $moduleName): bool
    {
        return !in_array($moduleName, $this->notAllowedModuleName());
    }


    private function registerModule(string $moduleName , string $type , bool $active= true): void
    {
        $bootstrapFile = module_bootstrap_path();
        $registeredModule = get_module_bootstrap();

        $namespace = Module::moduleNamespace($moduleName);

        $providerClass = "{$namespace}\\App\\Providers\\{$moduleName}ServiceProvider";

        if (!array_key_exists($moduleName, $registeredModule)) {
            $registeredModule[$moduleName]['provider'] = $providerClass;
            $registeredModule[$moduleName]['active'] = $active;
            $registeredModule[$moduleName]['type'] = $type;

            File::put(
                $bootstrapFile,
                '<?php return ' . humanReadableVarExport($registeredModule, true) . ';'
            );
            $this->line(" └─ updating bootstrap file", );
            $this->components->twoColumnDetail("<fg=gray>  └─ module <fg=cyan;options=bold>$moduleName</> is added to bootstrap/modules.php</>", '<fg=green;options=bold>✓ DONE</>');
        } else {
            $this->error("Module $moduleName is already in bootstrap/modules.php");
        }
    }



    protected function replaceStub(string $stub, array $replace, string $destination): void
    {
        $stubPath = $this->getStubFile($stub);
        $replacedContent = $this->getStubContent($stubPath, $replace);

        if (!File::exists(dirname($destination))) {
            File::makeDirectory(dirname($destination), 0755, true);
        }

        // Write to the file
        try {
            File::put($destination, $replacedContent);
        } catch (\Exception $e) {
            $this->error("Error writing to file: " . $e->getMessage());
        }

    }

    protected function getStubFile($path): string
    {
        return app('make-module.stubs') . trim($path, '\/');
    }

    protected function getStubContent(string $stubPath, array $replacements = []): string
    {
        if (!File::exists($stubPath)) {
            $this->error("$stubPath is not exists!");
            return '';
        }

        $content = File::get($stubPath);

        if (count($replacements)) {
            foreach ($replacements as $key => $value) {
                $content = str_replace($key, $value, $content);
            }
        }
        return $content;
    }

    protected function dumpingComposer(): void
    {

        $this->line("wait to dump autoload of composer, it may take a while ...");

        Process::path(base_path())
               ->command('composer dump-autoload')
               ->run()->output();

        $this->newLine();

    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the ' . strtolower($this->type)],
        ];
    }

}
