<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\ViewHandlerTrait;

class ViewMakeCommand extends GeneratorModuleCommand
{
    use ViewHandlerTrait;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'View';


    protected string $generatorType = 'file';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/view.stub');
    }

    protected function path(): string
    {
        return $this->viewDirectory();
    }


    /**
     * add extension to filename
     *
     * @param string $path
     * @return string
     */
    protected function prepareFile(string $path): string
    {

        return $path . '.' . ltrim($this->option('extension') ?? '.blade.php', '.');
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [
            '{{ quote }}' => Inspiring::quotes()->random(),
            '{{quote}}'   => Inspiring::quotes()->random(),
        ];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['extension', null, InputOption::VALUE_OPTIONAL, 'The extension of the generated view', 'blade.php'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the view even if the view already exists'],
        ];
    }
}
