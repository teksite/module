<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ProviderMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-provider {name} {module}
        {--f|force : Create the class even if the cast already exists }
    ';

    protected $description = 'Create a new provider in the specific module';

    protected $type = 'Provider';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/provider.stub');
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

        return $this->setNamespace($module,$name , '\\App\\Providers');
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
