<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class RuleMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-rule {name} {module}
     {--f|force : Create the class even if the resource already exists },
     {--i|implicit : Generate an implicit rule },
    ';


    protected $description = 'Create a new rule in the specific module';

    protected $type = 'Rule';

    protected function getStub()
    {
        return $this->option('implicit')
            ? $this->resolveStubPath('/rule.implicit.stub')
            : $this->resolveStubPath('/rule.stub');
    }


    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setPath($name,'php');
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

        return $this->setNamespace($module,$name , '\\App\\Rules');
    }

    protected function buildClass($name)
    {
        return str_replace(
            '{{ ruleType }}',
            $this->option('implicit') ? 'ImplicitRule' : 'Rule',
            parent::buildClass($name)
        );
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
