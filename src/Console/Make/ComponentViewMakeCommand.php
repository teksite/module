<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\ViewHandlerTrait;

class ComponentViewMakeCommand extends GeneratorModuleCommand
{
    use ViewHandlerTrait;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-component-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view file in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Component';

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
        return $this->viewPath() .'/Components';
    }

    /**
     * add extension to filename
     *
     * @param string $filename
     * @return string
     */
    protected function addExtensionToFilename(string $filename): string
    {
        return $filename . '.' . ltrim($this->option('extension') ?? '.blade.php', '.');
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
