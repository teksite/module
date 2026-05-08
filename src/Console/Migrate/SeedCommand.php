<?php

namespace Teksite\Module\Console\Migrate;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class SeedCommand extends BasicMigrator implements MigrationContract
{

    protected $name = 'db:seed';


    protected $description = 'Seed the database with records in modules or steward';

    protected $type = 'Seed';

    public function runTheCommand()
    {
        $this->seeding();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY, 'desired modules , if not set mean all modules'],
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
