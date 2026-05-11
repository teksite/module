<?php

namespace Teksite\Module\Console\Module;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\Module\traits\ModuleGeneratorCommandTrait;
use Teksite\Module\Facade\Module;

class StewardInitialize extends Command
{
    use ModuleGeneratorCommandTrait;

    protected $name = 'module:steward';

    protected $description = 'Developing a steward module to manage modules that are overseen by headquarters.';

    protected string $type = 'Module';

    public function handle(): void
    {
        $stewardName = 'Steward';

        $stewardPath = $this->getStewardPath();

        $this->newLine();
        $this->line("making <fg=cyan;options=bold>$stewardName</>:");

        $this->createDirectories($stewardPath, $stewardName);
        $this->createFiles($stewardPath, $stewardName);

        $this->newLine();
        $this->dumpingComposer();

        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('black', 'blue', ['bold']));
        $this->newLine();

        $this->info("<success>SUCCESS</success> $stewardName created successfully.");
    }


    private function createDirectories(string $path, string $stewardName): void
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


        $this->line(" └─ making directories");
        foreach ($directories as $directory) {
            File::makeDirectory("{$path}/{$directory}", 0755, true);
            $this->components->twoColumnDetail("<fg=gray>  └─ " . "$stewardName/$directory</>", "<fg=green>✓ DONE</>");

        }
    }

    private function createFiles(string $path, string $stewardName): void
    {
        $namespace = Module::stewardNamespace();

        $modulePath = Module::stewardPath(absolute: false);


        /* Register Composer file  */
        $this->line(" └─ making files");

        $this->generateFile(
            'stubs/composer.stub',
            [
                '{{ moduleLowerName }}' => strtolower($stewardName),
                '{{ moduleName }}'      => $stewardName,
                '{{ modulePath }}'      => str_replace("\\", '/', $modulePath),
                '{{ namespace }}'       => str_replace("\\", '\\\\', $namespace),
            ],
            "{$path}/composer.json"
        );

        /* Register ServiceProvider files  */
        $this->generateFile(
            'stubs/provider-steward.stub',
            [
                '{{ namespace }}'       => "{$namespace}\\App\\Providers",
                '{{ class }}'           => "{$stewardName}ServiceProvider",
                '{{ module }}'          => $stewardName,
                '{{ moduleLowerName }}' => strtolower($stewardName),

            ],
            "{$path}/app/Providers/StewardServiceProvider.php"
        );
        $this->generateFile(
            'stubs/provider-modules-hq.stub',
            [
                '{{ namespace }}'       => "{$namespace}\\App\\Providers",
                '{{ class }}'           => "ModulesHeadquarterServiceProvider",
                '{{ module }}'          => $stewardName,
                '{{ moduleLowerName }}' => strtolower($stewardName),

            ],
            "{$path}/app/Providers/ModulesHeadquarterServiceProvider.php"
        );

        /* Register Event ServiceProvider file  */
        $this->generateFile(
            'stubs/provider-event.stub',
            [
                '{{ namespace }}'       => "{$namespace}\\App\\Providers",
                '{{ class }}'           => "EventServiceProvider",
                '{{ moduleLowerName }}' => strtolower($stewardName),
                '{{ module }}'          => $stewardName,
            ],
            "{$path}/app/Providers/EventServiceProvider.php"
        );
        /* Register Abstract controller file  */
        $this->generateFile(
            'stubs/controller-abstract.stub',
            [
                '{{$namespace}}' => "{$namespace}\\App\\Http\\Controllers",
            ],
            "{$path}/app/Http/Controllers/Controller.php"
        );
        /* Register config file  */
        $this->generateFile(
            'stubs/config.stub',
            [
                '{{ module }}' => $stewardName,
            ],
            "{$path}/config/config.php"
        );
        /* Register JS file  */
        $this->generateFile(
            'stubs/js.stub',
            [],
            "{$path}/resources/js/app.js"
        );
        /* Register CSS file  */
        $this->generateFile(
            'stubs/css.stub',
            [],
            "{$path}/resources/css/app.css"
        );
        /* Register master blade file  */
        $this->generateFile(
            'stubs/view.stub',
            ['{{ quote }}' => Inspiring::quote()],
            "{$path}/resources/views/master.blade.php"
        );
        /* Register web route file  */
        $this->generateFile(
            'stubs/route-web.stub',
            ['{{ module }}' => strtolower($stewardName)],
            "{$path}/routes/web.php"
        );
        /* Register Seeder file */
        $this->generateFile(
            'stubs/seeder.stub',
            [
                '{{ module }}'    => strtolower($stewardName),
                '{{ namespace }}' => "{$namespace}\\Database\\Seeders",
                '{{ class }}'     => "{$stewardName}DatabaseSeeder",
            ],

            "{$path}/database/Seeders/{$stewardName}DatabaseSeeder.php"
        );
        /* Register info file  */
        $this->generateFile(
            'stubs/info.stub',
            [
                '{{ name }}'      => $stewardName,
                '{{ alias }}'     => strtolower($stewardName),
                '{{ providers }}' => str_replace('\\', '\\\\', "$namespace\App\Providers\\" . $stewardName . "ServiceProvider"),
            ],

            "{$path}/info.json"
        );

    }

    private function generateFile(string $stub, array $replacements, string $destination): void
    {
        $this->replaceStub($stub, $replacements, $destination);
        $relativePath = normalizeSlashPath(str_replace(base_path(), '', $destination));
        $this->components->twoColumnDetail("<fg=gray>  └─ $relativePath</>", "<fg=green>✓ DONE</>");

    }

    protected function getArguments(): array
    {
        return [
        ];
    }

}
