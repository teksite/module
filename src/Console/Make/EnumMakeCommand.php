<?php

namespace Teksite\Module\Console\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teksite\Module\Traits\ModuleCommandsTrait;
use Teksite\Module\Traits\ModuleNameValidator;
use function Laravel\Prompts\select;


class EnumMakeCommand extends GeneratorCommand
{
    use ModuleNameValidator, ModuleCommandsTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:make-enum {name} {module}
         {--f|force : Create the class even if the cast already exists }
         {--s|string : Generate a string backed enum. }
         {--i|int : Generate an integer backed enum. }
         ';

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
     * @throws \Exception
     */
    protected function getStub()
    {
        if ($this->option('string') || $this->option('int')) {
            return $this->resolveStubPath('/enum.backed.stub');
        }
        return $this->resolveStubPath('/enum.stub');
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
        return $this->setPath($name,'php');
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

        return match (true) {
            !!module_path($module ,'App\Enums') => $this->setNamespace($module,$name , '\\App\\Enums'),
            !!module_path($module ,'App\Enumerations') => $this->setNamespace($module,$name , '\\App\\Enumerations'),
            default => $this->setNamespace($module,$name , '\\App\\Enums'),
        };
    }


    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        if ($this->option('string') || $this->option('int')) {
            return str_replace(
                ['{{ type }}'],
                $this->option('string') ? 'string' : 'int',
                parent::buildClass($name)
            );
        }

        return parent::buildClass($name);
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $type = select('Which type of enum would you like?', [
            'pure' => 'Pure enum',
            'string' => 'Backed enum (String)',
            'int' => 'Backed enum (Integer)',
        ]);

        if ($type !== 'pure') {
            $input->setOption($type, true);
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
}
