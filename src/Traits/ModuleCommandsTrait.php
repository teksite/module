<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Teksite\Module\Facade\Module;
use Teksite\Module\Services\ModuleServices;

trait ModuleCommandsTrait
{

    /**
     * @throws \Exception
     */
    protected function resolveStubPath($stub)
    {
        $path = app('module.stubs') . $stub;

        return file_exists($path) ? $path : throw new \Exception ($stub . 'isn not exist in the path: ', $path);
    }

    protected function setPath($relativePath, string $format = 'php'): string
    {
        $absolutePath = base_path($relativePath);

        $this->existOrCreate($relativePath);

        return str_replace('\\', '/', $relativePath) . '.' . $format;
    }

    protected function setNamespace($module, $name, $relative): string
    {
        $namespace = Lareon::moduleNamespace($module, $relative);
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
        return Str::lower($module ?? $this->argument('module'));
    }

    protected function viewPath($path= '')
    {
        $module = $this->argument('module');
        return Lareon::ModuleViewPath($module , $path);

    }

    protected function qualifyModel(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $thisNamespace = Str::finish($this->rootNamespace() ,'\\');
        $appNamespace = app()->getNamespace();
        $moduleNamespace = Lareon::moduleNamespace();

        if (Str::startsWith($model, $thisNamespace)) {
            return $model;
        }
        if (Str::startsWith($model, $appNamespace)) {
            return $model;
        }
        if (Str::startsWith($model, $moduleNamespace)) {
            return $model;
        }
        return Lareon::moduleNamespace($this->argument('module') , 'App\\Models\\'.$model);



    }

    protected function rootNamespace()
    {
        $module = $this->argument('module');

        return Lareon::moduleNamespace($module , 'App');
    }

    public function moduleNamespace(?string $module =null, string $path=null)
    {
       return Lareon::moduleNamespace($module , $path);

    }


}
