<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class ViewMakeCommand extends GeneratorModuleCommand
{
    //TODO make test

    //    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'View';


    protected string $generatorType = 'file';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/view.stub');
    }

    protected function path(): string
    {
        return $this->viewPath();
    }

    /**
     * add extension to filename
     *
     * @param string $path
     * @return string
     */
    protected function prepareFile(string $path): string
    {

        return $path . '.' . ltrim($this->option('extension') ?? '.blade.php', '.');
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [
            '{{ quote }}' => Inspiring::quotes()->random(),
            '{{quote}}'   => Inspiring::quotes()->random(),
        ];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['extension', null, InputOption::VALUE_OPTIONAL, 'The extension of the generated view', 'blade.php'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the view even if the view already exists'],
        ];
    }

    /**
     * Get the destination test case path.
     *
     * @return string
     */
    protected function getTestPath(): string
    {
        return base_path(
            Str::of($this->testClassFullyQualifiedName())
               ->replace('\\', '/')
               ->replaceFirst('Tests/Feature', 'tests/Feature')
               ->append('Test.php')
               ->value()
        );
    }

    /**
     * Create the matching test case if requested.
     *
     * @param string $path
     * @throws FileNotFoundException
     */
    protected function handleTestCreation($path): bool
    {
        if (!$this->option('test') && !$this->option('pest') && !$this->option('phpunit')) {
            return false;
        }

        $contents = preg_replace(
            ['/\{{ namespace \}}/', '/\{{ class \}}/', '/\{{ name \}}/'],
            [$this->testNamespace(), $this->testClassName(), $this->testViewName()],
            File::get($this->getTestStub()),
        );

        File::ensureDirectoryExists(dirname($this->getTestPath()), 0755, true);

        $result = File::put($path = $this->getTestPath(), $contents);

        $this->components->info(sprintf('%s [%s] created successfully.', 'Test', $path));

        return $result !== false;
    }

    /**
     * Get the namespace for the test.
     *
     * @return string
     */
    protected function testNamespace(): string
    {
        return Str::of($this->testClassFullyQualifiedName())
                  ->beforeLast('\\')
                  ->value();
    }

    /**
     * Get the class name for the test.
     *
     * @return string
     */
    protected function testClassName(): string
    {
        return Str::of($this->testClassFullyQualifiedName())
                  ->afterLast('\\')
                  ->append('Test')
                  ->value();
    }

    /**
     * Get the class fully-qualified name for the test.
     *
     * @return string
     */
    protected function testClassFullyQualifiedName(): string
    {
        $name = Str::of(Str::lower($this->getNameInput()))->replace('.' . $this->option('extension'), '');

        $namespacedName = Str::of(
            (new Stringable($name))
                ->replace('/', ' ')
                ->explode(' ')
                ->map(fn($part) => (new Stringable($part))->ucfirst())
                ->implode('\\')
        )
                             ->replace(['-', '_'], ' ')
                             ->explode(' ')
                             ->map(fn($part) => (new Stringable($part))->ucfirst())
                             ->implode('');

        return 'Tests\\Feature\\View\\' . $namespacedName;
    }

    /**
     * Get the test stub file for the generator.
     *
     * @return string
     */
    protected function getTestStub(): string
    {
        $stubName = 'view.' . ($this->usingPest() ? 'pest' : 'test') . '.stub';

        return file_exists($customPath = $this->laravel->basePath("stubs/$stubName"))
            ? $customPath
            : __DIR__ . '/stubs/' . $stubName;
    }

    /**
     * Get the view name for the test.
     *
     * @return string
     */
    protected function testViewName(): string
    {
        return Str::of($this->getNameInput())
                  ->replace('/', '.')
                  ->lower()
                  ->value();
    }

    /**
     * Determine if Pest is being used by the application.
     *
     * @return bool
     */
    protected function usingPest(): bool
    {
        if ($this->option('phpunit')) {
            return false;
        }

        return $this->option('pest') ||
            (function_exists('\Pest\\version') &&
                file_exists(base_path('tests') . '/Pest.php'));
    }


}
