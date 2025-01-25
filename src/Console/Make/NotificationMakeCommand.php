<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class NotificationMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandTrait;

    protected $signature = 'module:make-notification {name} {module}
        {--m|markdown= : Create a new Markdown template for the mailable}
    ';

    protected $description = 'Create a new notification class in the specific module';

    protected $type = 'Notification';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('markdown')
            ? __DIR__ . '/../../stubs/markdown-notification.stub'
            : __DIR__ . '/../../stubs/notification.stub';
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
        return $this->setDefaultPath($module, $name, '/App/Notifications/');
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
        return $this->setDefaultNamespace($module, $name, '\\App\\Notifications');
    }

    public function handle(): bool|int|null
    {
        $module = $this->argument('module');
        [$isValid, $suggestedName] = $this->validateModuleName($module);


        if ($isValid) {
            if ($this->option('markdown')) {
                $this->writeMarkdownTemplate();
            }
            return parent::handle();
        }

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            if ($this->option('markdown')) {
                $this->writeMarkdownTemplate();
            }
            return parent::handle();
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
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
        $module = $this->getLowerNameModule();

        $class = parent::buildClass($name);

        if ($this->option('markdown')) {
            $class = str_replace(['DummyView', '{{ view }}'], $module . "::".$this->option('markdown'), $class);
        }

        return $class;
    }
}
