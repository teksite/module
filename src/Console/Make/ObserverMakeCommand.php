<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\suggest;

class ObserverMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandsTrait;

    protected $signature = 'module:make-observer {name} {module}
        {--f|force : Create the class even if the cast already exists }
        {--m|model= : Generate a resource controller for the given model}
    ';

    protected $description = 'Create a new observer in the specific module';

    protected $type = 'Observer';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('model')
            ? $this->resolveStubPath('/observer.stub')
            : $this->resolveStubPath('/observer.plain.stub');
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

        return $this->setNamespace($module,$name , '\\App\\Observer');
    }
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);
        $module = $this->argument('module');

        $modelName = $this->option('model');
        if ($modelName){
            if (File::exists(module_path($module, "App/Modules/$modelName.php"))) {
                $model = module_namespace($module, "App\\Modules\\$modelName");
            } elseif (!File::exists(module_path($module, "App/Modules/$modelName.php")) && File::exists(app_path("Models/$modelName.php"))) {
                $model = "App\\Modules\\$modelName";
            } else {
                $model = $modelName;
            }
        }
        return $modelName ? $this->replaceModel($stub, $model) : $stub;
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

    protected function replaceModel($stub, $model)
    {
        $modelClass = $this->parseModel($model);
        $replace = [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ];

        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
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
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $model = suggest(
            'What model should this observer apply to? (Optional)',
            $this->possibleModels(),
        );

        if ($model) {
            $input->setOption('model', $model);
        }
    }




}
