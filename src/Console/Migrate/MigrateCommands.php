<?php

namespace Teksite\Module\Console\Migrate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class MigrateCommands extends BasicMigrator
{

    protected $name = 'module:migrate';

    protected $description = 'Run the database migrations for a specific module or all modules';

    protected array $migrationNotes = [];


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

        $this->components->info('Migrating module(s)...');

        $options = array_filter([
            'pretend' => $this->option('pretend'),
            'step'    => $this->option('step'),
            'force'   => $this->option('force'),
        ]);

        foreach ($modules as $module) {
            $this->processModuleOperation($module, 'migration', $options, function ($module, $path, $opts) {
                $this->executeMigration($path, $opts);
            });
        }

        $this->showSummary('migration');
        return $this->failureCount === 0 ? Command::SUCCESS : Command::FAILURE;

    }

    private function executeMigration(string $migrationPath, array $options): void
    {
        $beforeMigrations = $this->getRanMigrationsForPath($migrationPath);

        $database = $this->getDatabaseConnection();

        $this->usingDatabase($database, function () use ($migrationPath, $options) {
            $this->migrator->path($migrationPath);
            $this->migrator->run([$migrationPath], $options);
        });

        $afterMigrations = $this->getRanMigrationsForPath($migrationPath);
        $newMigrations = array_diff($afterMigrations, $beforeMigrations);

        if (!empty($newMigrations)) {
            foreach ($newMigrations as $migration) {
                $this->components->twoColumnDetail("<fg=gray> └─" . $migration."</>", "<fg=green>✓ migrated</>");
            }
        } else {
            $this->components->twoColumnDetail("  └─ No new migrations", "<fg=yellow>⏭ skipped</>");
        }
    }


    /**
     * Show migration summary
     */
    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to seed (comma-separated or multiple values)', []],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database connection to use for migrations'],
            ['force', null, InputOption::VALUE_NONE, 'Force migration operation even in production'],
            ['pretend', null, InputOption::VALUE_NONE, 'Do not actually run migrations, just show SQL'],
            ['step', null, InputOption::VALUE_NONE, 'Force migrations to be run so they can be rolled back individually'],
            ['realpath', null, InputOption::VALUE_OPTIONAL, 'Absolute path to migrations directory'],
        ];
    }


    /**
     * Prompt for missing input
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn() => $this->components->choice(
                'Which module(s) do you want to migrate?',
                $this->getAllModules(true),
                multiple: true
            ),
        ];
    }
}
