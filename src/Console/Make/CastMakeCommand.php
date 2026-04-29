<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleGeneratorTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class CastMakeCommand extends GeneratorModuleCommand
{
    use ModuleNameValidator, ModuleGeneratorTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-cast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new custom Eloquent cast class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Cast';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->option('inbound')
            ? $this->resolveStubPath('cast.inbound.stub')
            : $this->resolveStubPath('cast.stub');
    }

    protected function path(): string
    {
       return  'app/Casts';
    }

    /**
     * set the path of the file.
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the cast already exists'],
            ['inbound', null, InputOption::VALUE_NONE, 'Generate an inbound cast class'],
        ];
    }


}
