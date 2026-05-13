<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Teksite\Module\Console\Make\traits\ModuleGeneratorTrait;
use Teksite\Module\Console\Make\traits\ModuleValidationGeneratorTrait;
use Teksite\Module\Console\Make\traits\ReplaceStubGeneratorTrait;
use Teksite\Module\Exception\FileNotFoundException;

abstract class GeneratorModuleCommand extends Command
{
    use ModuleGeneratorTrait, ModuleValidationGeneratorTrait, ReplaceStubGeneratorTrait;

    /**
     * The type of generation (class or file).
     */
    protected string $generatorType = 'class';

    /**
     * namespace before filename
     * Namespace instances.
     */
    protected ?string $namespace = null;

    /**
     * namespace of the file
     *
     * @var string|null
     */
    protected ?string $fullNamespace = null;

    /**
     * modules root namespace
     *
     * @var string|null
     */
    protected ?string $modulesNamespace = null;

    /**
     * module root namespace
     *
     * @var string|null
     */
    protected ?string $moduleNamespace = null;

    /**
     * final filename
     *
     * @var string|null
     */
    protected ?string $filename = null;
    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * The type of class being generated.
     */
    protected string $type;


    // Abstract methods

    /**
     * path of stub file
     *
     * @return string
     */
    abstract protected function getStub(): string;


    /**
     * desired path for making file or class - based on autoload(-dev)
     *
     * @return string
     */
    abstract protected function path(): string;

    /**
     * replace items is stub file
     *
     * @return array
     */
    abstract protected function replacements(): array;


    public function __construct(Filesystem $files)
    {
        parent::__construct();

        if ($this->usesCreatesMatchingTestTrait()) {
            $this->addTestOptions();
        }

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->newLine();

        $name = $this->getNameInput();

        if ($this->isReservedName($name)) {
            $this->errorReservedName($name);
            return;
        }

        $module = $this->getModuleInput();

        if (!$this->isModuleExist($module)) {
            $this->errorModuleNotExists($module);
            return;
        }

        $this->prepareNamespaces($module, $name);

        $path = $this->getPath($name, $module);
        $fullFilePath = $this->buildFullFilePath($path);

        if (!$this->checkForce($fullFilePath)) {
            return;
        }

        $this->ensureDirectoryExistence($fullFilePath);
        $this->generateAndWriteFile($fullFilePath);

        $this->handleTestCreationIfNeeded($fullFilePath);
        $this->displaySuccessMessage($module, $fullFilePath);
        $this->newLine();
    }

    /**
     * Build the complete file path with proper filename.
     */
    private function buildFullFilePath(string $path): string
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);
        $fileName = array_pop($pathParts);
        $resolvedFilename = $this->resolveFilename($fileName);
        $this->filename = $resolvedFilename;
        $finalFilename = $this->addExtensionToFilename($resolvedFilename);
        return implode(DIRECTORY_SEPARATOR, $pathParts) . DIRECTORY_SEPARATOR . $finalFilename;
    }

    /**
     * Generate and write the file content.
     */
    private function generateAndWriteFile(string $fullFilePath): void
    {
        $content = $this->buildFile();
        $this->makeFile($content, $fullFilePath);
        $this->handler();
    }

    /**
     * Handle test creation if the trait is used.
     */
    private function handleTestCreationIfNeeded(string $fullFilePath): void
    {
        if ($this->usesCreatesMatchingTestTrait()) {
            $this->handleTestCreation($fullFilePath);
        }
    }

    /**
     * Display success message.
     */
    private function displaySuccessMessage(string $module, string $fullFilePath): void
    {
        $this->components->twoColumnDetail(
            "{$module} | the {$this->type} file has been created.",
            $fullFilePath
        );
    }

    /**
     * Display reserved name error.
     */
    private function errorReservedName(string $name): void
    {
        $this->components->error("The name \"{$name}\" is reserved by PHP.");
    }

    /**
     * Display module not exists error.
     */
    private function errorModuleNotExists(string $module): void
    {
        $this->components->error("The module \"{$module}\" is not registered or does not exist.");
        $this->components->error("Use 'Steward' instead of module name to make {$this->type} in steward.");
    }

    /**
     * Prepare namespace configurations.
     */
    private function prepareNamespaces(string $module, string $name): void
    {
        if ($this->generatorType === 'class') {
            $this->getNamespace($module, $name);

            $isSteward = $this->getModuleInput() === 'Steward';
            $this->modulesNamespace = $isSteward ? steward_namespace() : module_namespace();
            $this->moduleNamespace = $isSteward ? steward_namespace() : module_namespace($module);

        }
    }

    /**
     * Check if the command uses the CreatesMatchingTest trait.
     */
    private function usesCreatesMatchingTestTrait(): bool
    {
        return isset(class_uses_recursive($this)[CreatesMatchingTest::class]);
    }


    /**
     * Resolve the stub file path.
     * @throws \Exception
     */
    protected function resolveStubPath(string $stub): string
    {
        $path = app('modules.stubs') . '/' . trim($stub, '/\\');

        if (!file_exists($path)) {
            throw new \Exception("{$stub} doesn't exist in the path: {$path}");
        }

        return $path;
    }

    /**
     * Get the full file path for generation.
     */
    protected function getPath(string $name, string $module): string
    {
        return $this->module_path($module, $this->path() . DIRECTORY_SEPARATOR . $name, false);
    }

    /**
     * Resolve filename (override in child classes if needed).
     */
    protected function resolveFilename(string $filename): string
    {
        return $filename;
    }

    /**
     * Add extension to filename.
     */
    protected function addExtensionToFilename(string $filename): string
    {
        return "{$filename}.php";
    }

    /**
     * Ensure directory exists for the given path.
     */
    protected function ensureDirectoryExistence(string $path): void
    {
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0777, true, true);
        }
    }

    /**
     * Check if file already exists.
     */
    protected function alreadyExists(string $path): bool
    {
        return $this->files->exists($path);
    }

    /**
     * Get the full namespace for a given class.
     * @throws FileNotFoundException
     */
    protected function getNamespace(string $module, string $name): string
    {
        $fullNamespace = $this->getModuleDirNamespace($module, $this->path()) . '\\' . $name;
        $namespaceParts = explode('\\', $fullNamespace);
        $namespace = trim(implode('\\', array_slice($namespaceParts, 0, -1)), '\\');

        $this->namespace = normalizeSlashPath($namespace);
        $this->fullNamespace = $fullNamespace;
        return $this->namespace;
    }


    /**
     * Get the desired class name from input.
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
     * Get the class name without namespace.
     */
    protected function getClassName(): string
    {
        if ($this->filename) return $this->filename;

        $name = $this->getNameInput();
        $parts = explode('\\', $name);

        return array_pop($parts);
    }

    /**
     * Get the module name from input.
     */
    protected function getModuleInput(): string
    {
        return trim($this->argument('module'));
    }

    /**
     * Get lowercase module name.
     */
    protected function getLowerNameModule(): string
    {
        return Str::lower($this->getModuleInput());
    }

    /**
     * Build the file content with replacements.
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildFile(): string
    {
        $stub = $this->files->get($this->getStub());

        $replacements = array_merge(
            $this->getDefaultReplacements(),
            $this->replacements(),
            $this->replacements ?? []
        );

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
    }

    /**
     * Get default replacements array.
     */
    private function getDefaultReplacements(): array
    {
        return [
            '{{ namespace }}' => $this->namespace,
            '{{namespace}}'   => $this->namespace,
            '{{ class }}'     => $this->getClassName(),
            '{{class}}'       => $this->getClassName(),
        ];
    }

    /**
     * Write content to file.
     */
    public function makeFile(string $content, string $path): void
    {
        $this->files->put($path, $content);
    }

    /**
     * integrate module and steward paths
     *
     * @param string $module
     * @param string|null $path
     * @param bool $absolute
     * @return string
     */
    protected function module_path(string $module, null|string $path = null, bool $absolute = false): string
    {
        if ($module === 'Steward') {
            return steward_path($path, $absolute);
        }
        return module_path($module, $path, $absolute);
    }

    /**
     * integrate module and steward paths
     *
     * @param string $module
     * @param string|null $path
     * @return string
     */
    protected function module_namespace(string $module, null|string $path = null): string
    {
        $pathNamespace = $path ? "\\" . normalizeSlashNamespace($path) : '';
        if ($module === 'Steward') {
            return steward_namespace() . $pathNamespace;
        }
        return module_namespace($module) . $pathNamespace;
    }

    /**
     * Get console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, "The name of the " . strtolower($this->type)],
            ['module', InputArgument::REQUIRED, 'The name of the module or steward'],
        ];
    }

    /**
     * Additional handler logic (override in child classes).
     */
    protected function handler(): void
    {
        // Override in child classes
    }


}
