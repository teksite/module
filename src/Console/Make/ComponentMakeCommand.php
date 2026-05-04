<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class ComponentMakeCommand extends GeneratorModuleCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view component class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Component';


    protected function handler(): void
    {
        if (!$this->option('inline')) {
            $this->call('module:make-component-view', ['name' => $this->getViewDir(), 'module' => $this->argument('module')]);
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/view-component.stub');
    }

    protected function path(): string
    {
        return 'app/View/Components';
    }


    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        if ($this->option('inline')) {
            return [
                '{{ view }}' => "<<<'blade'\n<div>\n    <!-- " . Inspiring::quotes()->random() . " -->\n</div>\nblade",
            ];
        }

        return [
            '{{ view }}' => 'view(\'' . $this->getLowerNameModule() . '::' . $this->getView() . '\')',
        ];

    }


    /**
     * Get the view name relative to the view path.
     *
     * @return string view
     */
    protected function getView(): string
    {

        $getViewArray = $this->getViewArray();

        $path = [
            'components',
            ...$getViewArray,
        ];
        return (new Collection($path))
            ->map(fn($segment) => Str::kebab($segment))
            ->implode('.');
    }

    protected function getViewDir(): string
    {

        $getViewArray = $this->getViewArray();

        $path = [
            ...$getViewArray,
        ];
        return (new Collection($path))
            ->map(fn($segment) => Str::kebab($segment))
            ->implode('\\');
    }

    protected function getViewArray(): array
    {
        $segments = explode('/', str_replace('\\', '/', $this->argument('name')));

        $name = array_pop($segments);

        $path = [
            ...$segments,
        ];
        $path[] = $name;

        return $path;
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['inline', null, InputOption::VALUE_NONE, 'Create a component that renders an inline view'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
        ];
    }
}
