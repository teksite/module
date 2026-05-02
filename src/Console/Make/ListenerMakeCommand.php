<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Console\GeneratorModuleCommand;
use function Laravel\Prompts\suggest;

class ListenerMakeCommand extends GeneratorModuleCommand implements PromptsForMissingInput
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new listener class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Listener';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->option('queued')) {
            return $this->option('event')
                ? $this->resolveStubPath('stubs/listener.typed.queued.stub')
                : $this->resolveStubPath('/stubs/listener.queued.stub');
        }

        return $this->option('event')
            ? $this->resolveStubPath('stubs/listener.typed.stub')
            : $this->resolveStubPath('stubs/listener.stub');

    }

    protected function path(): string
    {
        return 'app/Listeners';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [
            '{{ event }}' =>$this->option('event'),
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
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for'],
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
            ['force', 'f', InputOption::VALUE_NONE, "Create the class or file even if the {$this->type} already exists"],
        ];
    }


    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $event = suggest(
            'What event should be listened for? (Optional)',
            $this->possibleEvents(),
        );

        if ($event) {
            $input->setOption('event', $event);
        }
    }
}
