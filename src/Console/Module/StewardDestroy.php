<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;
use Teksite\Module\Facade\Module;

class StewardDestroy extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $name = 'module:destroy:steward';

    protected $description = 'remove STEWARD module.';

    protected string $type = 'Module';

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $stewardName = 'Steward';

        $stewardPath = $this->getStewardPath();

        if (!File::exists($stewardPath)) {
            $this->error("the Steward directory does not exist at {$stewardPath}.");
            return;
        }

        $this->newLine();
        $this->line("removing <fg=cyan;options=bold>$stewardName</>...");

        $res = $this->removeDirectories($stewardPath);
        $this->newLine();

        if (!$res) {
            $this->info("<failed>FAILED</failed> something went wrong in deleting $stewardName.");
            return;
        }

        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> $stewardName deleted successfully.");
    }


    private function removeDirectories(string $path): bool
    {
        return File::deleteDirectory($path ,false);
    }


    protected function getArguments(): array
    {
        return [
        ];
    }


}
