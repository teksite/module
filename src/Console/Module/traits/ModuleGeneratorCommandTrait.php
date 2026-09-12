<?php

namespace Teksite\Module\Console\Module\traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Input\InputArgument;
use Teksite\Module\Facade\Module;

trait ModuleGeneratorCommandTrait
{
    private function getModulePath(string $moduleName,): string
    {
        return Module::modulePath($moduleName);
    }

    private function getStewardPath(): string
    {
        return Module::stewardPath();
    }

    protected function isModuleDirectoryExists(string $modulePath,): bool
    {
        return File::exists($modulePath);
    }

    protected function isModuleRegistered(string $module,): bool
    {
        return Module::isRegistered($module);
    }

    protected function notAllowedModuleName(): array
    {
        return [
            'steward',
            'Steward',
            'lareon',
            'Lareon',
        ];
    }

    protected function isAllowedName(string $moduleName,): bool
    {
        return !in_array($moduleName, $this->notAllowedModuleName());
    }

    protected function hasForbiddenCharacters($moduleName,): bool
    {
        return preg_match('/^[a-zA-Z]+$/', $moduleName) === 1;
    }

    protected function validateModuleState(string $moduleName, string $modulePath, bool $shouldAlreadyExist, bool $shouldAlreadyBeRegistered,): bool
    {

        if (!$this->hasForbiddenCharacters($moduleName)) {
            $this->error("$moduleName contains invalid characters, use a-z A-Z");
            return false;
        }

        if (!$this->isAllowedName($moduleName)) {
            $this->error("$moduleName is not allowed");
            return false;
        }

        $directoryExists = $this->isModuleDirectoryExists($modulePath);
        if ($shouldAlreadyExist && !$directoryExists) {
            $this->error("directory of the module ($moduleName) does not exist");
            return false;
        }
        if (!$shouldAlreadyExist && $directoryExists) {
            $this->error("a directory with the same name ($moduleName) already exists.");
            return false;
        }

        $isRegistered = $this->isModuleRegistered($moduleName);
        if ($shouldAlreadyBeRegistered && !$isRegistered) {
            $this->error("the module ($moduleName) is not registered. run module:scan first to be registered in bootstrap/modules file");
            return false;
        }
        if (!$shouldAlreadyBeRegistered && $isRegistered) {
            $this->error("a module with the same name ($moduleName) already exists in bootstrap module file.");
            return false;
        }

        return true;
    }

    private function registerModule(string $moduleName, string $type, bool $active = true,): void
    {
        $bootstrapFile = module_bootstrap_path();
        $registeredModule = get_modules();

        $namespace = Module::moduleNamespace($moduleName);

        $providerClass = "{$namespace}\\App\\Providers\\{$moduleName}ServiceProvider";

        if (!array_key_exists($moduleName, $registeredModule)) {
            $registeredModule[$moduleName]['provider'] = $providerClass;
            $registeredModule[$moduleName]['active'] = $active;
            $registeredModule[$moduleName]['type'] = $type;

            File::put(
                $bootstrapFile,
                '<?php return '.humanReadableVarExport($registeredModule, true).';',
            );

            reset_modules_cache();

            $this->line(" └─ updating bootstrap file");
            $this->components->twoColumnDetail("<fg=gray>  └─ module <fg=cyan;options=bold>$moduleName</> is added to bootstrap/modules.php</>", '<fg=green;options=bold>✓ DONE</>');
        } else {
            $this->error("Module $moduleName is already in bootstrap/modules.php");
        }
    }

    protected function replaceStub(string $stub, array $replace, string $destination,): void
    {
        $stubPath = $this->getStubFile($stub);
        $replacedContent = $this->getStubContent($stubPath, $replace);

        if (!File::exists(dirname($destination))) File::makeDirectory(dirname($destination), 0755, true);

        // Write to the file
        try {
            File::put($destination, $replacedContent);
        } catch (\Exception $e) {
            $this->error("Error writing to file: ".$e->getMessage());
        }

    }

    protected function getStubFile($path,): string
    {
        return app('make-module.stubs').trim($path, '\/');
    }

    protected function getStubContent(string $stubPath, array $replacements = [],): string
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

        $this->warn("wait to dump autoload of composer, it may take a while ...");

        Process::path(base_path())->command('composer dump-autoload')->run()->output();

        $this->newLine();

    }

    private function isSteward(): bool
    {
        return $this->hasOption('steward') && $this->option('steward');
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type)],
        ];
    }

    protected function scaffoldDirectories(): array
    {
        return [
            '',
            'app',
            'app/Http',
            'app/Http/Controllers',
            'app/Models',
            'app/Providers',
            'config',
            'database',
            'database/factories',
            'database/migrations',
            'database/seeders',
            'lang',
            'resources/views',
            'resources/js',
            'resources/css',
            'routes',
            'tests',
            'tests/Feature',
            'tests/Unit',
        ];
    }

    protected function createScaffoldDirectories(string $path, string $label): void
    {
        $this->line(" └─ making directories");

        foreach ($this->scaffoldDirectories() as $directory) {
            File::makeDirectory("{$path}/{$directory}", 0755, true);
            $this->components->twoColumnDetail("<fg=gray>  └─ {$label}/{$directory}</>", "<fg=green>✓ DONE</>");
        }
    }

    protected function generateScaffoldFile(string $stub, array $replacements, string $destination): void
    {
        $this->replaceStub($stub, $replacements, $destination);
        $relativePath = normalizeSlashPath(str_replace(base_path(), '', $destination));
        $this->components->twoColumnDetail("<fg=gray>  └─ $relativePath</>", "<fg=green>✓ DONE</>");
    }


}
