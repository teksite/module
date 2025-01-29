<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\select;

class TestMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    protected $signature = 'module:make-test {name} {module}
         {--f|force : Create the test even if the test already exists }
         {--u|unit : Create a unit test }
         {--pest : Create a Pest test }
         {--phpunit : Create a PHPUnit test }
    ';


    protected $description = 'Create a new seeder in the specific module';

    protected $type = 'Tests';

    protected function getStub()
    {
        $suffix = $this->option('unit') ? '.unit.stub' : '.stub';

        return $this->usingPest()
            ? $this->resolveStubPath('/pest'.$suffix)
            : $this->resolveStubPath('/test'.$suffix)
            ;
    }


    protected function getPath($name)
    {
        $module = $this->argument('module');
        return $this->setPath($name,'php');

    }


    protected function qualifyClass($name)
    {
        $module = $this->argument('module');

        if ($this->option('unit')) {
            return $this->setNamespace($module , $name, '\\Tests\\Unit');
        } else {
            return $this->setNamespace($module , $name, '\\Tests\\Feature');
        }
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->option('unit')) {
            return '\Unit';
        } else {
            return '\Feature';
        }
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
        $this->error("The module '" . $module . "' does not exist.");
        return 1;
    }


    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $type = select('Which type of test would you like?', [
            'feature' => 'Feature',
            'unit' => 'Unit',
        ]);

        match ($type) {
            'feature' => null,
            'unit' => $input->setOption('unit', true),
        };
    }

    /**
     * Determine if Pest is being used by the application.
     *
     * @return bool
     */
    protected function usingPest()
    {
        if ($this->option('phpunit')) {
            return false;
        }

        return $this->option('pest') ||
            (function_exists('\Pest\\version') &&
                file_exists(base_path('tests') . '/Pest.php'));
    }


}
