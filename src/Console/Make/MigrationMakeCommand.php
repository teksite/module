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
    protected function generateMigration(): void
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

    }
}



//namespace Teksite\Module\Console\Make;
//
//use Illuminate\Console\Command;
//use Illuminate\Console\GeneratorCommand;
//use Illuminate\Database\Migrations\MigrationCreator;
//use Illuminate\Support\Facades\Artisan;
//use Illuminate\Support\Facades\File;
//use Illuminate\Support\Str;
//use Symfony\Component\Console\Command\Command as CommandAlias;
//use Teksite\Module\Traits\ModuleCommandTrait;
//use Teksite\Module\Traits\ModuleNameValidator;
//
//class ChannelMakeCommand extends Command
//{
//    use ModuleCommandTrait, ModuleNameValidator;
//
//    /**
//     * The name and signature of the console command.
//     *
//     * @var string
//     */
//protected
//$signature = 'module:make-migration {name} {module}
//                            {--create= : The table to be created}
//                            {--table= : The table to migrate}
//                            {--columns= : Predefined columns for the migration (comma-separated)}';
//
///**
// * The console command description.
// *
// * @var string
// */
//protected
//$description = 'Create a new migration file in a specific module';
//
//protected
//$type = 'Migration';
//
///**
// * Execute the console command.
// *
// * @return int
// */
//public
//function getSchemaParser()
//{
//    return new SchemaParser($this->option('fields'));
//}
//
///**
// * @return mixed
// *
// * @throws \InvalidArgumentException
// */
//protected
//function getTemplateContents()
//{
//    $parser = new NameParser($this->argument('name'));
//
//    if ($parser->isCreate()) {
//        return Stub::create('/migration/create.stub', [
//            'class' => $this->getClass(),
//            'table' => $parser->getTableName(),
//            'fields' => $this->getSchemaParser()->render(),
//        ]);
//    } elseif ($parser->isAdd()) {
//        return Stub::create('/migration/add.stub', [
//            'class' => $this->getClass(),
//            'table' => $parser->getTableName(),
//            'fields_up' => $this->getSchemaParser()->up(),
//            'fields_down' => $this->getSchemaParser()->down(),
//        ]);
//    } elseif ($parser->isDelete()) {
//        return Stub::create('/migration/delete.stub', [
//            'class' => $this->getClass(),
//            'table' => $parser->getTableName(),
//            'fields_down' => $this->getSchemaParser()->up(),
//            'fields_up' => $this->getSchemaParser()->down(),
//        ]);
//    } elseif ($parser->isDrop()) {
//        return Stub::create('/migration/drop.stub', [
//            'class' => $this->getClass(),
//            'table' => $parser->getTableName(),
//            'fields' => $this->getSchemaParser()->render(),
//        ]);
//    }
//
//    return Stub::create('/migration/plain.stub', [
//        'class' => $this->getClass(),
//    ]);
//}
//
///**
// * @return mixed
// */
//protected
//function getDestinationFilePath()
//{
//    $path = $this->laravel['modules']->getModulePath($this->getModuleName());
//
//    $generatorPath = GenerateConfigReader::read('migration');
//
//    return $path . $generatorPath->getPath() . '/' . $this->getFileName() . '.php';
//}
//
///**
// * @return string
// */
//private
//function getFileName()
//{
//    return date('Y_m_d_His_') . $this->getSchemaName();
//}
//
///**
// * @return array|string
// */
//private
//function getSchemaName()
//{
//    return $this->argument('name');
//}
//
///**
// * @return string
// */
//private
//function getClassName()
//{
//    return Str::studly($this->argument('name'));
//}
//
//public
//function getClass()
//{
//    return $this->getClassName();
//}
//
///**
// * Run the command.
// */
//public
//function handle(): int
//{
//
//    $this->components->info('Creating migration...');
//
//    if (parent::handle() === E_ERROR) {
//        return E_ERROR;
//    }
//
//    if (app()->environment() === 'testing') {
//        return 0;
//    }
//
//    return 0;
//}
//
//}
