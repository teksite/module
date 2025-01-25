<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Console\Migrations\BaseCommand;

use Illuminate\Support\Facades\File;


class MigrationMakeCommand extends GeneratorCommand
{
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
        $modulePath = config('lareon.module.path') . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . config('lareon.module.database.migration_path');
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
        // TODO: Implement getStub() method.
    }
}
