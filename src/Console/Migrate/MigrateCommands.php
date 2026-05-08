<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class MigrateCommands extends BasicMigrator
{

    protected $name = 'module:migrate';

    protected $description = 'Run the database migrations for a specific module or all modules';

    protected array $migrationNotes = [];

    public function __construct(private readonly Migrator $migrator)
    {
        parent::__construct();
    }

    /**
     * @throws \Throwable
     */
    protected function handler(array $modules): int
    {
        $this->resetStats();
        $this->components->info('migrating module(s)...');

        $options = array_filter([
            'pretend' => $this->option('pretend'),
            'step'    => $this->option('step'),
            'force'   => $this->option('force'),
        ]);

        foreach ($modules as $module) {
            $this->migrateModule($module, $options);
        }

        $this->showSummary('migration');
        return $this->failureCount === 0 ? Command::SUCCESS : Command::FAILURE;

    }


    /**
     * Migrate a single module
     */
    private function migrateModule(string $module, array $options): void
    {
        $migrationPath = $this->getMigrationPath($module);

        if (!$this->isValidMigrationPath($migrationPath)) {
            $this->warn("No migration path found for module: {$module}");
            $this->failureCount++;
            $this->failedItems[] = $module;
            return;
        }

        try {
            $this->components->twoColumnDetail("<fg=cyan;options=bold>{$module}</>", "$migrationPath");

            $this->migrationNotes = [];

            $beforeMigrations = $this->getRanMigrations();

            $time = $this->measureExecutionTime(function () use ($module, $migrationPath, $options) {
                $this->executeMigration($module, $migrationPath, $options);
            });

            $afterMigrations = $this->getRanMigrations();
            $newMigrations = array_diff($afterMigrations, $beforeMigrations);


            if (!empty($newMigrations)) {
                foreach ($newMigrations as $migration) {
                    $this->components->twoColumnDetail("  └─ " . $this->formatMigrationName($migration), "<fg=green>✓ migrated</>");
                }
            } else {
                $this->components->twoColumnDetail("  └─ No new migrations", "<fg=yellow>⏭ skipped</>");
            }

            $this->components->twoColumnDetail("<fg=green>✓ {$module} completed</>", "<fg=green>{$time}ms</>");


            $this->successCount++;
            $this->successItems[] = $module;

        } catch (\Throwable $e) {
            $this->components->error("✗ {$module} failed: " . $e->getMessage());
            $this->failureCount++;
            $this->failedItems[] = $module;

            if (!$this->option('force')) {
                throw $e;
            }
        }
    }

    /**
     * Get list of already ran migrations
     */
    private function getRanMigrations(): array
    {
        try {
            $repository = $this->migrator->getRepository();

            if ($repository->repositoryExists()) {
                return $repository->getRan();
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * Format migration name for display
     */
    private function formatMigrationName(string $migration): string
    {
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);

        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }

    /**
     * Get the migration path for a module
     */
    private function getMigrationPath(string $module): string
    {
        if ($module === 'steward') {
            return steward_path(config('modules.steward.migration_path', 'database/migrations'), false);
        }
        return module_path($module, config('modules.module.migration_path', 'database/migrations'), false);
    }

    /**
     * Validate if migration path exists and is readable
     */
    private function isValidMigrationPath(string $path): bool
    {
        return is_dir($path) && is_readable($path);
    }


    /**
     * Parse migrated files from output
     */
    private function parseMigratedFiles(string $output): array
    {
        $files = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match('/Migrated:\s+(.+?\.php)/', $line, $matches)) {
                $files[] = basename($matches[1]);
            }
        }

        return $files;
    }

    /**
     * Execute migration for a module
     */
    private function executeMigration(string $module, string $migrationPath, array $options): void
    {
        $database = $this->getDatabaseConnection();

        $this->usingDatabase($database, function () use ($migrationPath, $options) {

            $this->migrator->path($migrationPath);
            $this->migrator->run([$migrationPath], $options);

        });
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
