<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class AttributeMakeCommand extends GeneratorModuleCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-attribute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new PHP Attribute class in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Attribute';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/attribute.stub');
    }

    protected function path(): string
    {
        return 'app/Attributes';
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        $target = strtolower($this->option('target')) ?? 'class';

        $map = [
            'class'    => 'Attribute::TARGET_CLASS',
            'property' => 'Attribute::TARGET_PROPERTY',
            'method'   => 'Attribute::TARGET_METHOD',
            'all'      => 'Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD',
        ];

        $targetValue = $map[$target] ?? $map['class'];

        $paramsOption = trim($this->option('params'));
        $paramsCode = '';
        $assignCode = '';

        if ($paramsOption !== '') {
            $params = explode(',', $paramsOption);
            $pairs = [];

            foreach ($params as $param) {
                [$name, $type] = array_map('trim', explode(':', $param));
                $pairs[] = "public {$type} \${$name}";
            }

            $paramsCode = implode(', ', $pairs);
            $assignCode = '// custom attributes assigned automatically by PHP';
        }


        return [
            '{{ target }}'=>$targetValue,
            '{{ params }}'=>$paramsCode,
            '{{ assign }}'=>$assignCode,
        ];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['target', null, InputOption::VALUE_OPTIONAL, 'The target type for the attribute (class, property, method, all)', 'class'],
            ['params', null, InputOption::VALUE_OPTIONAL, 'Constructor parameters separated by comma, e.g. "value:string,enabled:bool"', ''],
        ];
    }
}
