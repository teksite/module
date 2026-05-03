<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Contract\TestGenerator;

class TestMakeCommand extends GeneratorModuleCommand implements TestGenerator
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Test';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        $suffix = $this->option('unit') ? '.unit.stub' : '.stub';

        return $this->usingPest()
            ? $this->resolveStubPath('stubs/pest' . $suffix)
            : $this->resolveStubPath('stubs/test' . $suffix);
    }

    protected function path(): string
    {
        if ($this->option('unit')) {
            return 'tests\Unit';
        } else {
            return 'tests\Feature';
        }
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the test even if the test already exists'],
            ['unit', 'u', InputOption::VALUE_NONE, 'Create a unit test'],
            ['pest', null, InputOption::VALUE_NONE, 'Create a Pest test'],
            ['phpunit', null, InputOption::VALUE_NONE, 'Create a PHPUnit test'],
        ];
    }


    protected function usingPest(): bool
    {
        if ($this->option('phpunit')) return false;


        return $this->option('pest') ||
            (
                function_exists('\Pest\\version') &&
                (
                    file_exists(base_path('tests') . '/Pest.php')
                    ||
                    file_exists(module_path($this->getModuleInput(), 'tests') . '/Pest.php')

                )

            );
    }
}
