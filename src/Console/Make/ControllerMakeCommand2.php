<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;


class ControllerMakeCommand2 extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandTrait;

    protected $signature = 'module:make-controller {name} {module}
     {--A|api : Exclude the create and edit methods from the controller,}
     {--type= : Manually specify the controller stub file to use}
     {--i|invokable : Generate a single method, invokable controller class}
     {--m|model= : Generate a resource controller for the given model}
     {--p|parent : Generate a nested resource controller class}
     {--r|resource : Generate a resource controller class}
     {--s|singleton : Generate a singleton resource controller class}
     {--creatable : Indicate that a singleton resource should be creatable}

     {--R|requests : Generate FormRequest classes for store and update}
     {--m|model= : Generate a resource controller for the given model}
    ';

    protected $description = 'Create a new controller class in the specific module';

    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = null;

        if ($type = $this->option('type')) {
            $stub = __DIR__ . "/../../stubs/controller/controller.{$type}.stub";
        } elseif ($this->option('parent')) {
            $stub = $this->option('singleton')
                ? __DIR__ . '/../../stubs/controller/controller.nested.singleton.stub'
                : __DIR__ . '/../../stubs/controller/controller.nested.stub';
        } elseif ($this->option('model')) {
            $stub = __DIR__ . '/../../stubs/controller/controller.model.stub';
        } elseif ($this->option('invokable')) {
            $stub = __DIR__ . '/../../stubs/controller/controller.invokable.stub';
        } elseif ($this->option('singleton')) {
            $stub = __DIR__ . '/../../stubs/controller/controller.singleton.stub';
        } elseif ($this->option('resource')) {
            $stub = __DIR__ . '/../../stubs/controller/controller.stub';
        }

        if ($this->option('api') && is_null($stub)) {
            $stub =  __DIR__ . '/../../stubs/controller/controller.api.stub';
        } elseif ($this->option('api') && !is_null($stub) && !$this->option('invokable')) {
            $stub = str_replace('.stub', '.api.stub', $stub);
        }

        $stub ??= __DIR__ .'/../../stubs/controller/controller.plain.stub';
        return $stub;
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
        return $this->setDefaultPath($module, $name ,'/App/Http/Controllers/');
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
        return $this->setDefaultNamespace($module,$name , '\\App\\Http\\Controllers\\');
    }
    public function handle(): bool|int|null
    {
        $module = $this->argument('module');
        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) return parent::handle();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return parent::handle();
        }
        $this->error("The module '".$module."' does not exist.");
        return 1;
    }
}
