<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class ResetCommands extends BasicMigrator
{

    protected $name = 'module:migrate-reset';

    protected $description = 'Rollback all database migrations for a specific module or all modules';


    protected function needsMigrator(): bool
    {
        return true;
    }

    /**
     * @throws \Throwable
     */
    protected function handler(array $modules): int
    {
        $this->resetStats();
        $this->components->info('Rolling back module migrations...');

        $options = array_filter([
            'pretend' => $this->option('pretend'),
            'step'    => $this->option('step'),
            'force'   => $this->option('force'),
        ]);

        foreach ($modules as $module) {
            $this->processModuleOperation($module, 'reset', $options, function ($module, $path, $opts) {
                $this->executeReset($path, $opts);
            });
        }

        $this->showSummary('reset');
        return $this->failureCount === 0 ? CommandAlias::SUCCESS : CommandAlias::FAILURE;
    }

    private function executeReset(string $migrationPath, array $options): void
    {
        $beforeMigrations = $this->getRanMigrationsForPath($migrationPath);

        if (empty($beforeMigrations)) {
            $this->components->twoColumnDetail("  └─ No migrations to rollback", "<fg=yellow>⏭ skipped</>");
            return;
        }

        $database = $this->getDatabaseConnection();

        $this->usingDatabase($database, function () use ($database, $migrationPath, $options) {
            $this->migrator->setConnection($database);
            $this->migrator->path($migrationPath);

            $this->migrator->reset([$migrationPath], $options);
        });

        $afterMigrations = $this->getRanMigrationsForPath($migrationPath);
        $rolledBackMigrations = array_diff($beforeMigrations, $afterMigrations);

        foreach ($rolledBackMigrations as $migration) {
            $this->components->twoColumnDetail("  └─ " . $this->formatMigrationName($migration), "<fg=yellow>✓ rolled back</>");
        }
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to reset (comma-separated or multiple values)', []],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database connection to use for migrations'],
            ['force', null, InputOption::VALUE_NONE, 'Force reset operation even in production'],
            ['pretend', null, InputOption::VALUE_NONE, 'Do not actually run migrations, just show SQL'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'Number of migrations to rollback', 0],
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
