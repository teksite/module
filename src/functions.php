<?php

use Illuminate\Support\Str;

if (!function_exists('module_path')) {
    function module_path(string $moduleName = null, ?string $path = null, bool $absolute = true ): ?string
    {
        $moduleName= Str::ucfirst($moduleName);
        $mainPath = config('module.main_path' ,'Lareon') . DIRECTORY_SEPARATOR .  config('module.module.path', 'Modules');

        $relativePath = $moduleName ? normalizePath($mainPath . '/' . $moduleName . ($path ? '/' . $path : '')) : $mainPath;
        return $absolute ? base_path($relativePath) : $relativePath;
    }

}


if (!function_exists('module_namespace')) {

    function module_namespace(string $moduleName, ?string $path = null): string
    {
        $moduleBaseNamespace = config('module.module.namespace' ,'Lareon\Modules') . '\\'. Str::ucfirst($moduleName);

        $path=$path ? str_replace('/', '\\', $path) :null;

        return $path
            ? $moduleBaseNamespace .'\\'. $path
            : $moduleBaseNamespace;
    }
}

