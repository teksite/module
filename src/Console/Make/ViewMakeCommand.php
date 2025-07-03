<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\select;

class ViewMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-view {name} {module}
         {--f|force : Create the test even if the view already exists }
         {--extension=blade.php : The extension of the generated view }

    ';


    protected $description = 'Create a new view in the specific module';

    protected $type = 'View';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/view.stub');
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setPath($name,$this->option('extension') ?? 'blade.php');
    }

    protected function rootNamespace()
    {
        $module = $this->argument('module');
        return config('lareon.module.namespace') . $module;
    }

    public function handle(): bool|int|null
    {
        $module = $this->argument('module');

        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) {
            return $this->writeView();
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return $this->writeView();
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }

    protected function writeView()
    {

        $path = $this->viewPath(
            str_replace('.', '/', $this->getView()). '.'. $this->option('extension') ??'blade.php'
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
    protected function getView()
    {
        $segments = explode('/', str_replace('\\', '/', $this->argument('name')));

        $name = array_pop($segments);

        $path =  [
                'components',
                ...$segments,
            ];

        $path[] = $name;
        return (new Collection($path))
            ->map(fn ($segment) => Str::kebab($segment))
            ->implode('.');
    }




    protected function buildClass($name)
    {
        $contents = parent::buildClass($name);

        return str_replace(
            '{{ quote }}',
            Inspiring::quotes()->random(),
            $contents,
        );
    }

    /**
     * Get the destination test case path.
     *
     * @return string
     */
    protected function getTestPath()
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
     * @param  string  $path
     */
    protected function handleTestCreation($path): bool
    {
        if (! $this->option('test') && ! $this->option('pest') && ! $this->option('phpunit')) {
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
    protected function testNamespace()
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
    protected function testClassName()
    {
        return Str::of($this->testClassFullyQualifiedName())
            ->afterLast('\\')
            ->append('Test')
            ->value();
    }

    /**
     * Get the class fully qualified name for the test.
     *
     * @return string
     */
    protected function testClassFullyQualifiedName()
    {
        $name = Str::of(Str::lower($this->getNameInput()))->replace('.'.$this->option('extension'), '');

        $namespacedName = Str::of(
            (new Stringable($name))
                ->replace('/', ' ')
                ->explode(' ')
                ->map(fn ($part) => (new Stringable($part))->ucfirst())
                ->implode('\\')
        )
            ->replace(['-', '_'], ' ')
            ->explode(' ')
            ->map(fn ($part) => (new Stringable($part))->ucfirst())
            ->implode('');

        return 'Tests\\Feature\\View\\'.$namespacedName;
    }

    /**
     * Get the test stub file for the generator.
     *
     * @return string
     */
    protected function getTestStub()
    {
        $stubName = 'view.'.($this->usingPest() ? 'pest' : 'test').'.stub';

        return file_exists($customPath = $this->laravel->basePath("stubs/$stubName"))
            ? $customPath
            : __DIR__.'/stubs/'.$stubName;
    }

    /**
     * Get the view name for the test.
     *
     * @return string
     */
    protected function testViewName()
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
    protected function usingPest()
    {
        if ($this->option('phpunit')) {
            return false;
        }

        return $this->option('pest') ||
            (function_exists('\Pest\\version') &&
                file_exists(base_path('tests').'/Pest.php'));
    }

}
