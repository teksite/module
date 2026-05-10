<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Console\Migrate\traits\TrackOperationStatusTrait;

class RefreshCommands extends BasicMigrator
{

    protected $name = 'module:migrate-refresh';

    protected $description = 'Reset and re-run all migrations for a specific module or all modules';

    protected function handler(array $modules): int
    {
        $this->resetStats();
        $this->components->info('Refreshing module migrations...');

        $step = $this->option('step');

        try {
            $resetResult = $this->call('module:migrate-reset', [
                '--module'   => $modules,
                '--database' => $this->option('database'),
                '--force'    => $this->option('force'),
                '--pretend'  => $this->option('pretend'),
                '--step'     => $step,
            ]);

            if ($resetResult !== CommandAlias::SUCCESS) {
                throw new \Exception('reset failed');
            }

            $migrateResult = $this->call('module:migrate', [
                '--module'   => $modules,
                '--database' => $this->option('database'),
                '--force'    => $this->option('force'),
                '--pretend'  => $this->option('pretend'),
                '--step'     => $step,
            ]);

            if ($migrateResult !== CommandAlias::SUCCESS) {
                throw new \Exception('Migration failed');
            }
            $this->successCount++;


        } catch (\Throwable $e) {
            $this->failureCount++;
            if (!$this->option('force')) {
                $this->showSummary('refresh');
                return CommandAlias::FAILURE;
            }

        }

        if ($this->option('seed')) {
            try {
                $this->call('module:db-seed', [
                    '--module' => $modules,
                    '--force'  => $this->option('force'),
                ]);
            } catch (\Exception $e) {
                $this->components->error("✗ seeding failed: " . $e->getMessage());


                if (!$this->option('force')) {
                    $this->showSummary('refresh');
                    return CommandAlias::FAILURE;
                }
            }
        }


        return $this->failureCount === 0 ? CommandAlias::SUCCESS : CommandAlias::FAILURE;
    }

    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to refresh (comma-separated or multiple values)', []],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database connection to use for migrations'],
            ['force', null, InputOption::VALUE_NONE, 'Force refresh operation even in production'],
            ['pretend', null, InputOption::VALUE_NONE, 'Do not actually run migrations, just show SQL'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'Number of migrations to rollback before refreshing', 0],
        ];
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn() => $this->components->choice(
                'Which module(s) do you want to refresh?',
                $this->getAllModules(true),
                multiple: true
            ),
        ];
    }
}
