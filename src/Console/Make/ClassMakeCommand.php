<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ClassMakeCommand extends GeneratorCommand
{
    use ModuleCommandsTrait, ModuleNameValidator;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-class {name} {module}
        {--f|force : Create the class even if the cast already exists }
        {--i|invokable : Generate a single method, invokable class }
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class in a specific module';

    protected $type = 'Class';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub()
    {
        return $this->option('invokable')
            ? $this->resolveStubPath('/class.invokable.stub')
            : $this->resolveStubPath('/class.stub');
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
        return $this->setPath($name,'php');
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

        return $this->setNamespace($module,$name , '\\App');
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
