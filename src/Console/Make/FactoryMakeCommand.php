<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class FactoryMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Factory';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/factory.stub');
    }

    protected function path(): string
    {
        return 'database/factories';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {

        $classFactoryName = $this->getClassName();

        $namespaceModel = $this->option('model')
            ? $this->qualifyModel($this->option('model'))
            : $this->qualifyModel($this->getNameInput() , 'Factory');

        return [
               '{{ namespacedModel }}' => $namespaceModel,
               '{{namespacedModel}}' => $namespaceModel,
               '{{ classFactoryName }}' => $classFactoryName,
               '{{classFactoryName}}' => $classFactoryName,

        ];

    }


    /**
     * change final filename if necessary
     *
     * @param string $filename
     * @return string
     */
    protected function resolveFilename(string $filename): string
    {
        return (!Str::endsWith($filename, 'Factory'))
            ? $filename . 'Factory'
            : $filename;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
