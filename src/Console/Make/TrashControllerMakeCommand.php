<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class TrashControllerMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new trash controller class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->option('api')
            ? $this->resolveStubPath('stubs/trash.controller.api.stub')
            : $this->resolveStubPath('stubs/trash.controller.stub');
    }

    protected function path(): string
    {
        return 'app/HTTP/Controllers';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        $defaultControllerPath = module_path($this->getModuleInput() , 'app\Http\Controllers\Controller.php');
        if (file_exists($defaultControllerPath)) {
            $defaultController = $this->defaultNamespaceController($defaultControllerPath);
        } else {
            $defaultController = 'App\Http\Controllers';
        }

        return ['{{ defaultController }}' => $defaultController];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, "Create the class or file even if the {$this->type} already exists"],
            ['api', null, InputOption::VALUE_NONE, 'Generate an api controller class'],
        ];
    }

    private function defaultNamespaceController(string $path): string
    {
        $contents = file_get_contents($path);

        preg_match('/^namespace\s+(.+?);/m', $contents, $nsMatch);
        preg_match('/^abstract class\s+(\w+)/m', $contents, $classMatch);
        return $nsMatch[1] . '\\' . $classMatch[1];

    }
}
