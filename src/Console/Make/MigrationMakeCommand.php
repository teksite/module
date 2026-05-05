<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;


class MigrationMakeCommand extends GeneratorModuleCommand
{
    protected string $generatorType = 'file';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Migration';


    public function handle(): void
    {

        $name = $this->getNameInput();
        if ($this->isReservedName($name)) {
            $this->components->error('The name "' . $name . '" is reserved by PHP.');
            return;
        }
        $module = $this->getModuleInput();
        if (!$this->isModuleExist($module)) {
            $this->components->error('The module "' . $module . ' is not registered or does not exist.');
            $this->components->error("use steward work instead of module name to make {$this->type} in steward");
            return;
        }

        $this->generateMigration($name, $module);


    }

    protected function generateMigration(string $name, string $module): void
    {


        $path = $module === 'Steward'
            ? steward_path(config('modules.steward.migration_path', 'database/migrations') , false)
            : module_path($module, config('modules.module.migration_path', 'database/migrations') , false);

        $this->ensureDirectoryExistence($path);

        $options = [
            'name'     => $this->argument('name'),
            '--table'  => $this->option('table'),
            '--create' => $this->option('create'),
            '--path'   => $path,
        ];
        $this->call('make:migration', $options);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */

    protected function getStub(): string
    {
        return '';
    }

    protected function path(): string
    {
        return 'database/migrations';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [];

    }

    protected function getOptions(): array
    {
        return [
            ['create', 'f', InputOption::VALUE_OPTIONAL, "The table to be created"],
            ['table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate'],
        ];
    }
}
