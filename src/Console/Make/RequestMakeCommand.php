<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class RequestMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-request {name} {module}
         {--f|force : Create the class even if the cast already exists }
         {--api : return json } ';


    protected $description = 'Create a new request class in the specific module';

    protected $type = 'Request';

    protected function getStub()
    {
        return $this->option('api')
            ? $this->resolveStubPath('/request-api.stub')
            : $this->resolveStubPath('/request.stub');
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

        return $this->setNamespace($module,$name , '\\App\\Http\\Requests');

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
