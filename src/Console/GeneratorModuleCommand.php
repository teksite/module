<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Teksite\Module\Console\Make\traits\ModuleGeneratorTrait;
use Teksite\Module\Console\Make\traits\ModuleValidationGeneratorTrait;

abstract class GeneratorModuleCommand extends Command implements PromptsForMissingInput
{
    use ModuleGeneratorTrait, ModuleValidationGeneratorTrait;

    protected string $generatorType = 'class';

    protected null|string $namespace = null;

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


    public function handle(): void
    {
        $name = $this->getNameInput();
        if ($this->isReservedName($name)) {
            $this->components->error('The name "' . $name . '" is reserved by PHP.');
            return;
        }
        $module = $this->getModuleInput();
        if (!$this->isModuleExist($module)) {
            $this->components->error('The module "' . $module . 'is not registered or does not exist.');
            $this->components->error("use steward work instead of module name to make {$this->type} in steward");
            return;
        }

        if ($this->generatorType === 'class') {
            $this->getNamespace($module, $name);

        }
        $path = $this->getPath($name, $module);
        if (!$this->checkForce($path)) return;

        $contentClass = $this->buildClass($module, $name);
        $this->makeFile($contentClass, $path, $module);


    }


    protected function resolveStubPath($stub): string
    {
        $path = app('modules.stubs') . '/' . $stub;
        return file_exists($path) ? $path : throw new \Exception ($stub . "doesn't exist in the path: ", $path);
    }


    protected function getPath(string $name, string $module): string
    {
        $path = $module === 'steward' ? steward_path($this->path() . '/' . $name, false) : module_path($module, $this->path() . '/' . $name, false);
        $this->makeDirectory($path);
        return normalizeSlashPath("$path.php");
    }


    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     * @return void
     */
    protected function makeDirectory(string $path): void
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
     */
    protected function getNamespace(string $module, string $name): string
    {
        $fullNamespace = $this->getModuleNamespace($module, $this->path()) . '\\' . $name;
        $namespace = trim(implode('\\', array_slice(explode('\\', $fullNamespace), 0, -1)), '\\');

        $this->namespace = $namespace;
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
            return Str::substr($name, 0, -4);
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
     * Get the first view directory path from the application configuration.
     *
     * @param string $path
     * @return string
     */
    protected function viewPath(string $path = ''): string
    {
        $views = $this->laravel['config']['view.paths'][0] ?? resource_path('views');

        return $views . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }


    /**
     * @throws FileNotFoundException
     */
    protected function buildClass($module, $name): string
    {
        $stub = $this->files->get($this->getStub());
        array_merge($this->replacements());
        $replacements = collect([
            '{{ namespace }}' => $this->namespace,
            '{{namespace}}'   => $this->namespace,
            '{{ class }}'     => $this->getClassName(),
            '{{class}}'       => $this->getClassName(),
        ])->merge($this->replacements())
          ->merge($this->replacements ?? [])
          ->unique()
          ->toArray();

        return str_replace(array_keys($replacements), array_values($replacements), $stub);


    }

    /**
     * make class file
     *
     * @param string $contentClass
     * @param string $path
     * @param string $module
     * @return void
     */
    public function makeFile(string $contentClass, string $path, string $module): void
    {
        $this->files->put($path, $contentClass);
        $this->newLine();
        $this->components->twoColumnDetail("$module| the {$this->type} file has been created.", $path);
        $this->newLine();


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
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string|array{string, string}|\Closure(): (array<int, string>|string|int|bool)>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => [
                'What should the ' . strtolower($this->type) . ' be named?',
                match ($this->type) {
                    'Attribute'       => 'E.g. SlugAttribute',
                    'Cast'            => 'E.g. Json',
                    'Channel'         => 'E.g. OrderChannel',
                    'Console command' => 'E.g. SendEmails',
                    'Component'       => 'E.g. Alert',
                    'Controller'      => 'E.g. UserController',
                    'Event'           => 'E.g. PodcastProcessed',
                    'Exception'       => 'E.g. InvalidOrderException',
                    'Factory'         => 'E.g. PostFactory',
                    'Job'             => 'E.g. ProcessPodcast',
                    'Listener'        => 'E.g. SendPodcastNotification',
                    'Mailable'        => 'E.g. OrderShipped',
                    'Middleware'      => 'E.g. EnsureTokenIsValid',
                    'Model'           => 'E.g. Flight',
                    'Notification'    => 'E.g. InvoicePaid',
                    'Observer'        => 'E.g. UserObserver',
                    'Policy'          => 'E.g. PostPolicy',
                    'Provider'        => 'E.g. ElasticServiceProvider',
                    'Request'         => 'E.g. StorePodcastRequest',
                    'Resource'        => 'E.g. UserResource',
                    'Rule'            => 'E.g. Uppercase',
                    'Scope'           => 'E.g. TrendingScope',
                    'Seeder'          => 'E.g. UserSeeder',
                    'Test'            => 'E.g. UserTest',
                    default           => '',
                },
            ],
        ];
    }


    /**
     * @return mixed
     */
    protected function findAvailableModels(): mixed
    {
        $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();

        return (new Collection(Finder::create()->files()->depth(0)->in($modelPath)))
            ->map(fn($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }
}
