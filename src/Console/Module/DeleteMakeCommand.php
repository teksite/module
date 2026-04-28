<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

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
            $isRegistered = (in_array($module, Module::all()));
            $pathExist = is_dir(Module::modulePath($module)) ? Module::modulePath($module) : false;
            if (!!$pathExist) {
                $this->removeDirectory($pathExist);
                $dirMSg = "<fg=green;options=bold>✔ deleted successfully!</>";

            } else {
                $dirMSg = "<fg=red;options=bold>✘ directory not found!</>";
            }

            if (!!$isRegistered) {
                $this->updateModuleBootstrap($module, $bootstrapFile);
                $regMSg = "<fg=green;options=bold>✔ unregistered successfully!</>";

            } else {
                $regMSg = "<fg=red;options=bold>✘ not found in bootstrap/modules.php!</>";
            }

            $this->components->twoColumnDetail("$module| $regMSg", "<fg=green;options=bold>$dirMSg</>");

        }

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
    private function updateModuleBootstrap(string $moduleName, string $bootstrapFile): void
    {
        $modules = get_module_bootstrap();

        if (!in_array($moduleName, array_keys($modules))) return;


        unset($modules[$moduleName]);
        File::put($bootstrapFile, '<?php return ' . humanReadableVarExport($modules, true) . ';');

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
