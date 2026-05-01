<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;
use Teksite\Module\Console\Make\traits\CreatesModuleMatchingTest;

class JobMiddlewareMakeCommand extends GeneratorModuleCommand
{
    use CreatesModuleMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-job-middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new job middleware class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Middleware';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/job.middleware.stub');
    }

    protected function path(): string
    {
        return 'app/Jobs/Middleware';
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the job middleware already exists'],
        ];
    }


}
