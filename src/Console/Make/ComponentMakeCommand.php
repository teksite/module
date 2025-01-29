<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;


class ComponentMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandsTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:make-component {name} {module}
     {--f|force : Create the class even if the cast already exists }
     {--inline : Create a component that renders an inline view }
     {--view : Create an anonymous component with only a view }
     {--path : The location where the component view should be created }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view component class in the specific module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Component';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/view-component.stub');
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name): string
    {
        $module = $this->argument('module');
        return $this->setPath($name,'php');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name): string
    {
        $module = $this->argument('module');

        return $this->setNamespace($module,$name , '\\App\\View');
    }


    /**
     * Write the view for the component.
     *
     * @return void
     */
    protected function writeView()
    {
        $path = $this->viewPath(
            str_replace('.', '/', $this->getView()).'.blade.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
        }

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('View already exists.');

            return;
        }

        file_put_contents(
            $path,
            '<div>
    <!-- '.Inspiring::quotes()->random().' -->
</div>'
        );
        $this->components->info(sprintf('%s [%s] created successfully.', 'View', $path));
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($this->option('inline')) {
            return str_replace(
                ['DummyView', '{{ view }}'],
                "<<<'blade'\n<div>\n    <!-- ".Inspiring::quotes()->random()." -->\n</div>\nblade",
                parent::buildClass($name)
            );
        }
        $moduleLowerName=$this->getLowerNameModule();
        return str_replace(
            ['DummyView', '{{ view }}'],
            'view(\''.$moduleLowerName.'::'.$this->getView().'\')',
            parent::buildClass($name)
        );
    }

    /**
     * Get the view name relative to the view path.
     *
     * @return string view
     */
    protected function getView()
    {
        $segments = explode('/', str_replace('\\', '/', $this->argument('name')));

        $name = array_pop($segments);

        $path = is_string($this->option('path'))
            ? explode('/', trim($this->option('path'), '/'))
            : [
                'components',
                ...$segments,
            ];

        $path[] = $name;
        return (new Collection($path))
            ->map(fn ($segment) => Str::kebab($segment))
            ->implode('.');
    }


    public function handle()
    {

        $module = $this->argument('module');

        [$isValid, $suggestedName] = $this->validateModuleName($module);

        if ($isValid) return $this->generateViews();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return $this->generateViews();
        }
        $this->error("The module '".$module."' does not exist.");
        return 1;
    }

    protected function generateViews(){
        if ($this->option('view')) {

            return $this->writeView();
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if (! $this->option('inline')) {
            $this->writeView();
        }
    }



}
