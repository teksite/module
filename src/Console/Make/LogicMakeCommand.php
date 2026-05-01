<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\CreatesModuleMatchingTest;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class LogicMakeCommand extends GeneratorModuleCommand
{
    use CreatesModuleMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-logic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new logic/repository-pattern class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Logic';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->hasOption('crud')) return  $this->resolveStubPath('stubs/logic.crud.stub');
        return $this->resolveStubPath('stubs/logic.stub');
    }

    protected function path(): string
    {
        return match (true) {
            is_dir(module_path($this->getModuleInput(), 'Repository'))  => 'app/Repository',
            is_dir(module_path($this->getModuleInput(), 'Logic')) => 'app/Logics',
            default => 'app/Logics',
        };
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the job already exists'],
            ['crud', null, InputOption::VALUE_NONE, 'contain crud methods'],
        ];
    }


}
