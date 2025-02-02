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
        if ($this->option('yes') || $this->confirmDeletion() =='yes') {
            $this->deleteModules();
        }
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
                $this->removeModule($moduleName, $modulePath);
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
    private function removeModule(string $moduleName, string $modulePath): void
    {
        File::deleteDirectory($modulePath);
        $this->line("{$moduleName} module directory deleted successfully.");
        $this->updateModuleConfig($moduleName);
    }

    /**
     * Remove the module from config/modules.php if it exists.
     */
    private function updateModuleConfig(string $moduleName): void
    {
        $configPath = config_path('modules.php');

        if (!File::exists($configPath)) {
            $this->error("Config file config/modules.php does not exist!");
            return;
        }

        $modules = require $configPath;
        if (!isset($modules['modules'][$moduleName])) {
            $this->warn("Module {$moduleName} was not found in config/modules.php.");
            return;
        }

        unset($modules['modules'][$moduleName]);
        File::put($configPath, '<?php return ' . var_export_short($modules, true) . ';');
        $this->line("Module {$moduleName} removed from config/modules.php.");
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
            $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('blue', null, ['bold']));
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
