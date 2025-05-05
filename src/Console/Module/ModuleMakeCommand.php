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
          {--lareon : if teksite/lareon in installed use this flag to the module be managed by lareon cms.}
      ';

    protected $description = 'Create a new module';

    protected string $type = 'Module';

    public function handle()
    {
        $moduleName = Str::studly($this->argument('name'));

        $modulePath = $this->getModulePath($moduleName);
        if ($this->moduleExists($modulePath)) {
            $this->error("a directory or a module with the same name ($moduleName) already exists.");
            return;
        }

        $this->createDirectories($modulePath);
        $this->createFiles($modulePath, $moduleName);
        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> Module $moduleName created successfully.");
    }

    private function getModulePath(string $moduleName): string
    {
        return Module::modulePath($moduleName);
    }

    private function moduleExists(string $modulePath): bool
    {
        if (File::exists($modulePath)) return true;
        if (in_array($modulePath, Module::all())) return true;
        return false;
    }

    private function createDirectories(string $path): void
    {
        $directories = [
            '',
            'App',
            'App/Http',
            'App/Http/Controllers',
            'App/Models',
            'App/Providers',
            'config',
            'Database',
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
        $module=$this->argument('name');

        foreach ($directories as $directory) {
            File::makeDirectory("{$path}/{$directory}", 0755, true);
            $this->components->twoColumnDetail("Directory: <fg=white;options=bold>$module/$directory</>" ,'<fg=green;options=bold>DONE</>' );
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
                '{{ moduleName }}' => $moduleName,
                '{{ modulePath }}' => str_replace("\\", '/', $modulePath),
                '{{ namespace }}' => str_replace("\\", '\\\\', $namespace),
            ],
            "{$path}/composer.json"
        );

        /* Register ServiceProvider file  */
        if ($this->option('lareon')) {
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

        /* Register Seeder file file  */
        $this->generateFile(
            'basic/info.stub',
            [
                '{{ name }}' => $moduleName,
                '{{ alias }}' => strtolower($moduleName),
                '{{ providers }}' => str_replace('\\','\\\\',"$namespace\App\Providers\\".$moduleName."ServiceProvider"),
            ],

            "{$path}/info.json"
        );

        $this->registerModule($moduleName);

    }

    private function generateFile(string $stub, array $replacements, string $destination): void
    {
        $this->replaceStub($stub, $replacements, $destination);
        $relativePath=str_replace(base_path(), '' , $destination);
        $this->components->twoColumnDetail("File: <fg=white;options=bold>$relativePath</>" ,'<fg=green;options=bold>DONE</>' );

    }

    private function registerModule(string $moduleName): void
    {

        $bootstrapFile = module_bootstrap_path();
        $registeredModule = get_module_bootstrap();


        $namespace = Module::moduleNamespace($moduleName);

        $providerClass = "{$namespace}\\App\\Providers\\{$moduleName}ServiceProvider";

        if (!array_key_exists($moduleName, $registeredModule)) {
            $registeredModule[$moduleName]['provider'] = $providerClass;
            $registeredModule[$moduleName]['active'] = true;
            $registeredModule[$moduleName]['type'] = $this->option('lareon') ? 'lareon' :'self';

            File::put(
                $bootstrapFile,
                '<?php return ' . var_export_short($registeredModule, true) . ';'
            );
            $this->newLine();
            $this->components->twoColumnDetail("registering: module <fg=cyan;options=bold>$moduleName</> is added to bootstrap/modules.php" ,'<fg=green;options=bold>DONE</>' );
        } else {
            $this->newLine();
            $this->error("Module $moduleName is already in bootstrap/modules.php");
        }
    }

    private function dumpingComposer(): void
    {
        $this->info("wait to dump autoload of composer, it may take a while ...");

        Process::path(base_path())
            ->command('composer dump-autoload')
            ->run()->output();
    }
}
