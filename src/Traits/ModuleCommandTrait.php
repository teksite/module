<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait ModuleCommandTrait
{
    protected function getLowerNameModule()
    {
        return Str::lower($this->argument('module'));
    }
    protected function setDefaultPath($module, $name, $relative, $format = 'php'): string
    {
        $defaultPath = Str::finish(config('lareon.module.path'), '/');
        $CommandPath = Str::start(Str::finish($relative, '/'), '/');
        $baseDir = $defaultPath . $module . $CommandPath;
        if (!dirname($baseDir)) {
            mkdir(dirname($baseDir), 0755, true, true);
        }

        $relativePath = Str::replaceFirst($baseDir, '', $name);

        $absolutePath = base_path($relativePath);

        return str_replace('\\', '/', $absolutePath) . '.' . $format;
    }

    protected function setDefaultNamespace($module, $name, $relative): string
    {
        $defaultNamespace = config('lareon.module.namespace');
        $relativeNamespace = Str::start($relative, '\\');

        $namespace = $defaultNamespace . $module . $relativeNamespace;
        return trim($namespace, '\\') . '\\' . str_replace('/', '\\', $name);
    }


    protected function rootNamespace()
    {
        $module = $this->argument('module');
        return config('lareon.module.namespace') . $module . '\\App\\';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
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

}
