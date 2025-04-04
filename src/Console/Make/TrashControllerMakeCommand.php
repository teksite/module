<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class TrashControllerMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-trash-controller {name} {module}
    ';

    protected $description = 'Create a new Trash controller in the specific module';

    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/trash-resource-controller.stub');
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

        return $this->setNamespace($module,$name , 'App\\Http\\Controllers');
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
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name){
    $defController= module_namespace($this->argument('module') ,'App\\Http\\Controllers\\Controller'); ;
        return str_replace(
            ['{{ defaultController }}'],
            $defController,
            parent::buildClass($name)
        );
    }

}
