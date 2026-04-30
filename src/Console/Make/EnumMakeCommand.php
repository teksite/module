<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Console\GeneratorModuleCommand;
use function Laravel\Prompts\select;

class EnumMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-enum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new enum in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Enum';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->option('string') || $this->option('int')) {
            return $this->resolveStubPath('stubs/enum.backed.stub');
        }
        return $this->resolveStubPath('stubs/enum.stub');
    }


    protected function path(): string
    {
        return match (true) {
            is_dir(module_path($this->getModuleInput(), 'app/Enums'))        => 'app/Enums',
            is_dir(module_path($this->getModuleInput(), 'app/Enumerations')) => 'app/Enumerations',
            default                                                          => 'app/Enums',
        };
    }

    protected function replacements(): array
    {
        $type = ($this->option('string') || $this->option('int'))
            ? $this->option('string') ? 'string' : 'int'
            : '';


        return ['{{ type }}' => $type];
    }


    protected function getOptions(): array
    {
        return [
            ['string', 's', InputOption::VALUE_NONE, 'Generate a string backed enum.'],
            ['int', 'i', InputOption::VALUE_NONE, 'Generate an integer backed enum.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the enum even if the enum already exists'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
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

        $type = select('Which type of enum would you like?', [
            'pure'   => 'Pure enum',
            'string' => 'Backed enum (String)',
            'int'    => 'Backed enum (Integer)',
        ]);

        if ($type !== 'pure') {
            $input->setOption($type, true);
        }
    }


}
