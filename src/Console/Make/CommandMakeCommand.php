<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\CreatesModuleMatchingTest;

class CommandMakeCommand extends GeneratorModuleCommand
{
    use CreatesModuleMatchingTest;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Artisan command in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Console command';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/console.stub');
    }

    protected function path(): string
    {
        return  'app/Console/Commands';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        $command = $this->option('command') ?: 'app:'.(new Stringable($this->getNameInput()))->classBasename()->kebab()->value();

        return [
            '{{ command }}' => $command,
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
            ['force', 'f', InputOption::VALUE_NONE, "Create the class even if the {$this->type} already exists"],
            ['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that will be used to invoke the class'],
        ];
    }


}
