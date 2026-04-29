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
use Teksite\Module\Traits\ModuleValidationGeneratorTrait;

abstract class GeneratorModuleCommand extends Command implements PromptsForMissingInput
{
    use ModuleGeneratorTrait, ModuleValidationGeneratorTrait;

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


    public function handle(): void
    {
        $name = $this->getNameInput();
        if ($this->isReservedName($name)) {
            $this->components->error('The name "' . $name . '" is reserved by PHP.');
            return;
        }
        $module = $this->getModuleInput();
        if (!$this->isModuleExist($module)) {
            $this->components->error('The module "' .$module . 'is not registered or does not exist.');
            $this->components->error("use steward work instead of module name to make {$this->type} in steward");
            return;
        }

        $this->getModuleNamespace($module , $this->path());

        $path = $this->getPath($name , $module);
        dd($this->namespace ,$path);



    }


    protected function resolveStubPath($stub): string
    {
        $path = app('modules.stubs') . '/' . $stub;
        return file_exists($path) ? $path : throw new \Exception ($stub . "doesn't exist in the path: ", $path);
    }


    protected function getPath(string $name, string $module): string
    {
        $path = $module === 'steward' ? steward_path() : module_path($module);
        return normalizeSlashPath("$path/$name.php");
    }

    protected abstract function path() :string;


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
