<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;


class MigrationMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandsTrait;
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:make-migration {name} {module}
        {--create= : The table to be created }
        {--table= : The table to migrate }
        ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file in the specific module';


    public function handle()
    {
        $module = $this->argument('module');

        [$isValid, $suggestedName] = $this->validateModuleName($module);

        if ($isValid) return $this->generateMigration();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return $this->generateMigration();
        }
        $this->error("The module '".$module."' does not exist.");
        return 1;

    }
    protected function generateMigration()
    {
        $module = $this->argument('module');
        $modulePath = Module::modulePath($module ,config('moduleconfigs.module.database.migration_path' , 'Database/Migrations'));

        if (!is_dir($modulePath)){
            File::makeDirectory($modulePath , '0755' , true);
        }
        $relativeBase=str_replace(base_path() , '', $modulePath);
        $options = [
            'name' => $this->argument('name'),
            '--table' => $this->option('table') ,
            '--create' => $this->option('create'),
            '--path' =>$relativeBase,
        ];
        $this->call('make:migration', $options);
    }


    protected function getStub()
    {
       // Leave gere empty. stubs are read from laravel
    }
}
