<?php

namespace Teksite\Module\Console\Migrate;

use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;

class RollbackCommands extends BasicMigrator
{
    protected $name = 'module:migrate-rollback';

    protected $description = 'Rollback the last batch of database migrations for a specific module or all modules';

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
        $this->components->info('Rolling back module migrations (last batch)...');

        $step = (int) $this->option('step');
        $hasModuleOption = !empty($this->option('module'));

        $options = array_filter([
            'pretend' => $this->option('pretend'),
            'step'    => $this->option('step'),
            'force'   => $this->option('force'),
        ]);

        if ($hasModuleOption) {
            foreach ($modules as $module) {
                $this->rollbackSpecificModule($module, $step);
            }
        } else {
            $this->rollbackGlobal($step);
        }

        $this->showSummary('rollback');
        return $this->failureCount === 0 ? CommandAlias::SUCCESS : CommandAlias::FAILURE;
    }

    private function rollbackSpecificModule(string $module, int $step): void
    {
        $migrationPath = $this->getMigrationPath($module);

        if (!$this->isValidMigrationPath($migrationPath)) {
            $this->warn("No migration path found: {$module}");
            $this->addFailureItem($module);
            return;
        }

        $moduleFiles = $this->getModuleMigrationFiles($migrationPath);

        $allRan = $this->migrator->getRepository()->getRan();
        $executedModuleMigrations = array_intersect($allRan, $moduleFiles);

        if (empty($executedModuleMigrations)) {
            $this->components->twoColumnDetail($module, "<fg=yellow>No migrations found</>");
            $this->addSuccessItem($module);
            return;
        }

        $batchGroups = [];
        foreach ($executedModuleMigrations as $migration) {
            $batch = $this->migrator->getRepository()->ba($migration);
            $batchGroups[$batch][] = $migration;
        }

        // 4. مرتب‌سازی batch ها (نزولی)
        $batches = array_keys($batchGroups);
        rsort($batches);

        // 5. انتخاب به تعداد step
        $batchesToRollback = array_slice($batches, 0, $step);

        if (empty($batchesToRollback)) {
            $this->components->twoColumnDetail($module, "<fg=yellow>No batches to rollback</>");
            $this->addSuccessItem($module);
            return;
        }

        // 6. اجرای rollback
        $this->components->twoColumnDetail("<fg=cyan>{$module}</>", $migrationPath);

        foreach ($batchesToRollback as $batch) {
            foreach ($batchGroups[$batch] as $migration) {
                $this->rollbackMigration($migrationPath, $migration);
                $this->components->twoColumnDetail(
                    "  └─ {$migration}",
                    "<fg=yellow>rolled back (batch {$batch})</>"
                );
            }
        }

        $this->addSuccessItem($module);
    }

    /**
     * سناریو 2: همه ماژول‌ها با هم - بر اساس batch های کلی
     */
    private function rollbackGlobal(int $step): void
    {
        $this->components->info("Rolling back last {$step} batch(es) from ALL modules...");

        // 1. همه migration های اجرا شده از دیتابیس (همه ماژول‌ها)
        $allRanMigrations = $this->migrator->getRepository()->getRan();

        if (empty($allRanMigrations)) {
            $this->components->warn("No migrations found in database.");
            return;
        }

        // 2. گروه‌بندی بر اساس batch (بدون توجه به ماژول)
        $batchGroups = [];
        foreach ($allRanMigrations as $migration) {
            $batch = $this->migrator->getRepository()->getBatchNumber($migration);
            $batchGroups[$batch][] = $migration;
        }

        // 3. مرتب‌سازی batch ها (نزولی)
        $batches = array_keys($batchGroups);
        rsort($batches);

        // 4. انتخاب به تعداد step
        $batchesToRollback = array_slice($batches, 0, $step);

        if (empty($batchesToRollback)) {
            $this->components->warn("No batches to rollback.");
            return;
        }

        // 5. برای هر batch، همه migration ها را rollback کن
        foreach ($batchesToRollback as $batch) {
            $this->components->info("Rolling back batch {$batch}...");

            foreach ($batchGroups[$batch] as $migration) {
                // پیدا کردن مسیر و ماژول مربوط به این migration
                $modulePath = $this->findModulePathForMigration($migration);

                if ($modulePath) {
                    $this->rollbackMigration($modulePath, $migration);
                    $moduleName = $this->getModuleNameFromPath($modulePath);
                    $this->components->twoColumnDetail(
                        "  └─ [{$moduleName}] {$migration}",
                        "<fg=yellow>rolled back</>"
                    );
                } else {
                    $this->components->warn("  └─ {$migration} - module not found");
                    $this->addFailureItem($migration);
                }
            }
        }
    }

    /**
     * پیدا کردن مسیر migration بر اساس نام فایل
     */
    private function findModulePathForMigration(string $migrationName): ?string
    {
        $allModules = $this->getEnabledModules();

        foreach ($allModules as $module) {
            $path = $this->getMigrationPath($module);

            if (!$this->isValidMigrationPath($path)) {
                continue;
            }

            $moduleFiles = $this->getModuleMigrationFiles($path);

            if (in_array($migrationName, $moduleFiles)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * گرفتن نام ماژول از مسیر
     */
    private function getModuleNameFromPath(string $path): string
    {
        // فرض کنید مسیرها به صورت: .../Modules/Post/Database/Migrations
        if (preg_match('/Modules[\/\\\\]([^\/\\\\]+)/', $path, $matches)) {
            return $matches[1];
        }

        // مسیر steward
        if (str_contains($path, 'steward')) {
            return 'steward';
        }

        return 'unknown';
    }

    /**
     * اجرای rollback برای یک migration خاص
     */
    private function rollbackMigration(string $path, string $migration): void
    {
        $database = $this->getDatabaseConnection();

        $this->usingDatabase($database, function () use ($path, $migration) {
            $this->migrator->rollbackMigrations([$path], $migration, false);
        });
    }


    private function getModuleMigrationFiles(string $path): array
    {
        if (!$this->migrator) {
            return [];
        }

        $files = $this->migrator->getMigrationFiles($path);
        return array_keys($files);
    }

    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules', []],
            ['step', null, InputOption::VALUE_OPTIONAL, 'Number of batches to rollback', 1],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database connection'],
            ['force', null, InputOption::VALUE_NONE, 'Force operation'],
            ['pretend', null, InputOption::VALUE_NONE, 'Pretend mode'],
        ];
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn() => $this->components->choice(
                'Which module(s) do you want to rollback?',
                $this->getAllModules(true),
                multiple: true
            ),
        ];
    }
}
