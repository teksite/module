<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class SeederMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandTrait;

    protected $signature = 'module:make-seeder {name} {module}
    ';


    protected $description = 'Create a new seeder in the specific module';

    protected $type = 'Seeder';

    protected function getStub()
    {
        return __DIR__.'/../../stubs/seeder.stub';
    }


    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setDefaultPath($module, $name, '/Database/seeder/');

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
        return $this->setDefaultNamespace($module, $name, '\\Database\\Seeders');
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
