<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Teksite\Module\Console\Make\traits\ModuleGeneratorTrait;
use Teksite\Module\Console\Make\traits\ModuleValidationGeneratorTrait;


abstract class GeneratorModuleCommand extends Command
{
    use ModuleGeneratorTrait, ModuleValidationGeneratorTrait;

    /**
     * set class to make class , set file to create normal class
     *
     * @var string
     */
    protected string $generatorType = 'class';

    protected null|string $namespace = null;
    protected null|string $modulesNamespace = null;
    protected null|string $moduleNamespace = null;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type;


    /**
     * Create a new generator command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        if (isset(class_uses_recursive($this)[CreatesMatchingTest::class])) {
            $this->addTestOptions();
        }

        $this->files = $files;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    abstract protected function getStub(): string;

    /**
     * set the path of the file.
     *
     * @return string
     */
    protected abstract function path(): string;

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace]
     */
    protected abstract function replacements(): array;

    /**
     * execute the command
     *
     * @throws \Teksite\Module\Exception\FileNotFoundException|FileNotFoundException
     */
    public function handle(): void
    {

        $name = $this->getNameInput();
        if ($this->isReservedName($name)) {
            $this->components->error('The name "' . $name . '" is reserved by PHP.');
            return;
        }
        $module = $this->getModuleInput();
        if (!$this->isModuleExist($module)) {
            $this->components->error('The module "' . $module . ' is not registered or does not exist.');
            $this->components->error("use steward work instead of module name to make {$this->type} in steward");
            return;
        }


        #TODO add/suggest missing inputs

//        if ($this instanceof PromptsForMissingInput) {
//        }

        if ($this->generatorType === 'class') {
            $this->getNamespace($module, $name);
        }

        $path = $this->getPath($name, $module);
        $arrayPath = explode(DIRECTORY_SEPARATOR, $path);

        $fileName = array_pop($arrayPath);

        $filename = $this->resolveFilename($fileName);

        $filename = $this->addExtensionToFilename($filename);


        $fullFilePath = implode(DIRECTORY_SEPARATOR, $arrayPath) . DIRECTORY_SEPARATOR . $filename;

        if (!$this->checkForce($fullFilePath)) return;

        $this->ensureDirectoryExistence($fullFilePath);

        $contentClass = $this->buildFile();

        $this->makeFile($contentClass, $fullFilePath);
        $this->handler();

        $this->newLine();

        if (isset(class_uses_recursive($this)[CreatesMatchingTest::class])) {
            $this->handleTestCreation($fullFilePath);
        }

        $this->components->twoColumnDetail("$module| the {$this->type} file has been created.", $fullFilePath);
        $this->newLine();
    }


    protected function resolveStubPath($stub): string
    {
        $path = app('modules.stubs') . '/' . trim($stub, '/\\');
        return file_exists($path) ? $path : throw new \Exception ($stub . "doesn't exist in the path: ", $path);
    }


    protected function getPath(string $name, string $module): string
    {
        return $module === 'Steward'
            ? steward_path($this->path() . DIRECTORY_SEPARATOR . $name, false)
            : module_path($module, trim($this->path(), '/\\') . DIRECTORY_SEPARATOR . $name, false);
    }


    /**
     * change final filename if necessary
     *
     * @param string $filename
     * @return string
     */
    protected function resolveFilename(string $filename): string
    {
        return $filename;
    }

    /**
     * add extension to filename
     *
     * @param string $filename
     * @return string
     */
    protected function addExtensionToFilename(string $filename): string
    {
        return $filename . '.php';
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     * @return void
     */
    protected function ensureDirectoryExistence(string $path): void
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * check if the file is existed or not
     *
     * @param $path
     * @return bool
     */
    protected function alreadyExists($path): bool
    {
        return $this->files->exists($path);

    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param string $module
     * @param string $name
     * @return string
     * @throws \Teksite\Module\Exception\FileNotFoundException
     */
    protected function getNamespace(string $module, string $name): string
    {
        $fullNamespace = $this->getModuleDirNamespace($module, $this->path()) . '\\' . $name;
        $namespace = trim(implode('\\', array_slice(explode('\\', $fullNamespace), 0, -1)), '\\');
        $namespace = normalizeSlashPath($namespace);

        $this->namespace = $namespace;
        $this->modulesNamespace = $this->getModuleInput() === 'Steward'
            ? steward_namespace()
            : module_namespace();
        $this->moduleNamespace = $this->getModuleInput() === 'Steward'
            ? steward_namespace()
            : module_namespace($module);
        return $namespace;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        $name = trim($this->argument('name'));

        if (Str::endsWith($name, '.php')) {
            $name = Str::substr($name, 0, -4);
        }


        return normalizeSlashPath($name);
    }

    /**
     * Get name of the class from the input.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        $name = $this->getNameInput();
        return array_last(explode('\\', $name));

    }

    /**
     * Get the module name or steward.
     *
     * @return string
     */
    protected function getModuleInput(): string
    {
        return trim($this->argument('module'));

    }

    /**
     * return lowercase of the name of the module
     *
     * @return string
     */
    protected function getLowerNameModule(): string
    {
        return Str::lower($this->getModuleInput());
    }


    /**
     * @throws FileNotFoundException
     */
    protected function buildFile(): string
    {
        $stub = $this->files->get($this->getStub());
        $replacements = collect([
            '{{ namespace }}' => $this->namespace,
            '{{namespace}}'   => $this->namespace,
            '{{ class }}'     => $this->getClassName(),
            '{{class}}'       => $this->getClassName(),
        ])
            ->merge($this->replacements())
            ->merge($this->replacements ?? [])
            ->toArray();

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * make class file
     *
     * @param string $contentClass
     * @param string $path
     * @return void
     */
    public function makeFile(string $contentClass, string $path): void
    {
        $this->files->put($path, $contentClass);
    }

    /**
     * Get the console command arguments.
     *
     * @return (InputArgument|array{
     *    0: non-empty-string,
     *    1?: InputArgument::REQUIRED|InputArgument::OPTIONAL|InputArgument::IS_ARRAY,
     *    2?: string,
     *    3?: mixed,
     *    4?: list<string|Suggestion>|\Closure(CompletionInput, CompletionSuggestions): list<string|Suggestion>
     * })[]
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the ' . strtolower($this->type)],
            ['module', InputArgument::REQUIRED, 'The name of the module or steward'],
        ];
    }


    /**
     * @return mixed
     */
    protected function findAvailableModels(): mixed
    {
        $modelPath = $this->getModuleInput() === 'Steward'
            ? steward_path('App\Models')
            : module_path($this->getModuleInput(), 'App\Models');

        return (new Collection(Finder::create()->files()->depth(0)->in($modelPath)))
            ->map(fn($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Get a list of possible event names.
     *
     * @return array<int, string>
     */
    protected function possibleEvents(): array
    {
        $eventPath = $this->getModuleInput() === 'Steward'
            ? steward_path('app/Events')
            : module_path($this->getModuleInput(), 'app/Events');

        if (!is_dir($eventPath)) {
            return [];
        }

        return (new Collection(Finder::create()->files()->depth(0)->in($eventPath)))
            ->map(fn($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Qualify the given model class base name.
     *
     * @return class-string|null
     */
    protected function qualifyModel(string $model, ?string $term = null, bool $check = false): ?string
    {
        $modelNamespace = $this->guessModel($model, $term);

        if ($check && !class_exists($modelNamespace)) {
            $answer = $this->confirm('the related model class does not exist. Do you want to continue?');
            if (!$answer) {
                return null;
            }
        }
        return $modelNamespace;
    }


    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @param string $name
     * @param string|null $term
     * @return string
     */
    protected function guessModel(string $name, ?string $term = null): string
    {

        if ($term && str_ends_with($name, $term)) {
            $model = substr($name, 0, -strlen($term));
        } else {
            $model = $name;
        }

        $model = trim($model, '\\/');
        $model = str_replace(['\\\\', '/', '//'], '\\', $model);

        $stewardNamespace = steward_namespace();
        $rootStewardPattern = $stewardNamespace . '\\App\\Models\\';
        $stewardBasePattern = 'Steward\\App\\Models\\';

        if (Str::startsWith($model, $rootStewardPattern)) {
            return $model;
        }

        if (Str::startsWith($model, $stewardBasePattern)) {
            $model = Str::replaceFirst($stewardBasePattern, '', $model);
            return 'Lareon' . '\\' . $model;
        }

        $modulesNamespace = module_namespace();
        $rootModulesPattern = '/^Lareon\\\\Modules\\\\([^\\\\s]+)\\\\App\\\\Modules\\\\(.+)$/';
        $modulesAppModelsPattern = '/^([^\\\\s]+)\\\\App\\\\Modules\\\\(.+)$/';

        if (preg_match($rootModulesPattern, $model)) {
            return $model;
        }
        if (preg_match($modulesAppModelsPattern, $model)) {
            return 'Lareon\\Modules\\' . $model;
        }

        if (Str::startsWith($model, 'App\\Models\\')) {
            return $model;
        }

        return module_namespace($this->getModuleInput()) . '\\App\\Models\\' . $model;
    }


    protected function modelNameReplaces(): array
    {

        $modelNamespace = $this->qualifyModel($this->option('model'));
        $model = class_basename($modelNamespace);
        $modelVariable = lcfirst($model);
        return [
            '{{ model }}'           => $model,
            '{{model}}'             => $model,
            '{{ modelVariable }}'   => $modelVariable,
            '{{modelVariable}}'     => $modelVariable,
            '{{ namespacedModel }}' => $modelNamespace,
            '{{namespacedModel}}'   => $modelNamespace,

        ];
    }


    protected function userNameReplaces(): array
    {

        $userModelNamespace = $this->userProviderModel();
        $userClassName = class_basename($userModelNamespace);

        return [

            '{{ namespacedUserModel }}' => $userModelNamespace,
            '{{namespacedUserModel}}'   => $userModelNamespace,
            '{{ user }}'                => $userClassName,
            '{{user}}'                  => $userClassName,
            '$user'                     => '$' . Str::camel($userClassName),
        ];
    }



    /**
     * Get the model for the guard's user provider.
     *
     * @return string|null
     *
     * @throws \LogicException
     */
    protected function userProviderModel(): ?string
    {
        $config = $this->laravel['config'];

        $guard = $this->hasOption('guard') ?  $this->option('guard') : $config->get('auth.defaults.guard');

        if (is_null($guardProvider = $config->get('auth.guards.' . $guard . '.provider'))) {
            throw new LogicException('The [' . $guard . '] guard is not defined in your "auth" configuration file.');
        }

        if (!$config->get('auth.providers.' . $guardProvider . '.model')) {
            return 'App\\Models\\User';
        }

        return $config->get(
            'auth.providers.' . $guardProvider . '.model'
        );
    }


    /**
     * extra code if needed in commands
     */
    protected function handler()
    {

    }

}
