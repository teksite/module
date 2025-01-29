<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class NotificationMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-notification {name} {module}
        {--f|force : Create the class even if the cast already exists }
        {--m|markdown= : Create a new Markdown template for the mailable}
    ';

    protected $description = 'Create a new notification in the specific module';

    protected $type = 'Notification';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('markdown')
            ? $this->resolveStubPath('/markdown-notification.stub')
            : $this->resolveStubPath('/notification.stub');
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

        return $this->setNamespace($module,$name , '\\App\\Notifications');
    }

    public function handle(): bool|int|null
    {
        $module = $this->argument('module');
        [$isValid, $suggestedName] = $this->validateModuleName($module);

        if ($isValid) {
          return $this->generateViews();
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
          return $this->generateViews();
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }

    protected function generateViews()
    {
        if ($this->option('markdown')) {
            $this->writeMarkdownTemplate();
        }
        return parent::handle();
    }

    protected function writeMarkdownTemplate()
    {
        $path = $this->viewPath(
            str_replace('.', '/', $this->option('markdown')).'.blade.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $this->files->put($path, file_get_contents(__DIR__.'/../../stubs/markdown.stub'));

        $this->components->info(sprintf('%s [%s] created successfully.', 'Markdown', $path));
    }

    protected function buildClass($name)
    {
        $class = parent::buildClass($name);

        $module = $this->getLowerNameModule();

        if ($this->option('markdown')) {
            $class = str_replace(['DummyView', '{{ view }}'], $module . "::".$this->option('markdown'), $class);
        }

        return $class;
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $wantsMarkdownView = confirm('Would you like to create a markdown view?');

        if ($wantsMarkdownView) {
            $defaultMarkdownView = (new Collection(explode('/', str_replace('\\', '/', $this->argument('name')))))
                ->map(fn ($path) => Str::kebab($path))
                ->prepend('mail')
                ->implode('.');

            $markdownView = text('What should the markdown view be named?', default: $defaultMarkdownView);

            $input->setOption('markdown', $markdownView);
        }
    }
}
