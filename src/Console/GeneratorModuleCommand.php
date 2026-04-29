<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\Concerns\FindsAvailableModels;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\Migration\ModuleMigrationTrait;
use Teksite\Module\Traits\ModuleGeneratorTrait;
use Teksite\Module\Traits\ModuleNameValidator;

abstract class GeneratorModuleCommand extends Command implements PromptsForMissingInput
{
    use ModuleGeneratorTrait;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $files;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type;

    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'parent',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'self',
        'static',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        '__CLASS__',
        '__DIR__',
        '__FILE__',
        '__FUNCTION__',
        '__LINE__',
        '__METHOD__',
        '__NAMESPACE__',
        '__TRAIT__',
    ];

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
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(): ?bool
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->components->error('The name "' . $this->getNameInput() . '" is reserved by PHP.');

            return false;
        }

        if (!$this->isModuleExist($this->getModuleInput())) {
            $this->components->error('The module "' . $this->getModuleInput() . 'is not registered or does not exist.');
            $this->components->error("use steward work instead of module name to make {$this->type} in steward");
            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((!$this->hasOption('force') ||
                !$this->option('force')) &&
            $this->alreadyExists($this->getNameInput())) {
            $this->components->error($this->type . ' already exists.');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($name)));

        $info = $this->type;

        if (isset(class_uses_recursive($this)[CreatesMatchingTest::class])) {
            $this->handleTestCreation($path);
        }

        if (windows_os()) {
            $path = str_replace('/', '\\', $path);
        }

        $this->components->info(sprintf('%s [%s] created successfully.', $info, $path));
        return true;
    }


    protected function resolveStubPath($stub): string
    {
        $path = app('modules.stubs') . '/' .$stub;
        return file_exists($path) ? $path : throw new \Exception ($stub . "doesn't exist in the path: ", $path);
    }



    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Qualify the given model class base name.
     *
     * @return class-string
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace . 'Models\\' . $model
            : $rootNamespace . $model;
    }


    /**
     * Get a list of possible event names.
     *
     * @return array<int, string>
     */
    protected function possibleEvents(): array
    {
        $eventPath = app_path('Events');

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
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace;
    }

    /**
     * Determine if the class already exists.
     *
     * @param string $rawName
     * @return bool
     */
    protected function alreadyExists(string $rawName): bool
    {
        return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @param string $module
     * @return string
     */
    protected function getPath(string $name , string $module): string
    {
        if ($module === 'Steward')
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     * @return string
     */
    protected function makeDirectory(string $path): string
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass(string $name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return $this
     */
    protected function replaceNamespace(string &$stub, string $name): static
    {
        $searches = [
            ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
            ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
            ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getNamespace($name), $this->rootNamespace(), $this->userProviderModel()],
                $stub
            );
        }

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param string $name
     * @return string
     */
    protected function getNamespace($name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceClass($stub, $name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        return str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     *
     * @param string $stub
     * @return string
     */
    protected function sortImports($stub): string
    {
        if (preg_match('/(?P<imports>(?:^use [^;{]+;$\n?)+)/m', $stub, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
        }

        return $stub;
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

        return $name;
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
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }

    /**
     * Get the model for the default guard's user provider.
     *
     * @return string|null
     */
    protected function userProviderModel(): ?string
    {
        $config = $this->laravel['config'];

        $provider = $config->get('auth.guards.' . $config->get('auth.defaults.guard') . '.provider');

        return $config->get("auth.providers.{$provider}.model");
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $module
     * @return bool
     */
    protected function isModuleExist(string $module): bool
    {
        $modules = get_modules_status(true);

        if (!in_array($module, array_keys($modules))) return false;
        if ($modules[$module] === false) $this->line("<fg=yellow;options=bold>{$module} in not active<>");
        return true;
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $name
     * @return bool
     */
    protected function isReservedName(string $name): bool
    {
        return in_array(
            strtolower($name),
            (new Collection($this->reservedNames))
                ->transform(fn($name) => strtolower($name))
                ->all()
        );
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
