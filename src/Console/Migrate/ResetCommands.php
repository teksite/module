<?php

namespace Teksite\Module\Console\Migrate;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class ResetCommands extends BasicMigrator
{

    protected $signature = 'module:migrate-reset {module?}
        ';

    protected $description = 'Rollback all database migrations for a specific module or all modules';



    protected function handler(array $modules): int
    {
        // TODO: Implement handler() method.
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'],
        ];
    }
    /**
     * Prompt for missing input
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn() => $this->components->choice(
                'Which module(s) do you want to reset?',
                $this->getAllModules(true),
                multiple: true
            ),
        ];
    }

}
