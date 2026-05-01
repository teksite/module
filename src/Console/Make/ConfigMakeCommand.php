<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class ConfigMakeCommand extends GeneratorModuleCommand
{
    protected string $generatorType = 'file';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new configuration file in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Config';

    /**
     * The console command name aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['config:make'];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/config.stub');
    }

    protected function path(): string
    {
       return  'config';
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
