<?php

namespace Teksite\Module\Console\Migrate;

use Teksite\Module\Console\BasicMigrator;
use Teksite\Module\Contract\MigrationContract;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;

class FreshCommands extends BasicMigrator implements MigrationContract
{

    protected $signature = 'module:migrate-fresh
    {--module=}
    {--seed}';

    protected $description = 'Drop all tables and re-run migrations for a specific module or all modules';

    public function handle(): void
    {
        parent::handle();
        if ($this->option('seed')) $this->seeding();
    }

    public function runTheCommand(): void
    {
        $this->down();
        $this->up();
    }
}
