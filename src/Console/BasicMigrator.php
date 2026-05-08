<?php

namespace Teksite\Module\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Teksite\Module\Traits\Migration\ModuleMigrationTrait;

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
     * Execute the console command.
     * @return int
     */
    public function handle(): int
    {
        if ($this->isProhibited() || !$this->confirmToProceed()) {
            return Command::FAILURE;
        }
        $this->newLine();
        return $this->handler($this->getModules());
    }

    /**
     * Main business logic of the command
     */
    abstract protected function handler(array $modules): int;

    /**
     * get modules name from the command or get all
     *
     * @return array
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
    protected function getAllModules(bool $onlyName = true): array
    {
        if ($this->cachedAllModules === null) {
            $this->cachedAllModules = get_all_modules($onlyName);
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
     */
    protected function showTimedDetail(string $label, \Closure $callback, ?string $errorMessage = null): float
    {
        try {
            $time = $this->measureExecutionTime($callback);
            $this->components->twoColumnDetail($label, "<fg=green>{$time}ms</>");
            return $time;
        } catch (\Throwable $e) {
            if ($errorMessage) {
                $this->components->twoColumnDetail($label, "<fg=red>Failed: {$errorMessage}</>");
            }
            throw $e;
        }
    }


    //
    //
    //

    /**
     * Show seeding summary
     */
    protected function showSummary(null|string $operationType = null): void
    {
        $this->newLine(2);

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

        if (!empty($this->successItems)) {
            $this->newLine();
            $this->components->bulletList(
                collect($this->successItems)
                    ->map(fn($item) => "<fg=green>✓ {$item}</>")
                    ->toArray()
            );
        }

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
