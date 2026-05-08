<?php

namespace Teksite\Module\Console\Migrate;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;

class SeedCommand extends BasicMigrator implements MigrationContract
{

    protected $name = 'module:db-seed';


    protected $description = 'Seed database records for specific modules or all modules';

    /**
     * @throws \Throwable
     */
    protected function handler(array $modules): int
    {
        $this->components->info('Seeding module databases...');
        $isForce = $this->option('force');

        foreach ($modules as $module) {
            $this->seedModule($module, $isForce);
        }

        $this->showSummary('Seeding ');

        return $this->failureCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Seed a single module
     */
    private function seedModule(string $module, bool $isForce): void
    {
        $seederClass = $this->getSeederClass($module);

        if (!$seederClass) {
            $this->warn("Seeder not found for module: {$module}");
            $this->failureCount++;
            $this->failedModules[] = $module;
            return;
        }

        $database = $this->getDatabaseConnection();

        try {
            $time = $this->showTimedDetail(
                "$module| Seeding: {$seederClass}",
                function () use ($seederClass, $database, $isForce) {
                    $this->executeSeeder($seederClass, $database, $isForce);
                }
            );

            $this->successCount++;
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail(
                "Seeding: {$module}",
                "<fg=red>Error: {$e->getMessage()}</>"
            );
            $this->failureCount++;
            $this->failedModules[] = $module;

            if (!$this->option('force')) {
                throw $e;
            }
        }
    }

    /**
     * Get the fully qualified seeder class name for a module
     */
    private function getSeederClass(string $module): ?string
    {
        $seederClass = module_namespace($module) . "\\Database\\Seeders\\{$module}DatabaseSeeder";

        return class_exists($seederClass) ? $seederClass : null;
    }

    /**
     * Execute the seeder
     */
    private function executeSeeder(string $seederClass, string $database, bool $isForce): void
    {
        $seeder = $this->laravel->make($seederClass);
        $seeder->setContainer($this->laravel);

        if (method_exists($seeder, 'setCommand')) {
            $seeder->setCommand($this);
        }

        $this->usingDatabase($database, function () use ($seeder, $isForce) {
            Model::unguarded(function () use ($seeder) {
                $seeder->__invoke();
            });
        });
    }


    /**
     * Get the console command options
     */
    protected function getOptions(): array
    {
        return [
            ['module', 'M', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specific modules to seed (comma-separated or multiple values)', []],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database connection to use for seeding'],
            ['force', null, InputOption::VALUE_NONE, 'Force seed operation even in production'],
        ];
    }
}
