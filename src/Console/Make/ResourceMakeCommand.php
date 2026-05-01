<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class ResourceMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Resource';


    public function handle(): void
    {
        if ($this->collection()) {
            $this->type = 'Resource collection';
        }

        parent::handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return match (true) {
            $this->collection() => $this->resolveStubPath('stubs/resource-collection.stub'),
            $this->option('json-api') => $this->resolveStubPath('stubs/resource-json-api.stub'),
            default => $this->resolveStubPath('stubs/resource.stub'),
        };
    }

    /**
     * Determine if the command is generating a resource collection.
     *
     * @return bool
     */
    protected function collection(): bool
    {
        return $this->option('collection') ||
            str_ends_with($this->argument('name'), 'Collection');
    }

    protected function path(): string
    {
        return  'app/Http/Resources';
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the resource already exists'],
            ['json-api', 'j', InputOption::VALUE_NONE, 'Create a JSON:API resource'],
            ['collection', 'c', InputOption::VALUE_NONE, 'Create a resource collection'],
        ];
    }


}
