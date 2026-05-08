<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;
use Teksite\Module\Facade\Module;

class DeleteMakeCommand extends Command
{

    use ModuleGeneratorCommandTrait;

    protected $name = 'module:delete';

    protected $description = 'delete a module';

    protected string $type = 'Module';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modulesName = $this->argument('name');

        $bootstrapFile = module_bootstrap_path();

        if (!$this->validating($modulesName, $bootstrapFile)) return;

        $this->deleteModules($modulesName, $bootstrapFile);

    }

    /**
     * Ask for confirmation before deleting.
     */
    private function validating(array $modulesName, $bootstrapFile): bool
    {
        if (!File::exists($bootstrapFile)) {
            $this->error("The file bootstrap/modules.php does not exist!");
            return false;
        }
        foreach ($modulesName as $module) {
            if (!$this->isAllowedName($module)) {
                $this->error("$module is not allowed");
                return false;
            }
        }
        return true;

    }

    private function deleteConfirmation($moduleName): bool
    {
        if ($this->option('all')) return true;
        return $this->confirm("Are you sure you want to delete the module ($moduleName)? [y|n]?", "n");
    }

    /**
     * Handle the deletion process for the given modules.
     */
    private function deleteModules(array $modulesName, string $bootstrapFile): void
    {
        $modulesName = array_map(fn($module) => Str::studly(trim($module)), $modulesName);

        foreach ($modulesName as $module) {
            if (!$this->deleteConfirmation($module)) continue;

            $this->newLine();
            $this->line("<fg=yellow> Deleting module $module</>");

            $this->deletingDirectory($module);
            $this->unregister($module, $bootstrapFile);
        }
        $this->newLine();
        $this->dumpingComposer();

    }

    /**
     * Remove the module directory and update configuration.
     */
    private function removeDirectory(string $path): void
    {
        File::deleteDirectory($path);
    }

    /**
     * Remove the module from config/modules.php if it exists.
     */
    private function updateModuleBootstrap(string $moduleName, string $bootstrapFile): bool
    {
        $modules = get_all_modules();

        unset($modules[$moduleName]);

        File::put($bootstrapFile, '<?php return ' . humanReadableVarExport($modules, true) . ';');
        return true;
    }


    /**
     * @param mixed $module
     * @return void
     */
    public function deletingDirectory(mixed $module): void
    {
        $pathExist = is_dir(Module::modulePath($module)) ? Module::modulePath($module) : false;
        if (!!$pathExist) {
            $this->removeDirectory($pathExist);
            $this->components->twoColumnDetail("<fg=gray> └─ deleting $module directory</>", "<fg=green>✓ DONE</>");

        } else {
            $this->components->twoColumnDetail("<fg=gray> └─ deleting $module directory", "<fg=red>✘ directory not found!</>");
        }
    }

    /**
     * @param mixed $module
     * @param string $bootstrapFile
     * @return void
     */
    public function unregister(string $module, string $bootstrapFile): void
    {
        $isRegistered = (in_array($module, get_all_modules(true)));
        if ($isRegistered) {
            $this->updateModuleBootstrap($module, $bootstrapFile);
            $this->components->twoColumnDetail("<fg=gray> └─ unregistering $module</>", "<fg=green>✓ DONE</>");

        } else {
            $this->components->twoColumnDetail("<fg=gray> └─ unregistering $module</>", "<fg=red>✘ not found in bootstrap/modules.php!</>");
        }

    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::IS_ARRAY, 'module(s) to be deleted'],
        ];
    }

    protected function getOptions(): array
    {
        //TODO rollback in deleting module(s)
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'delete all modules without confirmation'],
            ['rollback', 'r', InputOption::VALUE_NONE, 'rollback module(s)'],
        ];
    }


}
