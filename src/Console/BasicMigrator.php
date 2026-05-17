<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command as CommandAlias;

abstract class BasicMigrator extends Command implements Isolatable
{
    use Prohibitable, ConfirmableTrait;

    /**
     * Cache for modules list to avoid repeated calls
     */
    private ?array $cachedAllModules = null;
    private ?array $cachedEnabledModules = null;
    private ?array $cachedDisabledModules = null;

    protected int $successCount = 0;
    protected int $failureCount = 0;
    protected array $successItems = [];
    protected array $failedItems = [];

    /**
     * @var Migrator|null The migrator instance for database operations
     */
    protected ?Migrator $migrator = null;


    /**
     * Execute the console command.
     * @return int
     */
    public function handle(): int
    {
        if ($this->isProhibited() || !$this->confirmToProceed()) {
            return CommandAlias::FAILURE;
        }
        $this->newLine();

        if (method_exists($this, 'needsMigrator') && $this->needsMigrator()) {
            $this->migrator = app('migrator');
        }

        return $this->handler($this->getModules());
    }

    /**
     * Main business logic of the command
     */
    abstract protected function handler(array $modules): int;


    /**
     * Set the migrator instance
     */
    public function setMigrator(Migrator $migrator): self
    {
        $this->migrator = $migrator;
        return $this;
    }


    /**
     * get modules name from the command or get all
     *
     */
    protected function getModules(): array
    {
        $modules = $this->parseModuleOption();
        $this->validateModulesExist($modules);
        $this->updateModuleOption($modules);
        return $modules;
    }

    /**
     * Get all enabled modules (cached)
     */
    protected function getEnabledModules(): array
    {
        return $this->cachedEnabledModules ??= get_enabled_modules(true);
    }

    /**
     * Get all disabled modules (cached)
     */
    protected function getDisabledModules(): array
    {
        return $this->cachedDisabledModules ??= get_disabled_modules(true);
    }

    /**
     * Get all modules (cached)
     */
    protected function getAllModules(bool $onlyName = true , bool $onlyEnabled = true ): array
    {
        if ($this->cachedAllModules === null) {
            $modules=$onlyEnabled ? get_enabled_modules(true ) : get_all_modules($onlyName);
            if (isStewardInstalled()) {
                $modules= array_merge(['Steward'] , $modules);
            }
            $this->cachedAllModules =  $modules;
            $this->cachedEnabledModules =  $modules;
        }

        return $this->cachedAllModules;
    }

    /**
     * Check if a specific module exists
     */
    protected function moduleExists(string $moduleName): bool
    {
        return in_array($moduleName, $this->getAllModules(true), true);
    }

    /**
     * Execute a closure and measure execution time in milliseconds
     */
    protected function measureExecutionTime(\Closure $callback): float
    {
        $startTime = Carbon::now();
        $callback();
        $endTime = Carbon::now();

        return $startTime->diffInMilliseconds($endTime);
    }


    /**
     * Execute callback with a specific database connection
     */
    protected function usingDatabase(string $database, \Closure $callback): mixed
    {
        $resolver = app('db');
        $previousConnection = $resolver->getDefaultConnection();

        try {
            $resolver->setDefaultConnection($database);
            return $callback();
        } finally {
            $resolver->setDefaultConnection($previousConnection);
        }
    }

    private function parseModuleOption(): array
    {
        $moduleOption = $this->option('module');
        if (empty($moduleOption)) {
            return $this->getAllModules(true);
        }

        return Collection::make($moduleOption)
                         ->flatMap(fn($module) => $this->splitAndTrimModuleString($module))
                         ->unique()
                         ->values()
                         ->all();
    }

    /**
     * Split comma-separated module string and trim each item
     */
    private function splitAndTrimModuleString(string $moduleString): array
    {
        return Collection::make(explode(',', $moduleString))
                         ->map('trim')
                         ->filter()
                         ->values()
                         ->all();
    }

    /**
     * Validate that all requested modules exist
     *
     * @throws InvalidArgumentException
     */
    private function validateModulesExist(array $modules): void
    {
        $invalidModules = array_diff($modules, $this->getAllModules(true));

        if (!empty($invalidModules)) {
            throw new InvalidArgumentException(
                sprintf('Modules not found or disabled: [%s]', implode(', ', $invalidModules))
            );
        }
    }

    /**
     * Update the module option value in input
     */
    private function updateModuleOption(array $modules): void
    {
        $this->input->setOption('module', $modules);
    }

    /**
     * Display a two-column detail with timing
     * @throws \Throwable
     */
    protected function showTimedDetail(string $label, \Closure $callback, ?string $errorMessage = null): float
    {
        try {
            $time = $this->measureExecutionTime($callback);
            $this->components->twoColumnDetail($label, "<fg=green>{$time}ms</>");
            return $time;
        } catch (\Throwable $e) {
            Log::error($e);
            if ($errorMessage) {
                $this->components->twoColumnDetail($label, "<fg=red>Failed: {$errorMessage}</>");
            }
            throw $e;
        }
    }


    protected function getMigrationPath(string $module): string
    {
        if ($module === 'Steward') {
            return steward_path(config('modules.steward.migration_path', 'database/migrations'), false);
        }
        return module_path($module, config('modules.module.migration_path', 'database/migrations'), false);
    }


    /**
     * Validate if migration path exists and is readable
     */
    protected function isValidMigrationPath(string $path): bool
    {
        return is_dir($path) && is_readable($path);
    }

    /**
     * Format migration name for display
     */
    protected function formatMigrationName(string $migration): string
    {
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);
        $name = str_replace('_', ' ', $name);
        return ucwords($name);
    }

    /**
     * Get ran migrations for a specific path
     */
    protected function getRanMigrationsForPath(string $migrationPath): array
    {
        if (!$this->migrator) {
            return [];
        }

        try {
            $repository = $this->migrator->getRepository();

            if (!$repository->repositoryExists()) {
                return [];
            }

            $ran = $repository->getRan();
            $migrationFiles = $this->migrator->getMigrationFiles($migrationPath);

            // Return only migrations that belong to this path
            return array_intersect($ran, array_keys($migrationFiles));

        } catch (\Exception $e) {
            Log::error($e);

            return [];
        }
    }


    /**
     * Process a single module migration/rollback operation
     * @throws \Throwable
     */
    protected function processModuleOperation(
        string   $module,
        string   $operationType, // 'migrate', 'reset', 'rollback', 'fresh'
        array    $options,
        callable $operationCallback
    ): bool
    {
        $migrationPath = $this->getMigrationPath($module);

        if (!$this->isValidMigrationPath($migrationPath)) {
            $this->warn("No migration path found for module: {$module}");
            $this->addFailureItem($module);
            return false;
        }

        try {
            $this->components->twoColumnDetail("<fg=cyan;options=bold>{$module}</>", "$migrationPath");

            $time = $this->measureExecutionTime(function () use ($operationCallback, $module, $migrationPath, $options) {
                $operationCallback($module, $migrationPath, $options);
            });

            $this->components->twoColumnDetail("<fg=green>✓ {$module} {$operationType} completed</>", "<fg=green>{$time}ms</>");

            $this->addSuccessItem($module);

            return true;

        } catch (\Throwable $e) {
            Log::error($e);
            $this->components->error("✗ {$module} failed: " . $e->getMessage());
            $this->addFailureItem($module);

            if (!$this->option('force')) {
                throw $e;
            }
            return false;
        }
    }

    protected function addFailureItem($module): void
    {
        $this->failureCount++;
        $this->failedItems[] = $module;
    }

    protected function addSuccessItem($module): void
    {
        $this->successCount++;
        $this->successItems[] = $module;

    }

    /**
     * Show seeding summary
     */
    protected function showSummary(null|string $operationType = null): void
    {
        $this->newLine();

        $total = $this->successCount + $this->failureCount;

        $this->components->twoColumnDetail(
            "<fg=yellow>{$operationType} Summary</>",
            sprintf(
                '✅ Success: %d | ❌ Failed: %d | 📁 Total: %d',
                $this->successCount,
                $this->failureCount,
                $total
            )
        );


        if (!empty($this->failedItems)) {
            $this->newLine();
            $this->components->error('Failed items:');
            $this->components->bulletList(
                collect($this->failedItems)
                    ->map(fn($item) => "<fg=red>✗ {$item}</>")
                    ->toArray()
            );
        }

        $this->newLine();
    }


    protected function ensureMigrationTableExists(?string $database): void
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
     * Reset statistics
     */
    protected function resetStats(): void
    {
        $this->successCount = 0;
        $this->failureCount = 0;
        $this->successItems = [];
        $this->failedItems = [];
    }

    /**
     * Get the database connection name
     */
    protected function getDatabaseConnection(): string
    {
        return $this->option('database') ?: $this->laravel['config']['database.default'];
    }
}
