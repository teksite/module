<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class DeleteMakeCommand extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $signature = 'module:delete {name}
        {--y|yes : Delete without confirmation}
    ';
    protected $description = 'Delete a specific module';
    protected string $type = 'Module';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('yes') || $this->confirmDeletion() == 'yes') $this->deleteModules();
    }

    /**
     * Ask for confirmation before deleting.
     */
    private function confirmDeletion(): bool
    {
        return $this->confirm('Are you sure you want to delete the module?', "no");
    }

    /**
     * Handle the deletion process for the given modules.
     */
    private function deleteModules(): void
    {
        $moduleNames = array_map(fn($name) => Str::studly(trim($name)), explode(',', $this->argument('name')));
        $existingModules = [];
        $failedModules = [];

        foreach ($moduleNames as $moduleName) {
            if ($modulePath = Module::modulePath($moduleName)) {
                $this->removeDirectory($moduleName, $modulePath);
                $this->updateModuleBootstrap($moduleName);
                $existingModules[] = $moduleName;
            } else {
                $failedModules[] = $moduleName;
            }
        }

        $this->displayResults($existingModules, $failedModules);
    }

    /**
     * Remove the module directory and update configuration.
     */
    private function removeDirectory(string $moduleName, string $modulePath): void
    {
        if (!File::isDirectory($modulePath)) {
            $this->warn("Directory {$moduleName} was not found");
            return;
        }
        File::deleteDirectory($modulePath);
        $this->components->twoColumnDetail("deleting directory: <fg=cyan;options=bold>$moduleName</>", '<fg=green;options=bold>DONE</>');
    }

    /**
     * Remove the module from config/modules.php if it exists.
\     */
    private function updateModuleBootstrap(string $moduleName): void
    {
        $bootstrapFile = module_bootstrap_path();
        if (!File::exists($bootstrapFile)) {
            $this->error("The file bootstrap/modules.php does not exist!");
            return;
        }

        $modules = get_module_bootstrap();

        if (!in_array($moduleName, array_keys($modules))) {
            $this->warn("Module {$moduleName} was not found in bootstrap/modules.php");
            return;
        }

        unset($modules[$moduleName]);
        File::put($bootstrapFile, '<?php return ' . var_export_short($modules, true) . ';');
        $this->components->twoColumnDetail("updating module bootstrap: remove <fg=cyan;options=bold>$moduleName</>", '<fg=green;options=bold>DONE</>');

    }

    /**
     * Display deletion results and trigger composer dump-autoload.
     */
    private function displayResults(array $existingModules, array $failedModules): void
    {
        if (!empty($failedModules)) {
            $this->error("The following module(s) were not found: " . implode(", ", $failedModules));
        }

        if (!empty($existingModules)) {
            $this->composerDumpAutoload();
            $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('red', null, ['bold']));
            $this->newLine();
            $this->info("<success>DELETED</success> Module(s) " . implode(", ", $existingModules) . " deleted successfully.");
        }
    }

    /**
     * Run composer dump-autoload.
     */
    private function composerDumpAutoload(): void
    {
        $this->info("Running composer dump-autoload, please wait...");
        Process::path(base_path())->command('composer dump-autoload')->run()->output();
    }
}
