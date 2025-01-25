<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class CommandMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandTrait;

    protected $signature = 'module:make-command {name} {module}';

    protected $description = 'Create a new custom command class in the specific module';

    protected $type = 'Command';

    protected function getPath($name): string
    {
        $module = $this->argument('module');
        return $this->setDefaultPath($module, $name , '/App/Console/Commands');

    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {

        $module = $this->argument('module');
        return $this->setDefaultNamespace($module,$name , '\\App\\Console\\Commands');
    }

    protected function getStub()
    {
        return __DIR__ . '/../../stubs/command-class.stub';
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
