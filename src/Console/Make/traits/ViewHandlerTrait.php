<?php

namespace Teksite\Module\Console\Make\traits;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ViewHandlerTrait
{

    /**
     * Get the first view directory path from the application configuration.
     *
     * @param string $path
     * @return string
     */
    protected function viewDirectory(string $path = ''): string
    {
        $views = $this->getModuleInput() === 'Steward' ? config('modules.steward.view', 'resources/views') : config('modules.module.view', 'resources/views');

        return $views . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }


    /**
     * Get the view name relative to the view path.
     *
     * @return string view
     */
    protected function viewPath($starterDir = null): string
    {
        return ($starterDir ? "$starterDir." : '') . (new Collection($this->getViewArray()))
                ->map(fn($segment) => Str::kebab($segment))
                ->implode('.');
    }

    protected function viewPathDir($starterDir = null): string
    {
        return ($starterDir ? "$starterDir\\" : '') . (new Collection($this->getViewArray()))
                ->map(fn($segment) => Str::kebab($segment))
                ->implode('\\');
    }


    protected function getViewArray(): array
    {
        $segments = explode(DIRECTORY_SEPARATOR, $this->getNameInput());

        $name = array_pop($segments);

        $path = [
            ...$segments,
        ];
        $path[] = $name;

        return $path;
    }

    /**
     * Write the Markdown template for the mailable.
     *
     * @return void
     * @throws \Exception
     */
    protected function writeMarkdownTemplate($module, ?string $dir = null, string $extension = '.blade.php'): void
    {

        $stubPath = $this->resolveStubPath('stubs/markdown.stub');

        $filePath = module_view_path($module , false) . DIRECTORY_SEPARATOR. "\\" . $this->viewPathDir($dir);
        $filepath = normalizeSlashPath($filePath) . $extension;

        if ($this->files->exists($filepath)) {
            $this->components->error(sprintf('%s [%s] already exists.', 'View', $filepath));
            return;
        }

        $this->files->ensureDirectoryExists(dirname($filepath));


        $content = str_replace(
            ['{{ quote }}' , '{{quote}}'],
            Inspiring::quotes()->random(),
            file_get_contents($stubPath)
        );

        $this->files->put($filepath, $content);

        $this->components->twoColumnDetail("$module| the markdown file has been created.", $filepath);


    }

    /**
     * Write the Blade template for the mailable.
     *
     * @throws \Exception
     */
    protected function writeView($module, ?string $dir = null, string $extension = '.blade.php'): void
    {

        $stubPath = $this->resolveStubPath('stubs/view.stub');

        $filePath = module_view_path($module , false) . DIRECTORY_SEPARATOR. "\\" . $this->viewPathDir($dir);
        $filepath = normalizeSlashPath($filePath) . $extension;

        if ($this->files->exists($filepath)) {
            $this->components->error(sprintf('%s [%s] already exists.', 'View', $filepath));
            return;
        }

        $this->files->ensureDirectoryExists(dirname($filepath));


        $content = str_replace(
            ['{{ quote }}' , '{{quote}}'],
            Inspiring::quotes()->random(),
            file_get_contents($stubPath)
        );

        $this->files->put($filepath, $content);

        $this->components->twoColumnDetail("$module| the view file has been created.", $filepath);
    }


}
