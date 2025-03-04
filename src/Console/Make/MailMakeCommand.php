<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\select;

class MailMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait, CreatesMatchingTest;

    // protected $name = 'module:make-mail';
    protected $signature = 'module:make-mail {name} {module}
    {--force= : Create a new Blade template for the mailable (true false) }
    {--m|markdown= : Create a new Markdown template for the mailable }
    {--view= : Create a new Blade template for the mailable }
    ';

    protected $description = 'Create a new email in the specific module';

    protected $type = 'Mailable';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if (!!$this->option('markdown') !== false) {
            return $this->resolveStubPath('/markdown-mail.stub');
        }

        if (!!$this->option('view') !== false) {
            return $this->resolveStubPath('/view-mail.stub');
        }

        return $this->resolveStubPath('/mail.stub');

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

        return $this->setNamespace($module,$name , '\\App\\Mail');
    }


    public function handle()
    {
        $module = $this->argument('module');

        [$isValid, $suggestedName] = $this->validateModuleName($module);

        if ($isValid) return $this->generateViews();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return $this->generateViews();
        }
        $this->error("The module '".$module."' does not exist.");
        return 1;
    }

    protected function generateViews()
    {
        if (parent::handle() === false && !!!$this->option('force')) {
            return;
        }

        if (!!$this->option('markdown') !== false) {
           return  $this->writeMarkdownTemplate();
        }

        if (!!$this->option('view') !== false) {
           return  $this->writeView();
        }
    }

    /**
     * Write the Markdown template for the mailable.
     *
     * @return void
     */
    protected function writeMarkdownTemplate()
    {
        $path = $this->viewPath('mail/'.
            str_replace('.', '/', $this->getView()) . '.blade.php'
        );

        if ($this->files->exists($path)) {
            return $this->components->error(sprintf('%s [%s] already exists.', 'Markdown view', $path));
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put($path, file_get_contents(__DIR__ . '/../../stubs/markdown.stub'));

        $this->components->info(sprintf('%s [%s] created successfully.', 'Markdown view', $path));
    }

    /**
     * Write the Blade template for the mailable.
     *
     * @return void
     */
    protected function writeView()
    {

        $path = $this->viewPath(
            str_replace('.', '/', 'mails.'.$this->getView()) . '.blade.php'
        );

        if ($this->files->exists($path)) {
            return $this->components->error(sprintf('%s [%s] already exists.', 'View', $path));
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $stub = str_replace(
            '{{ quote }}',
            Inspiring::quotes()->random(),
            file_get_contents(__DIR__ . '/../../stubs/view.stub')
        );

        $this->files->put($path, $stub);

        $this->components->info(sprintf('%s [%s] created successfully.', 'View', $path));
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $module = $this->getLowerNameModule();

        $class = str_replace(
            '{{ subject }}',
            Str::headline(str_replace($this->getNamespace($name) . '\\', '', $name)),
            parent::buildClass($name)
        );

        if (!!$this->option('markdown') !== false || !!$this->option('view') !== false) {
            $class = str_replace(['DummyView', '{{ view }}'], $module . '::mails.' . $this->getView(), $class);
        }

        if (!!!$this->option('markdown') || !!!$this->option('view')) {
            $class = str_replace(['DummyView', '{{ view }}'], $this->getView(), $class);
        }
        return $class;
    }

    /**
     * Get the view name.
     *
     * @return string
     */
    protected function getView()
    {
        $module = $this->getLowerNameModule();
        $view = $this->option('markdown') ?: $this->option('view');

        if (!$view) {
            $name = str_replace('\\', '/', $this->argument('name'));

            $view = $module . '::mail.' . (new Collection(explode('/', $name)))
                    ->map(fn($part) => Str::kebab($part))
                    ->implode('.');
        }
        return $view;


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

        $type = select('Would you like to create a view?', [
            'markdown' => 'Markdown View',
            'view' => 'Empty View',
            'none' => 'No View',
        ]);

        if ($type !== 'none') {
            $input->setOption($type, null);
        }
    }
}
