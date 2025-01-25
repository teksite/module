<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\select;

class TraitMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandTrait;

    protected $signature = 'module:make-trait {name} {module}
         {--f|force : Create the test even if the test already exists }
    ';


    protected $description = 'Create a new trait in the specific module';

    protected $type = 'Trait';

    protected function getStub()
    {
        return __DIR__ . '/../../stubs/trait.stub';
    }


    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setDefaultPath($module, $name, '/App/Traits');

    }


    protected function qualifyClass($name)
    {
        $module = $this->argument('module');

        return $this->setDefaultNamespace($module, $name, '\\App\\Traits');

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

}
