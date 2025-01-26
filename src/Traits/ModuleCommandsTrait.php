<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Teksite\Module\Facade\Module;

trait ModuleCommandsTrait
{

    protected function resolveStubPath($stub)
    {
        $path = app('module.stubs') . $stub;

        return file_exists($path) ? $path : throw new \Exception ($stub . ': diwsnt exist in the path: ', $path);
    }

    protected function setPath($relativePath, string $format = 'php'): string
    {
        $absolutePath = base_path($relativePath);

        $this->existOrCreate($relativePath);

        return str_replace('\\', '/', $relativePath) . '.' . $format;
    }


    protected function setNamespace($module, $name, $relative): string
    {
        $namespace = Module::ModuleNamespace($module, $relative);
        return $namespace . '\\' . str_replace('/', '\\', $name);
    }

    protected function existOrCreate(string $path): void
    {
        if (!dirname($path)) {
            mkdir(dirname($path), 0755, true, true);
        }
    }

    protected function getLowerNameModule(?string $module = null): string
    {
        return Str::lower($module ?? $this->argument('name'));
    }

    protected function viewPath($path = '')
    {
        $module = $this->argument('module');

        $views = config('lareon.module.view_path');
        $pathModule = config('lareon.module.path');

        $viewPath = $views ?
            $pathModule .DIRECTORY_SEPARATOR. $module . DIRECTORY_SEPARATOR . $views :
            resource_path('views');

        return $viewPath . ( $path ? DIRECTORY_SEPARATOR . $path : $path);

    }

    protected function rootNamespace()
    {
        $module = $this->argument('module');

        return Module::ModuleNamespace($module , 'App');
    }


}
