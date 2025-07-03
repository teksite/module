<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;


class ControllerMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-controller {name} {module}
     {--A|api : Exclude the create and edit methods from the controller,}
     {--type= : Manually specify the controller stub file to use}
     {--i|invokable : Generate a single method, invokable controller class}
     {--m|model= : Generate a resource controller for the given model}
     {--p|parent= : Generate a nested resource controller class}
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
            $stub = $this->resolveStubPath("/controller/controller.{$type}.stub");
        } elseif ($this->option('parent')) {
            $stub = $this->option('singleton')
                ? $this->resolveStubPath("/controller/controller.nested.singleton.stub")
                : $this->resolveStubPath("/controller/controller.nested.stub");
        } elseif ($this->option('model')) {
            $stub = $this->resolveStubPath("/controller/controller.model.stub");
        } elseif ($this->option('invokable')) {
            $stub = $this->resolveStubPath("/controller/controller.invokable.stub");
        } elseif ($this->option('singleton')) {
            $stub = $this->resolveStubPath("/controller/controller.singleton.stub");
        } elseif ($this->option('resource')) {
            $stub = $this->resolveStubPath("/controller/controller.stub");
        }

        if ($this->option('api') && is_null($stub)) {
            $stub = $this->resolveStubPath("/controller/controller.api.stub");
        } elseif ($this->option('api') && !is_null($stub) && !$this->option('invokable')) {
            $stub = str_replace('.stub', '.api.stub', $stub);
        }

        $stub ??= $this->resolveStubPath("/controller/controller.plain.stub");

        return $stub;

    }


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
    protected function qualifyClass($name)
    {
        $module = $this->argument('module');

        return $this->setNamespace($module,$name , '\\App\\Http\\Controllers');
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in the base namespace.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $rootNamespace = $this->rootNamespace();
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];

        if ($this->option('parent')) {
            $replace = $this->buildParentReplacements();
        }

        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace);
        }

        if ($this->option('creatable')) {
            $replace['abort(404);'] = '//';
        }

        $baseControllerExists = file_exists($this->getPath("{$rootNamespace}\Http\Controllers\Controller"));

        if ($baseControllerExists) {
            $replace["use {$controllerNamespace}\Controller;\n"] = '';
        } else {
            $replace[' extends Controller'] = '';
            $replace["use {$rootNamespace}Http\Controllers\Controller;\n"] = '';
        }

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the replacements for a parent controller.
     *
     * @return array
     */
    protected function buildParentReplacements()
    {

        $parentModelClass = $this->parseModel($this->option('parent'));
        if (!class_exists($parentModelClass) &&
            confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", default: true)) {
            $module = $this->argument('module');
            $model = $this->option('parent');
            $this->call('module:make-model', ['name' =>$model ,'module'=>$module]);
        }

        return [
            'ParentDummyFullModelClass' => $parentModelClass,
            '{{ namespacedParentModel }}' => $parentModelClass,
            '{{namespacedParentModel}}' => $parentModelClass,
            'ParentDummyModelClass' => class_basename($parentModelClass),
            '{{ parentModel }}' => class_basename($parentModelClass),
            '{{parentModel}}' => class_basename($parentModelClass),
            'ParentDummyModelVariable' => lcfirst(class_basename($parentModelClass)),
            '{{ parentModelVariable }}' => lcfirst(class_basename($parentModelClass)),
            '{{parentModelVariable}}' => lcfirst(class_basename($parentModelClass)),
        ];
    }

    /**
     * Build the model replacement values.
     *
     * @param array $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        if (!class_exists($modelClass) && confirm("A {$modelClass} model does not exist. Do you want to generate it?", default: true)) {
            $this->call('module:make-model', ['name' => $modelClass]);
        }

        $replace = $this->buildFormRequestReplacements($replace, $modelClass);

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param string $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    /**
     * Build the model replacement values.
     *
     * @param array $replace
     * @param string $modelClass
     * @return array
     */
    protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        $module = $this->argument('module');
        [$namespace, $storeRequestClass, $updateRequestClass,] = [
            'Illuminate\\Http', 'Request', 'Request',
        ];

        if ($this->option('requests')) {
            $namespace =Module::moduleNamespace($module ,'App\\Http\\Requests');

            [$storeRequestClass, $updateRequestClass] = $this->generateFormRequests(
                $modelClass, $storeRequestClass, $updateRequestClass
            );
        }

        $namespacedRequests = $namespace . '\\' . $storeRequestClass . ';';

        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL . 'use ' . $namespace . '\\' . $updateRequestClass . ';';
        }

        return array_merge($replace, [
            '{{ storeRequest }}' => $storeRequestClass,
            '{{storeRequest}}' => $storeRequestClass,
            '{{ updateRequest }}' => $updateRequestClass,
            '{{updateRequest}}' => $updateRequestClass,
            '{{ namespacedStoreRequest }}' => $namespace . '\\' . $storeRequestClass,
            '{{namespacedStoreRequest}}' => $namespace . '\\' . $storeRequestClass,
            '{{ namespacedUpdateRequest }}' => $namespace . '\\' . $updateRequestClass,
            '{{namespacedUpdateRequest}}' => $namespace . '\\' . $updateRequestClass,
            '{{ namespacedRequests }}' => $namespacedRequests,
            '{{namespacedRequests}}' => $namespacedRequests,
        ]);
    }

    /**
     * Generate the form requests for the given model and classes.
     *
     * @param string $modelClass
     * @param string $storeRequestClass
     * @param string $updateRequestClass
     * @return array
     */
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        $storeRequestClass = 'Store' . class_basename($modelClass) . 'Request';
        $module = $this->argument('module');
        $this->call('module:make-request', [
            'name' => $storeRequestClass,
            'module' => $module,
        ]);

        $updateRequestClass = 'Update' . class_basename($modelClass) . 'Request';

        $this->call('module:make-request', [
            'name' => $updateRequestClass,
            'module' => $module,
        ]);

        return [$storeRequestClass, $updateRequestClass];
    }


    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $type = select('Which type of controller would you like?', [
            'empty' => 'Empty',
            'resource' => 'Resource',
            'singleton' => 'Singleton',
            'api' => 'API',
            'invokable' => 'Invokable',
        ]);

        if ($type !== 'empty') {
            $input->setOption($type, true);
        }

        if (in_array($type, ['api', 'resource', 'singleton'])) {
            $model = suggest(
                "What model should this $type controller be for? (Optional)",
                $this->possibleModels()
            );

            if ($model) {
                $input->setOption('model', $model);
            }
        }
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
