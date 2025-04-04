<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

if (!function_exists('module_path')) {
    /**
     * @param string|null $moduleName
     * @param string|null $path
     * @param bool $absolute
     * @return string|null
     */
    function module_path(?string $moduleName = null, ?string $path = null, bool $absolute = true ): ?string
    {
        $moduleName= Str::ucfirst($moduleName);
        $mainPath = config('module.main_path' ,'Lareon') . DIRECTORY_SEPARATOR .  config('module.module.path', 'Modules');

        $relativePath = $moduleName ? normalizePath($mainPath . '/' . $moduleName . ($path ? '/' . $path : '')) : $mainPath;
        return $absolute ? base_path($relativePath) : $relativePath;
    }

}


if (!function_exists('module_namespace')) {

    /**
     * @param string|null $moduleName
     * @param string|null $path
     * @return string
     */
    function module_namespace(?string $moduleName, ?string $path = null): string
    {
        $moduleBaseNamespace = config('module.module.namespace' ,'Lareon\Modules') . '\\'. ($moduleName ? Str::ucfirst($moduleName): '');

        $path=$path ? str_replace('/', '\\', $path) :null;

        return $path
            ? $moduleBaseNamespace .'\\'. $path
            : $moduleBaseNamespace;
    }
}



if (!function_exists('module_bootstrap_path')) {
    /**
     * @return string
     */
    function module_bootstrap_path(): string
    {
        return config('module.registration_file' , base_path('bootstrap').'/modules.php');
    }
}

if (!function_exists('get_module_bootstrap')) {

    /**
     * @param string|array $modules
     * @return array
     */
    function get_module_bootstrap(string|array $modules = ['*']): array
    {
        $bootstrapContent = File::exists(module_bootstrap_path()) ? require module_bootstrap_path() : [];

        $modules = is_array($modules) ? $modules : [$modules];

        if(in_array('*', $modules)) return $bootstrapContent;
        $moduleArray=[];
        foreach ($modules as $module) {
            if(in_array($module, array_keys($bootstrapContent))) {
                $moduleArray[$module] = $bootstrapContent[$module];
            }
        }
        return $moduleArray;

    }
}
