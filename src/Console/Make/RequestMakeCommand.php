<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class RequestMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new form request class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Request';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if (!$this->hasApiFormRequest()) return $this->resolveStubPath('stubs/request.stub');

        return $this->option('api')
            ? $this->resolveStubPath('stubs/request.api.stub')
            : $this->resolveStubPath('stubs/request.stub');
    }

    protected function path(): string
    {
        return 'app/Http/Requests';
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
        $options = [
            ['force', 'f', InputOption::VALUE_NONE, "Create the class or file even if the {$this->type} already exists",],
        ];

        if ($this->hasApiFormRequest()) $options[] = ['api', null, InputOption::VALUE_NONE, 'Generate an API form request class',];

        return $options;
    }

    /**
     * Determine whether the API form request class is available.
     */
    protected function hasApiFormRequest(): bool
    {
        return class_exists('Teksite\Extralaravel\Http\ApiFormRequest');
    }
}
