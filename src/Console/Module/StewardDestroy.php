<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Console\Module\Traits\ModuleGeneratorCommandTrait;

class StewardDestroy extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $name = 'module:destroy:steward';

    protected $description = 'Remove the STEWARD module.';

    protected string $type = 'Module';

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $answer = 'n';

        if ($this->option('yes')) $answer = 'y';
        else  $this->ask('are you sure you want to delete STEWARD , it may affect on the application? (y,yes | no,n)', 'n');

        if (!in_array($answer, ['y', 'yes'])) return;

        $stewardName = 'Steward';
        $stewardPath = $this->getStewardPath();
        $dryRun = $this->option('dry-run');

        if (!File::isDirectory($stewardPath)) {
            $this->error("The {$stewardName} directory does not exist at {$stewardPath}.");
            return;
        }

        $this->newLine();

        if ($dryRun) {
            $this->line("Simulating removal of <fg=cyan;options=bold>{$stewardName}</>...");
        } else {
            $this->line("Removing <fg=cyan;options=bold>{$stewardName}</>...");
        }

        $this->showStewardModules();

        if ($dryRun) {
            $this->newLine();
            $this->info("<fg=yellow;options=bold>DRY-RUN</> No files or Composer configuration were changed.");
            return;
        }

        if (!$this->removeDirectory($stewardPath)) {
            $this->newLine();
            $this->error("FAILED: Something went wrong while deleting {$stewardName}.");
            return;
        }

        $this->dumpingComposer();
        $this->newLine();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));

        $this->info("<success>SUCCESS</success> {$stewardName} deleted successfully.");
    }

    private function removeDirectory(string $path,): bool
    {
        return File::deleteDirectory($path);
    }

    private function showStewardModules(): void
    {
        $modules = get_modules();

        $stewardModules = array_filter($modules, static fn(array $module,): bool => ($module['type'] ?? 'self') === 'steward');

        if ($stewardModules === []) return;

        $this->newLine();
        $this->line('These modules work with Steward:');


        foreach ($stewardModules as $name => $module) {
            $this->line("  - {$name}");
        }
    }

    protected function getOptions(): array
    {
        return [
            ['dry-run', null, InputOption::VALUE_NONE, 'Simulate deleting Steward without making any changes.',],
            ['yes', '-y', InputOption::VALUE_NONE, 'delete without confirmation.',],
        ];
    }

    protected function getArguments(): array
    {
        return [];
    }
}
