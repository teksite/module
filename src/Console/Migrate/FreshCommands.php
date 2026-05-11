<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;
use Illuminate\Contracts\Events\Dispatcher;

class FreshCommands extends BasicMigrator implements MigrationContract
{

    protected $name = 'module:migrate-fresh';

    protected $description = 'Drop all tables and re-run all migrations for a specific module or all modules';


    protected function needsMigrator(): bool
    {
        return true;
    }

    /**
     * @throws \Throwable
     */
    protected function handler(array $modules): int
    {
        if ($this->isProhibited() || !$this->confirmToProceed()) {
            return CommandAlias::FAILURE;
        }

        $this->resetStats();
        $database = $this->getDatabaseConnection();

        $this->migrator->usingConnection($database, function () use ($database) {
            try {
                $repositoryExists = $this->migrator->repositoryExists();
                $this->successCount++;
            } catch (\Throwable $e) {
                Log::error($e);
                $this->failureCount++;
                $repositoryExists = false;
            }

            if ($repositoryExists) {
                $this->wipeDB($database);
            }
        });

        $this->ensureMigrationTableExists($database);


        $this->migrateModules($database);

        $this->seeding($database);

        $this->showSummary('fresh');
        return $this->failureCount === 0 ? CommandAlias::SUCCESS : CommandAlias::FAILURE;
    }


    /**
     * @param string $database
     * @return void
     */
    function wipeDB(string $database): void
    {
        $this->line("<fg=cyan;options=bold> dropping tables</>");

        try {
            $this->components->task('<fg=gray> └─dropped</>', function () use ($database) {
                return $this->callSilent('db:wipe', array_filter([
                        '--database'   => $database,
                        '--drop-views' => $this->option('drop-views'),
                        '--drop-types' => $this->option('drop-types'),
                        '--force'      => true,
                    ])) === 0;
            });
            $this->successCount++;
        } catch (\Throwable $e) {
            Log::error($e);
            $this->failureCount++;

        }

    }

    /**
     * @param string $database
     * @return void
     */
    public function migrateModules(string $database): void
    {
        $this->newLine();
        try {
            $result = $this->call('module:migrate', array_filter([
                '--module'   => $this->option('module'),
                '--database' => $database,
                '--force'    => true,
                '--step'     => $this->option('step'),
                '--pretend'  => $this->option('pretend'),
                '--realpath' => $this->option('realpath'),
            ]));
            if ($result !== CommandAlias::SUCCESS) {
                throw new \Exception('seeding failed');
            }
            $this->successCount++;

        } catch (\Throwable $e) {
            Log::error($e);
            $this->failureCount++;
        }
    }


    private function ensureMigrationTableExists(?string $database): void
    {
        $this->line("<fg=cyan;options=bold> migrations table</>");

        $this->components->task('<fg=gray> └─creating migration table</>', function () use ($database) {
            $this->usingDatabase($database, function () use ($database) {
                $schema = $this->laravel['db']->connection($database)->getSchemaBuilder();

                if (!$schema->hasTable('migrations')) {
                    $schema->create('migrations', function (Blueprint $table) {
                        $table->increments('id');
                        $table->string('migration');
                        $table->integer('batch');
                    });
                    $this->successCount++;

                }
            });
        });
    }

    /**
     * @param string $database
     * @return void
     */
    public function seeding(string $database): void
    {
        if ($this->option('seed')) {
            try {
                $result = $this->call('module:db-seed', array_filter([
                    '--module'   => $this->option('module'),
                    '--database' => $database,
                    '--force'    => true,
                ]));
                if ($result !== CommandAlias::SUCCESS) {
                    throw new \Exception('seeding failed');
                }
                $this->successCount++;

            } catch (\Throwable $e) {
                Log::error($e);
                $this->failureCount++;

            }
        }
    }

    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to migrate', []],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['pretend', null, InputOption::VALUE_NONE, 'Do not actually run migrations, just show SQL'],
            ['step', null, InputOption::VALUE_NONE, 'Force the migrations to be run so they can be rolled back individually'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
        ];
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn() => $this->components->choice(
                'Which module(s) do you want to fresh migrate?',
                $this->getAllModules(true),
                multiple: true
            ),
        ];
    }
}
