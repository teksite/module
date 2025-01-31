<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Teksite\Module\Facade\Module;
use Teksite\Module\Traits\ModuleGeneratorCommandTrait;

class ModuleMakeCommand extends Command
{

    use ModuleGeneratorCommandTrait;

    protected $signature = 'module:make {name}
          {--self : make self service provider}
      ';

    protected $description = 'Create a new module';

    protected $type = 'Module';

    public function handle()
    {
        $moduleName = Str::studly($this->argument('name'));

        $modulePath = $this->getModulePath($moduleName);
        if ($this->moduleExists($modulePath)) {
            $this->error("The module '{$moduleName}' already exists.");
            return;
        }

        $this->createDirectories($modulePath);
        $this->createFiles($modulePath, $moduleName);
        $this->dumpngComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module {$moduleName} created successfully.");
    }

    private function getModulePath(string $moduleName): string
    {
        return Module::modulePath($moduleName);
    }

    private function moduleExists(string $modulePath): bool
    {
        return File::exists($modulePath);
    }

    private function createDirectories(string $path): void
    {
        $directories = [
            '',
            'App/Http/Controllers',
            'App/Models',
            'App/Providers',
            'config',
            'Database/Factories',
            'Database/Migrations',
            'Database/Seeders',
            'lang',
            'resources/views',
            'resources/js',
            'resources/css',
            'routes',
            'Tests',
            'Tests/Feature',
            'Tests/Unit',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$path}/{$directory}", 0755, true);
            $this->line("Directory: {$path}/{$directory} is generated");

        }
    }

    private function createFiles(string $path, string $moduleName): void
    {
        $namespace = config('moduleconfigs.module.namespace') . '\\' . $moduleName;

        $modulePath = Module::modulePath($moduleName, absolute: false);

        /* Register Composer file  */
        $this->generateFile(
            'basic/composer.stub',
            [
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ moduleName }}' => $moduleName,

                '{{ modulePath }}' => str_replace("\\", '/', $modulePath),
                '{{ namespace }}' => str_replace("\\", '\\\\', $namespace),
            ],
            "{$path}/composer.json"
        );

        /* Register ServiceProvider file  */
        if (!$this->option('self')) {
            $this->generateFile(
                'basic/provider.stub',
                [
                    '{{ namespace }}' => "{$namespace}\\App\\Providers",
                    '{{ class }}' => "{$moduleName}ServiceProvider",
                    '{{ module }}' => $moduleName,
                    '{{ moduleLowerName }}' => strtolower($moduleName),

                ],
                "{$path}/App/Providers/{$moduleName}ServiceProvider.php"
            );
        } else {
            $this->generateFile(
                'basic/provider-service.stub',
                [
                    '{{ namespace }}' => "{$namespace}\\App\\Providers",
                    '{{ class }}' => "{$moduleName}ServiceProvider",
                    '{{ module }}' => $moduleName,
                    '{{ moduleLowerName }}' => strtolower($moduleName),
                ],
                "{$path}/App/Providers/{$moduleName}ServiceProvider.php"
            );
        }
        /* Register Event ServiceProvider file  */
        $this->generateFile(
            'basic/provider-event.stub',
            [
                '{{ namespace }}' => "{$namespace}\\App\\Providers",
                '{{ class }}' => "EventServiceProvider",
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ module }}' => $moduleName,
            ],
            "{$path}/App/Providers/EventServiceProvider.php"
        );
        /* Register Route ServiceProvider file  */
        $this->generateFile(
            'basic/provider-route.stub',
            [
                '{{ namespace }}' => "{$namespace}\\App\\Providers",
                '{{ class }}' => "RouteServiceProvider",
                '{{ moduleLowerName }}' => strtolower($moduleName),
                '{{ module }}' => $moduleName,
            ],
            "{$path}/App/Providers/RouteServiceProvider.php"
        );
        /* Register Abstract controller file  */
        $this->generateFile(
            'basic/controller-abstract.stub',
            [
                '{{$namespace}}' => "{$namespace}\\App\\Http\\Controllers",
            ],
            "{$path}/App/Http/Controllers/Controller.php"
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
            "{$path}/resources/js/scripts.js"
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

        /* Register Seeder file file  */
        $this->generateFile(
            'basic/seeder.stub',
            [
                '{{ module }}' => strtolower($moduleName),
                '{{ namespace }}' => "{$namespace}\\Database\\Seeders",
                '{{ class }}' => "{$moduleName}DatabaseSeeder",
            ],

            "{$path}/Database/Seeders/{$moduleName}DatabaseSeeder.php"
        );

        $this->addModuleToConfig($moduleName);

    }

    private function generateFile(string $stub, array $replacements, string $destination): void
    {
        $this->replaceStub($stub, $replacements, $destination);
        $this->line("File: $destination is generated");
    }

    private function addModuleToConfig(string $moduleName): void
    {
        $configPath = config_path('modules.php');
        $modules = File::exists($configPath) ? require $configPath : [
            'modules' => [],
            'routes' => [
                'client.web' => [
                    'path' => 'web.php',
                    'middleware' => '',
                    'prefix' => '',
                    'name' => ''
                ],
                'client.api.v1' => [
                    'path' => 'api.php',
                    'middleware' => 'api',
                    'prefix' => 'api\v1',
                    'name' => 'api.v1.'
                ],
                'client.ajax' => [
                    'path' => 'ajax.php',
                    'middleware' => 'api',
                    'prefix' => 'ajax',
                    'name' => 'ajax'
                ],
                'admin.web' => [
                    'path' => 'admin/web.php',
                    'middleware' => 'auth,verified',
                    'prefix' => 'tkadmin',
                    'name' => ''
                ],
                'admin.api.v1' => [
                    'path' => 'admin/api.php',
                    'middleware' => 'api',
                    'prefix' => 'tkadmin/api',
                    'name' => 'api.v1.'
                ],
                'admin.ajax' => [
                    'path' => 'admin/ajax.php',
                    'middleware' => 'api,auth,verified',
                    'prefix' => 'tkadmin/ajax',
                    'name' => 'admin.ajax.'
                ],
                'panel.web' => [
                    'path' => 'panel/web.php',
                    'middleware' => 'auth,verified',
                    'prefix' => 'panel',
                    'name' => ''
                ],
                'panel.api.v1' => [
                    'path' => 'panel/api.php',
                    'middleware' => 'api',
                    'prefix' => 'panel/api',
                    'name' => 'api.v1.'
                ],
                'panel.ajax' => [
                    'path' => 'panel/ajax.php',
                    'middleware' => 'api,auth,verified',
                    'prefix' => 'panel/ajax',
                    'name' => 'panel.ajax.'
                ],

            ],
            'configs' => [
                'config.php',
                'search.php',
                'sitemap.php',
            ]
        ];

        $namespace = Module::moduleNamespace($moduleName);
        $providerClass = "{$namespace}\\App\\Providers\\{$moduleName}ServiceProvider";

        if (!array_key_exists($moduleName, $modules['modules'])) {
            $modules['modules'][$moduleName] = $providerClass;

            File::put(
                $configPath,
                '<?php return ' . var_export($modules, true) . ';'
            );

            $this->info("Module {$moduleName} added to config/modules.php.");

        } else {
            $this->info("Module {$moduleName} is already in config/modules.php.");
        }
    }

    private function dumpngComposer()
    {
        $this->info("now wait to dump autoload of composer, it may take a while ...");

        Process::path(base_path())
            ->command('composer dump-autoload')
            ->run()->output();

    }

}
