<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\suggest;

class ListenerMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait, CreatesMatchingTest;

    protected $signature = 'module:make-listener {name} {module}
        {--e|event= : The event class being listened  for}
        {--f|force : Create the class even if the listener already exists }
        {--queued : Indicates the event listener should be queued }
    ';

    protected $description = 'Create a new listener in the specific module';

    protected $type = 'Listener';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->option('queued')) {
            return $this->option('event')
                ? $this->resolveStubPath('/listener.typed.queued.stub')
                : $this->resolveStubPath('/listener.queued.stub');
        }

        return $this->option('event')
            ? $this->resolveStubPath('/listener.typed.stub')
            : $this->resolveStubPath('/listener.stub');

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
        return $this->setPath($name, 'php');
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
        return $this->setNamespace($module, $name, '\\App\\Listeners');
    }

    protected function buildClass($name)
    {
        $module=$this->argument('module');
        $event = $this->option('event') ?? '';
        if (!Str::startsWith($event, [
            $this->moduleNamespace(),
            $this->moduleNamespace($module),
            $this->moduleNamespace($module ,'App\\Events\\'),
        ])) {
            $event = $this->moduleNamespace($module , 'App\\Events\\')  . str_replace('/', '\\', $event);
        }

        $stub = str_replace(
            ['DummyEvent', '{{ event }}'], class_basename($event), parent::buildClass($name)
        );

        return str_replace(
            ['DummyFullEvent', '{{ eventNamespace }}'], trim($event, '\\'), $stub
        );
    }
    public function handle(): bool|int|null
    {
        $module = $this->argument('module');
        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) return parent::handle();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return parent::handle();
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
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
