<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;
use Teksite\Module\Facade\Module;

class ModuleMakeCommand extends Command
{

    use ModuleGeneratorCommandTrait;

    protected $name = 'module:make';

    protected $description = 'Create a new module';

    protected string $type = 'Module';

    public function handle(): void
    {
        $moduleName = Str::studly($this->argument('name'));
        $modulePath = $this->getModulePath($moduleName);

        $moduleType = $this->isSteward() ? 'steward' : 'self';

        if (!$this->validating($moduleName, $modulePath)) return;

        $this->newLine();
        $this->line("making <fg=cyan;options=bold>$moduleName</> module:");

        $this->createDirectories($modulePath, $moduleName);
        $this->createFiles($modulePath, $moduleName, $moduleType);
        $this->registerModule($moduleName, $moduleType);

        $this->newLine();
        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module $moduleName created successfully.");
    }

    private function validating(string $moduleName, $modulePath,): bool
    {
        return $this->validateModuleState($moduleName, $modulePath, shouldAlreadyExist: false, shouldAlreadyBeRegistered: false);
    }

    private function createDirectories(string $path, string $moduleName,): void
    {
        $this->createScaffoldDirectories($path, $moduleName);
    }

    private function createFiles(string $path, string $moduleName, string $moduleType,): void
    {
        $namespace = Module::moduleNamespace($moduleName);

        $modulePath = Module::modulePath($moduleName, absolute: false);

        /* Register Composer file  */
        $this->line(" └─ making files");

        $this->generateFile(
            'stubs/composer.stub',
            [
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ moduleName }}'      => $moduleName,
                '{{ modulePath }}'      => str_replace("\\", '/', $modulePath),
                '{{ namespace }}'       => str_replace("\\", '\\\\', $namespace),
            ],
            "{$path}/composer.json",
        );

        $providerStub = $this->isSteward() ? 'stubs/provider-steward-managed.stub' : 'stubs/provider-self-service.stub';

        $this->generateFile(
            $providerStub,
            [
                '{{ namespace }}'       => "{$namespace}\\App\\Providers",
                '{{ class }}'           => "{$moduleName}ServiceProvider",
                '{{ module }}'          => $moduleName,
                '{{ moduleLowerName }}' => strtolower($moduleName),

            ],
            "{$path}/app/Providers/{$moduleName}ServiceProvider.php",
        );

        /* Register Event ServiceProvider file  */
        $this->generateFile(
            'stubs/provider-event.stub',
            [
                '{{ namespace }}'       => "{$namespace}\\App\\Providers",
                '{{ class }}'           => "EventServiceProvider",
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ module }}'          => $moduleName,
            ],
            "{$path}/app/Providers/EventServiceProvider.php",
        );

        /* Register Route ServiceProvider file  */
        if (!$this->isSteward()) {
            $this->generateFile(
                'stubs/provider-route.stub',
                [
                    '{{ namespace }}'       => "{$namespace}\\App\\Providers",
                    '{{ class }}'           => "RouteServiceProvider",
                    '{{ moduleLowerName }}' => strtolower($moduleName),
                    '{{ module }}'          => $moduleName,
                ],
                "{$path}/app/Providers/RouteServiceProvider.php",
            );
        }

        /* Register Abstract controller file  */
        $this->generateFile(
            'stubs/controller-abstract.stub',
            [
                '{{$namespace}}' => "{$namespace}\\App\\Http\\Controllers",
            ],
            "{$path}/app/Http/Controllers/Controller.php",
        );

        /* Register config file  */
        $this->generateFile(
            'stubs/config.stub',
            [
                '{{ module }}' => $moduleName,
            ],
            "{$path}/config/config.php",
        );

        /* Register JS file  */
        $this->generateFile(
            'stubs/js.stub',
            [],
            "{$path}/resources/js/app.js",
        );

        /* Register CSS file  */
        $this->generateFile(
            'stubs/css.stub',
            [],
            "{$path}/resources/css/app.css",
        );

        /* Register master blade file  */
        $this->generateFile(
            'stubs/view.stub',
            ['{{ quote }}' => Inspiring::quote()],
            "{$path}/resources/views/master.blade.php",
        );

        /* Register web route file  */
        $this->generateFile(
            'stubs/route-web.stub',
            ['{{ module }}' => strtolower($moduleName)],
            "{$path}/routes/web.php",
        );

        /* Register Seeder file */
        $this->generateFile(
            'stubs/seeder.stub',
            [
                '{{ module }}'    => strtolower($moduleName),
                '{{ namespace }}' => "{$namespace}\\Database\\Seeders",
                '{{ class }}'     => "{$moduleName}DatabaseSeeder",
            ],

            "{$path}/database/Seeders/{$moduleName}DatabaseSeeder.php",
        );

        /* Register Seeder file  */
        $this->generateFile(
            'stubs/info.stub',
            [
                '{{ name }}'      => $moduleName,
                '{{ alias }}'     => strtolower($moduleName),
                '{{ providers }}' => str_replace('\\', '\\\\', "$namespace\App\Providers\\".$moduleName."ServiceProvider"),
            ],

            "{$path}/info.json",
        );
    }

    private function generateFile(string $stub, array $replacements, string $destination,): void
    {
        $this->generateScaffoldFile($stub, $replacements, $destination);
    }

    protected function getOptions(): array
    {
        if (!Module::isStewardInstalled()) return [];

        return [
            ['steward', 's', InputOption::VALUE_NONE, 'to be managed by steward'],
        ];
    }
}
