<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\CreatesModuleMatchingTest;

class ClassMakeCommand extends GeneratorModuleCommand
{
    use CreatesModuleMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-class';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Console command';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->option('invokable')
            ? $this->resolveStubPath('stubs/class.invokable.stub')
            : $this->resolveStubPath('stubs/class.stub');
    }

    protected function path(): string
    {
        return 'app';
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
    protected function getOptions()
    {
        return [
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate a single method, invokable class'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the class already exists'],
        ];
    }


}
