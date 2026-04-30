<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\CreatesModuleMatchingTest;
use function Laravel\Prompts\confirm;


class ExceptionMakeCommand extends GeneratorModuleCommand
{
    use CreatesModuleMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-exception';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new custom exception class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Exception';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->option('render')) {
            return $this->option('report')
            ? $this->resolveStubPath('stubs/exception-render-report.stub')
            : $this->resolveStubPath('stubs/exception-render.stub');
        }

        return $this->option('report')
            ? $this->resolveStubPath('stubs/exception-report.stub')
            : $this->resolveStubPath('stubs/exception.stub');
    }

    protected function path(): string
    {
        return 'app/Exceptions';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the exception already exists'],
            ['render', null, InputOption::VALUE_NONE, 'Create the exception with an empty render method'],
            ['report', null, InputOption::VALUE_NONE, 'Create the exception with an empty report method'],
        ];
    }


    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $input->setOption('report', confirm('Should the exception have a report method?', default: false));
        $input->setOption('render', confirm('Should the exception have a render method?', default: false));
    }


}
