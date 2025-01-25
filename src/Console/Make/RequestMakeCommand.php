<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class RequestMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandTrait;

    protected $signature = 'module:make-request {name} {--api} {module}';


    protected $description = 'Create a new request class in the specific module';

    protected $type = 'Request';

    protected function getStub()
    {
        return $this->option('api') ?
            __DIR__ . '/../../stubs/request-api-class.stub' :
            __DIR__ . '/../../stubs/request-class.stub';
    }


    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setDefaultPath($module, $name, '/App/Http/Requests/');

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
        return $this->setDefaultNamespace($module, $name, '\\App\\Http\\Requests');
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
