<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class LogicMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandTrait ,CreatesMatchingTest;

    protected $signature = 'lareon:make-logic {name} {module}';


    protected $description = 'Create a new logic class (repository and logic layer) in the specific module';

    protected $type = 'Logic';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/logic-class.stub';
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
        return $this->setDefaultPath($module, $name ,'/App/Logics/');
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
        return $this->setDefaultNamespace($module,$name , '\\App\\Logic');
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
