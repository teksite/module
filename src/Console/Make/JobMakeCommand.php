<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\CreatesModuleMatchingTest;

class JobMakeCommand extends GeneratorModuleCommand
{
    use CreatesModuleMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new job class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Job';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->option('batched')) {
            return $this->resolveStubPath('stubs/job.batched.queued.stub');
        }

        return $this->option('sync')
            ? $this->resolveStubPath('stubs/job.stub')
            : $this->resolveStubPath('stubs/job.queued.stub');
    }

    protected function path(): string
    {
        return 'app/Jobs';
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
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the job already exists'],
            ['sync', null, InputOption::VALUE_NONE, 'Indicates that the job should be synchronous'],
            ['batched', null, InputOption::VALUE_NONE, 'Indicates that the job should be batchable'],
        ];
    }


}
