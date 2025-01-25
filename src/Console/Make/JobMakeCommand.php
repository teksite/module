<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class JobMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandTrait ,CreatesMatchingTest;

    protected $signature = 'module:make-job {name} {module}
     {--f|force : Create the class even if the job already exists }
     {--sync : Indicates that job should be synchronous }
    ';

    protected $description = 'Create a new job class in the specific module';

    protected $type = 'Job';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('sync')
            ? __DIR__ . '/../../stubs/job.stub'
            :  __DIR__ . '/../../stubs/job.queued.stub';
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
        return $this->setDefaultPath($module, $name ,'/App/Jobs/');
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
        return $this->setDefaultNamespace($module,$name , '\\App\\Jobs');
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
        $this->error("The module '".$module."' does not exist.");
        return 1;
    }

}
