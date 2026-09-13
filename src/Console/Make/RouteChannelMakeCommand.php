<?php

namespace Teksite\Module\Console\Make;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Teksite\Module\Console\GeneratorModuleCommand;

class RouteChannelMakeCommand extends GeneratorModuleCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-route-channel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new broadcast channel file in modules or steward';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Broadcast';

    protected string $generatorType = 'file';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/route-channel.stub');
    }

    /**
     * @throws \Exception
     */
    protected function path(): string
    {
        return $this->routeDirectory();
    }

    protected function routeDirectory(string $path = ''): string
    {
        return  'routes' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * add extension to filename
     *
     * @param string $path
     * @return string
     */
    protected function prepareFile(string $path,): string
    {
        return $path.'.'.ltrim($this->option('file') ?? 'channel.php', '.');
    }

    /**
     * set replacements
     *
     * @return array [string $searchable , string $replace ]
     */
    protected function replacements(): array
    {
        return [];
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['file', null, InputOption::VALUE_OPTIONAL, 'The file name', 'channels.php'],
        ];
    }

    protected function needNameArgument(): bool
    {
        return false;
    }

    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of the module or steward'],
        ];
    }

    protected function getNameInput(): string
    {
        return $this->option('file') ?? 'channels.php';
    }

    protected function prepareToProcess(): void
    {
        if (!isBroadcastingInstalled()) throw new \Exception('you should install broadcasting package first');
    }
}
