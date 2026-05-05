<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\ViewHandlerTrait;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class NotificationMakeCommand extends GeneratorModuleCommand
{
    use ViewHandlerTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new notification class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Notification';


    /**
     * @throws \Exception
     */
    protected function handler(): void
    {
        if ($this->option('markdown') !== false) {
            $this->writeMarkdownTemplate($this->getModuleInput(), 'notification');
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
        return $this->option('markdown')
            ? $this->resolveStubPath('stubs/markdown-notification.stub')
            : $this->resolveStubPath('stubs/notification.stub');
    }

    protected function path(): string
    {
        return 'app/Notifications';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        $view = $this->getModuleInput() . "::" . $this->viewPath('notification');

        return [
            '{{ view }}' => $view,
            '{{view}}'   => $view,

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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the notification already exists'],
            ['markdown', 'm', InputOption::VALUE_NONE, 'Create a new Markdown template for the notification'],
        ];
    }


    /**
     * Perform actions after the user was prompted for missing arguments.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $wantsMarkdownView = confirm('Would you like to create a markdown view?');

        if ($wantsMarkdownView) {
            $defaultMarkdownView = (new Collection(explode('/', str_replace('\\', '/', $this->argument('name')))))
                ->map(fn($path) => Str::kebab($path))
                ->prepend('mail')
                ->implode('.');

            $markdownView = text('What should the markdown view be named?', default: $defaultMarkdownView);

            $input->setOption('markdown', $markdownView);
        }
    }
}
