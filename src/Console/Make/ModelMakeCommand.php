<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class ModelMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create aa new Eloquent model class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        if ($this->option('pivot')) {
            return $this->resolveStubPath('stubs/model.pivot.stub');
        }

        if ($this->option('morph-pivot')) {
            return $this->resolveStubPath('stubs/model.morph-pivot.stub');
        }

        return $this->resolveStubPath('stubs/model.stub');
    }

    protected function path(): string
    {
        return 'app/Models';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        $replacements = [];
        if ($this->option('factory') || $this->option('all')) {
            $modelPath = Str::of($this->argument('name'))->studly()->replace('/', '\\')->toString();

            $factoryNamespace = $this->module_namespace($this->getModuleInput()) . '\\Database\\Factories\\' . $modelPath . 'Factory';

            $factoryCode = <<<EOT
            /** @use HasFactory<$factoryNamespace> */
                use HasFactory;
            EOT;

            $replacements['{{ factory }}'] = $factoryCode;
            $replacements['{{ factoryImport }}'] = 'use Illuminate\Database\Eloquent\Factories\HasFactory;';
        } else {
            $replacements['{{ factory }}'] = '//';
            $replacements["{{ factoryImport }}\n"] = '';
            $replacements["{{ factoryImport }}\r\n"] = '';
        }

        return $replacements;

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['morph-pivot', null, InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom polymorphic intermediate table model'],
            ['policy', null, InputOption::VALUE_NONE, 'Create a new policy for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an API resource controller'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Create new form request classes and use them in the resource controller'],
        ];
    }

    protected function handler(): void
    {
        parent::handler();

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        } elseif ($this->option('requests')) {
            $this->createFormRequests();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }
    }

    /**
     * Create a model factory for the model.
     *
     * @return void
     */
    protected function createFactory(): void
    {
        $factory = Str::studly($this->argument('name'));

        $this->call('module:make-factory', [
            'name'    => "{$factory}Factory",
            'module'  => $this->getModuleInput(),
            '--model' => $this->filename,
        ]);
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('module:make-migration', [
            'name'     => "create_{$table}_table",
            'module'   => $this->getModuleInput(),
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder file for the model.
     *
     * @return void
     */
    protected function createSeeder(): void
    {
        $seeder = Str::studly(class_basename($this->argument('name')));

        $this->call('module:make-seeder', [
            'name'   => "{$seeder}Seeder",
            'module' => $this->getModuleInput(),

        ]);
    }

    /**
     * Create a controller for the model.
     *
     * @return void
     */
    protected function createController(): void
    {
        $controller = Str::studly(class_basename($this->argument('name')));

        $modelName = $this->filename;

        $this->call('module:make-controller', array_filter([
            'name'       => "{$controller}Controller",
            'module'     => $this->getModuleInput(),
            '--model'    => $this->option('resource') || $this->option('api') ? $modelName : null,
            '--api'      => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
        ]));
    }

    /**
     * Create the form requests for the model.
     *
     * @return void
     */
    protected function createFormRequests(): void
    {
        $request = Str::studly(class_basename($this->argument('name')));

        $this->call('module:make-request', [
            'name'   => "Store{$request}Request",
            'module' => $this->getModuleInput(),
        ]);

        $this->call('module:make-request', [
            'name'   => "Update{$request}Request",
            'module' => $this->getModuleInput(),
        ]);
    }

    /**
     * Create a policy file for the model.
     *
     * @return void
     */
    protected function createPolicy()
    {
        $policy = Str::studly(class_basename($this->argument('name')));

        $this->call('module:make-policy', [
            'name'    => "{$policy}Policy",
            'module'  => $this->getModuleInput(),
            '--model' => $this->filename,
        ]);
    }
}
