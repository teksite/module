<?php

namespace Teksite\Module\Console\Migrate;

use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;

class RollbackCommands extends BasicMigrator
{
    protected $name = 'module:migrate-rollback';

    protected $description = 'Rollback the last batch of database migrations for a specific module or all modules.';

    protected function needsMigrator(): bool
    {
        return true;
    }

    /**
     * @throws \Throwable
     */
    protected function handler(array $modules,): int
    {
        $this->resetStats();

        $step = max(1, (int)$this->option('step'));

        $this->components->info("Rolling back the last {$step} migration batch(s)...");


        if ($this->option('module')) {
            $this->rollbackModules($modules, $step);
        } else {
            $this->rollbackAllModules($step);
        }

        $this->showSummary('rollback');

        return $this->failureCount === 0
            ? CommandAlias::SUCCESS
            : CommandAlias::FAILURE;
    }

    /**
     * Rollback migrations for specific modules.
     *
     * @param array<int, string> $modules
     *
     * @throws \Throwable
     */
    private function rollbackModules(array $modules, int $step,): void
    {
        foreach (array_reverse($modules) as $module) {
            $this->rollbackModule($module, $step);
        }
    }

    /**
     * Rollback migrations for a single module.
     *
     * @throws \Throwable
     */
    private function rollbackModule(string $module, int $step,): void
    {
        $migrationPath = $this->getMigrationPath($module);

        if (!$this->isValidMigrationPath($migrationPath)) {
            $this->components->twoColumnDetail($module, '<fg=yellow>No migration path found</>');

            $this->addFailureItem($module);

            return;
        }

        $ranMigrations = $this->getRanMigrationsForPath($migrationPath);

        if ($ranMigrations === []) {
            $this->components->twoColumnDetail($module, '<fg=yellow>No executed migrations found</>');

            $this->addSuccessItem($module);

            return;
        }

        $this->components->twoColumnDetail(
            "<fg=cyan;options=bold>{$module}</>",
            $migrationPath,
        );

        try {
            $this->rollback([$migrationPath], $step);

            $this->addSuccessItem($module);

        } catch (\Throwable $e) {
            $this->addFailureItem($module);

            $this->components->error("✗ {$module} failed: {$e->getMessage()}");

            if (!$this->option('force')) throw $e;

        }
    }

    /**
     * Rollback migrations from all enabled modules.
     *
     * This uses Laravel's public Migrator::rollback() API.
     *
     * @throws \Throwable
     */
    private function rollbackAllModules(int $step,): void
    {
        $modules = $this->getEnabledModules();

        $paths = [];

        foreach ($modules as $module) {
            $path = $this->getMigrationPath($module);

            if (!$this->isValidMigrationPath($path)) continue;


            $paths[] = $path;
        }

        if ($paths === []) {
            $this->components->warn('No valid migration paths found.');
            return;
        }

        try {
            $this->rollback($paths, $step);

            $this->addSuccessItem('all modules');

        } catch (\Throwable $e) {
            $this->addFailureItem('all modules');

            $this->components->error(
                "✗ Rollback failed: {$e->getMessage()}",
            );

            if (!$this->option('force')) throw $e;

        }
    }

    /**
     * Execute Laravel's public Migrator::rollback() method.
     *
     * This intentionally does NOT call:
     *
     *     Migrator::rollbackMigrations()
     *
     * because that is an internal implementation detail.
     *
     * @param array<int, string> $paths
     *
     * @throws \Throwable
     */
    private function rollback(array $paths, int $step,): void
    {
        $database = $this->getDatabaseConnection();

        $this->usingDatabase(
            $database,
            function () use ($paths, $step): void {
                $this->migrator->rollback(
                    $paths,
                    [
                        'step'    => $step,
                        'pretend' => (bool)$this->option('pretend'),
                    ],
                );
            },
        );
    }

    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to rollback.', [],],
            ['step', null, InputOption::VALUE_OPTIONAL, 'Number of migration batches to rollback.', 1,],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.',],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation.',],
            ['pretend', null, InputOption::VALUE_NONE, 'Display the SQL queries without executing them.',],
        ];
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn() => $this->components->choice(
                'Which module(s) do you want to rollback?',
                $this->getAllModules(true),
                multiple: true,
            ),
        ];
    }
}
