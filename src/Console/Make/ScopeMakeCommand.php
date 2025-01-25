<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ScopeMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandTrait;

    protected $signature = 'module:make-scope {name} {module}
     {--f|force : Create the class even if the resource already exists },
    ';


    protected $description = 'Create a new scope in the specific module';

    protected $type = 'Scope';

    protected function getStub()
    {
        return __DIR__.'/../../stubs/scope.stub';
    }


    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setDefaultPath($module, $name, '/App/Models/Scopes/');

    }

    /**
     * تنظیمات نام‌گذاری کلاس.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {
         $module = $this->argument('module');
        return $this->setDefaultNamespace($module, $name, '\\App\\Models\\Scopes');
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
