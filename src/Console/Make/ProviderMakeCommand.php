<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class ProviderMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-provider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new provider class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Provider';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/provider.stub');
    }

    protected function path(): string
    {
        return  'app/Providers';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, "Create the class or file even if the {$this->type} already exists"],
        ];
    }


}
