<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class ResourceMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-resource {name} {module}
     {--f|force : Create the class even if the resource already exists },
     {--c|collection : Create a resource collection },
    ';


    protected $description = 'Create a new resource in the specific module';

    protected $type = 'Resource';

    protected function getStub()
    {
        return $this->collection()
            ? $this->resolveStubPath('/resource-collection.stub')
            : $this->resolveStubPath('/resource.stub');
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

        return $this->setNamespace($module,$name , '\\App\\Http\\Resources');
    }

    public function handle(): bool|int|null
    {
        $module = $this->argument('module');

        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($this->collection()) {
            $this->type = 'Resource collection';
        }
        if ($isValid) return parent::handle();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return parent::handle();
        }
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }

    protected function collection()
    {
        return $this->option('collection') ||
            str_ends_with($this->argument('name'), 'Collection');
    }


}
