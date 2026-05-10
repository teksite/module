<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

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
        // Todo add migrate

        $this->resetStats();
        $this->components->info('Fresh migrating module(s)...');

        if ($this->option('drop-views')) {
            $this->dropAllViews();
        }

        if ($this->option('drop-types')) {
            $this->dropAllTypes();
        }

        $options = array_filter([
            'pretend' => $this->option('pretend'),
            'force'   => $this->option('force'),
        ]);

        foreach ($modules as $module) {
            $this->processModuleOperation($module, 'fresh', $options, function ($module, $path, $opts) {
                $this->executeFresh($module, $path, $opts);
            });
        }

        if ($this->option('seed')) {
            $this->call('module:db-seed', [
                '--module' => $modules,
                '--force' => $this->option('force'),
            ]);
        }

        $this->showSummary('fresh migration');
        return $this->failureCount === 0 ? CommandAlias::SUCCESS : CommandAlias::FAILURE;
    }

    private function executeFresh(string $module, string $migrationPath, array $options): void
    {
        $database = $this->getDatabaseConnection();
        $tablesDropped = false;

        $this->usingDatabase($database, function () use ($module, $migrationPath, $options, &$tablesDropped) {
            // Get all tables for this module's migrations
            $tables = $this->getModuleTables($module);

            if (!empty($tables)) {
                $this->dropTables($tables);
                $tablesDropped = true;
            }

            $this->ensureMigrationRepositoryExists();

            // Clear migration repository for this path
            $this->clearMigrationRepository($migrationPath);
        });

        if ($tablesDropped) {
            $this->components->twoColumnDetail("  └─ Tables dropped", "<fg=green>✓ cleaned</>");
        }

        // Now run migrations fresh
        $this->usingDatabase($database, function () use ($migrationPath, $options) {
            $this->migrator->path($migrationPath);
            $this->migrator->run([$migrationPath], $options);
        });

        $newMigrations = $this->getRanMigrationsForPath($migrationPath);
        foreach ($newMigrations as $migration) {
            $this->components->twoColumnDetail("  └─ " . $this->formatMigrationName($migration), "<fg=green>✓ migrated</>");
        }
    }

    private function getModuleTables(string $module): array
    {
        $connection = $this->getDatabaseConnection();
        $schema = Schema::connection($connection);

        $allTables = $schema->getTables();
        $moduleTables = [];

        // Get prefix if configured
        $prefix = config("{$module}.table_prefix", '');

        foreach ($allTables as $table) {
            $tableName = $table['name'] ?? $table;
            // If module has prefix, filter tables that start with prefix
            if ($prefix && str_starts_with($tableName, $prefix)) {
                $moduleTables[] = $tableName;
            } elseif (!$prefix && $this->isTableFromModule($module, $tableName)) {
                $moduleTables[] = $tableName;
            }
        }

        return $moduleTables;
    }

    private function isTableFromModule(string $module, string $tableName): bool
    {
        // You can implement custom logic here to determine if a table belongs to a module
        // For example, check against migration files or naming conventions
        $migrationPath = $this->getMigrationPath($module);
        $migrationFiles = $this->migrator->getMigrationFiles($migrationPath);

        foreach ($migrationFiles as $migration) {
            if (str_contains($migration, $tableName)) {
                return true;
            }
        }

        return false;
    }

    private function dropTables(array $tables): void
    {
        $connection = $this->getDatabaseConnection();
        $schema = Schema::connection($connection);

        foreach ($tables as $table) {
            if ($schema->hasTable($table)) {
                $schema->drop($table);
                $this->components->twoColumnDetail("    └─ Dropped table: {$table}", "<fg=red>✓</>");
            }
        }
    }

    /**
     * Ensure migration repository exists (create if not exists)
     */
    private function ensureMigrationRepositoryExists(): void
    {
        $repository = $this->migrator->getRepository();

        if (!$repository->repositoryExists()) {
            $this->components->twoColumnDetail("  └─ Creating migration repository", "<fg=yellow>creating...</>");
            $repository->createRepository();
            $this->components->twoColumnDetail("  └─ Migration repository created", "<fg=green>✓</>");
        }
    }


    private function clearMigrationRepository(string $migrationPath): void
    {
        $migrations = $this->getRanMigrationsForPath($migrationPath);

        if (empty($migrations)) {
            return;
        }

        $repository = $this->migrator->getRepository();
        foreach ($migrations as $migration) {
            $repository->delete($repository->find($migration));
        }
    }

    private function dropAllViews(): void
    {
        $connection = $this->getDatabaseConnection();
        $schema = Schema::connection($connection);

        $views = $schema->getViews();
        foreach ($views as $view) {
            $schema->dropView($view['name'] ?? $view);
        }

        $this->components->info('Dropped all views.');
    }

    private function dropAllTypes(): void
    {
        // PostgreSQL specific
        if ($this->getDatabaseConnection() === 'pgsql') {
            $connection = $this->getDatabaseConnection();
            $types = \DB::connection($connection)->select('SELECT typname FROM pg_type WHERE typtype = \'e\'');
            foreach ($types as $type) {
                \DB::connection($connection)->statement("DROP TYPE IF EXISTS {$type->typname} CASCADE");
            }

            $this->components->info('Dropped all custom types.');
        }
    }

    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to fresh migrate (comma-separated or multiple values)', []],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database connection to use for migrations'],
            ['force', null, InputOption::VALUE_NONE, 'Force fresh migration operation even in production'],
            ['pretend', null, InputOption::VALUE_NONE, 'Do not actually run migrations, just show SQL'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all views when refreshing database'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all custom types when refreshing database (PostgreSQL only)'],
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
