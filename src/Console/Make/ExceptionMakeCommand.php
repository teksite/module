<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ExceptionMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandTrait;

    protected $signature = 'module:make-exception {name} {module}
        {--render : Create the exception with an empty render method}
        {--report : Create the exception with an empty report method}
    ';

    protected $description = 'Create a new exception in the specific module';

    protected $type = 'Exception';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('render')) {
            return $this->option('report')
                ? __DIR__ . '/../../stubs/exception-render-report.stub'
                :__DIR__ . '/../../stubs/exception-render.stub';
        }

        return $this->option('report')
            ?__DIR__ . '/../../stubs/exception-report.stub'
            :__DIR__ . '/../../stubs/exception.stub';
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
        return $this->setDefaultPath($module, $name ,'/App/Exceptions/');
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
        return $this->setDefaultNamespace($module,$name , '\\App\\Exceptions');
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
