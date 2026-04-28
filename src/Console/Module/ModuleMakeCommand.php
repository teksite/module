<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

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

        if (!$this->validating($modulePath, $modulePath)) return;

        $this->createDirectories($modulePath);
        $this->createFiles($modulePath, $moduleName);

        $this->newLine();
        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module $moduleName created successfully.");
    }


    private function validating(string $moduleName, $modulePath): bool
    {
        if (!$this->isAllowedName($moduleName)) {
            $this->error("$moduleName is not allowed");
            return false;
        }

        if ($this->isModuleDirectoryExists($modulePath)) {
            $this->error("a directory with the same name ($moduleName) already exists.");
            return false;
        }

        if ($this->isModuleRegistered($moduleName)) {
            $this->error("a module with the same name ($moduleName) already exists in bootstrap module file.");
            return false;
        }
        return true;
    }


    private function createDirectories(string $path): void
    {
        $directories = [
            '',
            'app',
            'app/Http',
            'app/Http/Controllers',
            'app/Models',
            'app/Providers',
            'config',
            'database',
            'database/factories',
            'database/migrations',
            'database/seeders',
            'lang',
            'resources/views',
            'resources/js',
            'resources/css',
            'routes',
            'tests',
            'tests/Feature',
            'tests/Unit',
        ];

        $module = $this->argument('name');

        foreach ($directories as $directory) {
            File::makeDirectory("{$path}/{$directory}", 0755, true);
            $this->components->twoColumnDetail("Directory: <fg=white;options=bold>$module/$directory</>", '<fg=green;options=bold>DONE</>');
        }
    }

    private function createFiles(string $path, string $moduleName): void
    {
        $namespace = Module::moduleNamespace($moduleName);

        $modulePath = Module::modulePath($moduleName, absolute: false);

        /* Register Composer file  */
        $this->generateFile(
            'basic/composer.stub',
            [
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ moduleName }}'      => $moduleName,
                '{{ modulePath }}'      => str_replace("\\", '/', $modulePath),
                '{{ namespace }}'       => str_replace("\\", '\\\\', $namespace),
            ],
            "{$path}/composer.json"
        );

        /* Register ServiceProvider file  */
        if ($this->option('steward')) {

            $this->generateFile(
                'basic/provider-steward-managed.stub',
                [
                    '{{ namespace }}'       => "{$namespace}\\Providers",
                    '{{ class }}'           => "{$moduleName}ServiceProvider",
                    '{{ module }}'          => $moduleName,
                    '{{ moduleLowerName }}' => strtolower($moduleName),

                ],
                "{$path}/app/Providers/{$moduleName}ServiceProvider.php"
            );
        } else {
            $this->generateFile(
                'basic/provider-self-service.stub',
                [
                    '{{ namespace }}'       => "{$namespace}\\Providers",
                    '{{ class }}'           => "{$moduleName}ServiceProvider",
                    '{{ module }}'          => $moduleName,
                    '{{ moduleLowerName }}' => strtolower($moduleName),
                ],
                "{$path}/app/Providers/{$moduleName}ServiceProvider.php"
            );
        }
        /* Register Event ServiceProvider file  */
        $this->generateFile(
            'basic/provider-event.stub',
            [
                '{{ namespace }}'       => "{$namespace}\\Providers",
                '{{ class }}'           => "EventServiceProvider",
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ module }}'          => $moduleName,
            ],
            "{$path}/app/Providers/EventServiceProvider.php"
        );
        /* Register Route ServiceProvider file  */
        if (!$this->option('steward')) {
            $this->generateFile(
                'basic/provider-route.stub',
                [
                    '{{ namespace }}'       => "{$namespace}\\Providers",
                    '{{ class }}'           => "RouteServiceProvider",
                    '{{ moduleLowerName }}' => strtolower($moduleName),
                    '{{ module }}'          => $moduleName,
                ],
                "{$path}/app/Providers/RouteServiceProvider.php"
            );
        }
        /* Register Abstract controller file  */
        $this->generateFile(
            'basic/controller-abstract.stub',
            [
                '{{$namespace}}' => "{$namespace}\\Http\\Controllers",
            ],
            "{$path}/app/Http/Controllers/Controller.php"
        );
        /* Register config file  */
        $this->generateFile(
            'basic/config.stub',
            [
                '{{ module }}' => $moduleName,
            ],
            "{$path}/config/config.php"
        );
        /* Register JS file  */
        $this->generateFile(
            'basic/js.stub',
            [],
            "{$path}/resources/js/app.js"
        );
        /* Register CSS file  */
        $this->generateFile(
            'basic/css.stub',
            [],
            "{$path}/resources/css/app.css"
        );
        /* Register master blade file  */
        $this->generateFile(
            'basic/view.stub',
            ['{{ quote }}' => Inspiring::quote()],
            "{$path}/resources/views/master.blade.php"
        );
        /* Register web route file  */
        $this->generateFile(
            'basic/route-web.stub',
            ['{{ module }}' => strtolower($moduleName)],
            "{$path}/routes/web.php"
        );
        /* Register Seeder file */
        $this->generateFile(
            'basic/seeder.stub',
            [
                '{{ module }}'    => strtolower($moduleName),
                '{{ namespace }}' => "{$namespace}\\Database\\Seeders",
                '{{ class }}'     => "{$moduleName}DatabaseSeeder",
            ],

            "{$path}/database/Seeders/{$moduleName}DatabaseSeeder.php"
        );
        /* Register Seeder file  */
        $this->generateFile(
            'basic/info.stub',
            [
                '{{ name }}'      => $moduleName,
                '{{ alias }}'     => strtolower($moduleName),
                '{{ providers }}' => str_replace('\\', '\\\\', "$namespace\App\Providers\\" . $moduleName . "ServiceProvider"),
            ],

            "{$path}/info.json"
        );

        $this->registerModule($moduleName);
    }

    private function generateFile(string $stub, array $replacements, string $destination): void
    {
        $this->replaceStub($stub, $replacements, $destination);
        $relativePath = normalizeSlashPath(str_replace(base_path(), '', $destination));
        $this->components->twoColumnDetail("File: <fg=white;options=bold>$relativePath</>", '<fg=green;options=bold>DONE</>');

    }

    private function registerModule(string $moduleName): void
    {
        $bootstrapFile = module_bootstrap_path();
        $registeredModule = get_module_bootstrap();

        $namespace = Module::moduleNamespace($moduleName);

        $providerClass = "{$namespace}\\Providers\\{$moduleName}ServiceProvider";

        if (!array_key_exists($moduleName, $registeredModule)) {
            $registeredModule[$moduleName]['provider'] = $providerClass;
            $registeredModule[$moduleName]['active'] = true;
            $registeredModule[$moduleName]['type'] = $this->option('steward') ? 'steward' : 'self';

            File::put(
                $bootstrapFile,
                '<?php return ' . humanReadableVarExport($registeredModule, true) . ';'
            );
            $this->newLine();
            $this->components->twoColumnDetail("registering: module <fg=cyan;options=bold>$moduleName</> is added to bootstrap/modules.php", '<fg=green;options=bold>DONE</>');
        } else {
            $this->newLine();
            $this->error("Module $moduleName is already in bootstrap/modules.php");
        }
    }

    protected function getOptions(): array
    {
        return [
            ['steward', 's', InputOption::VALUE_NONE, 'to be managed by steward'],
        ];
    }
}
