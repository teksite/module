<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Traits\ModuleCommandTrait;
use Teksite\Module\Traits\ModuleNameValidator;


class EnumMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator , ModuleCommandTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:make-enum {name} {module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view enum in the specific module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Enum';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/enum.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name): string
    {
        $module = $this->argument('module');
        return $this->setDefaultPath($module, $name ,'/App/Enums/');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name): string
    {
        $module = $this->argument('module');
        return $this->setDefaultNamespace($module,$name , '\\App\\Enums');
    }

    public function handle(): bool|int|null
    {
        $module = $this->argument('module');
        [$isValid, $suggestedName] = $this->validateModuleName($module);
        if ($isValid) return parent::handle();

        if ($suggestedName && $this->confirm("Did you mean '{$suggestedName}'?")) {
            $this->input->setArgument('module', $suggestedName);
            return parent::handle();
        }
        $this->error("The module '".$module."' does not exist.");
        return 1;
    }
}
