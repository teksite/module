<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class ModuleScanCommand extends Command
{

    use ModuleGeneratorCommandTrait;

    protected $name = 'module:scan';

    protected $description = 'scan and register widowed modules';

    protected string $type = 'Module';

    public function handle(): void
    {
      #TODO create module:scan
    }


    private function validating(string $moduleName, $modulePath): bool
    {

    }



    protected function getOptions(): array
    {
        return [
            ['steward', 's', InputOption::VALUE_NONE, 'to be managed by steward'],
        ];
    }
}
