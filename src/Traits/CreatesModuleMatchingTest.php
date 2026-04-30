<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Stringable;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

trait CreatesModuleMatchingTest
{
    /**
     * Add the standard command options for generating matching tests.
     *
     * @return void
     */
    protected function addTestOptions(): void
    {
        foreach (['test' => 'Test', 'pest' => 'Pest', 'phpunit' => 'PHPUnit'] as $option => $name) {
            $this->getDefinition()->addOption(new InputOption(
                $option,
                null,
                InputOption::VALUE_NONE,
                "Generate an accompanying {$name} test for the {$this->type} in modules or steward"
            ));
        }
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     * @return bool
     */
    protected function handleTestCreation($path): bool
    {
        if (! $this->option('test') && ! $this->option('pest') && ! $this->option('phpunit')) {
            return false;
        }

        return $this->call('module:make-test', [
                'name' => (new Stringable($path))->after($this->laravel['path'])->beforeLast('.php')->append('Test')->replace('\\', '/'),
                'module' => $this->getM,
                '--pest' => $this->option('pest'),
                '--phpunit' => $this->option('phpunit'),
                '--force' => $this->hasOption('force') && $this->option('force'),
            ]) == 0;
    }
}
