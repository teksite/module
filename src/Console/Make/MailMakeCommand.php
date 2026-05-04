<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\ViewHandlerTrait;

class MailMakeCommand extends GeneratorModuleCommand
{
    use ViewHandlerTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new email cast class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Mailable';


    /**
     * @throws \Exception
     */
    protected function handler(): void
    {
        if ($this->option('markdown') !== false) {
            $this->writeMarkdownTemplate($this->getModuleInput(), 'mail');
        }

        if ($this->option('view') !== false) {
            $this->writeView($this->getModuleInput(), 'mail');
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->option('markdown') !== false) {
            return $this->resolveStubPath('stubs/markdown-mail.stub');
        }

        if ($this->option('view') !== false) {
            return $this->resolveStubPath('stubs/view-mail.stub');
        }

        return $this->resolveStubPath('stubs/mail.stub');
    }

    protected function path(): string
    {
        return 'app/Mail';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        $nameSegment = explode(DIRECTORY_SEPARATOR, $this->getNameInput());
        $subject = array_pop($nameSegment);
        $view = $this->getModuleInput(). "::" .$this->viewPath('mail');
        return [
            '{{subject}}'   => $subject,
            '{{ subject }}' => $subject,
            '{{view}}'      => $view,
            '{{ view }}'    => $view,
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the mailable already exists'],
            ['markdown', 'm', InputOption::VALUE_OPTIONAL, 'Create a new Markdown template for the mailable', false],
            ['view', null, InputOption::VALUE_OPTIONAL, 'Create a new Blade template for the mailable', false],
        ];
    }

}
