<?php

namespace Teksite\Module\Console\Installer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Facade\Module;
use Teksite\Module\Facade\ModuleManager;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class InstallerCommand extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $signature = 'module:install-manager';

    protected $description = 'install Module Manager';

    protected $type = 'Installer';

    public function handle()
    {

            $this->dumpngComposer();

            $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
            $this->newLine();

            $this->info("<success>SUCCESS</success> Module manager is installed successfully.");

    }


}
