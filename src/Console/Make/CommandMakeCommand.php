<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class CommandMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandsTrait;

    protected $signature = 'module:make-command {name} {module}
         {--f|force : Create the class even if the console command already exists }
         {--command : he terminal command that will be used to invoke the class }
         ';

    protected $description = 'Create a new custom command class in the specific module';

    protected $type = 'Command';

    protected function getStub()
    {
        return $this->resolveStubPath('/console.stub');
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

        return $this->setNamespace($module,$name , '\\App\\Console\\Command');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $command = $this->option('command') ?: 'app:'.(new Stringable($name))->classBasename()->kebab()->value();

        return str_replace(['dummy:command', '{{ command }}'], $command, $stub);
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
