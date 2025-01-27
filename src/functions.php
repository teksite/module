<?php
if (!function_exists('module_path')) {
    function module_path(string $moduleName = null, ?string $path = null, bool $absolute = true ): ?string
    {
        if (is_null($moduleName)) {
            return $absolute ? base_path(config('moduleconfigs.path')) : config('moduleconfigs.path');
        }
        if (in_array($moduleName, array_keys(config('modules.modules')))) {
            $relative = $path
                ? config('moduleconfigs.main_path') . DIRECTORY_SEPARATOR . config('moduleconfigs.module.directory') . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . $path
                : config('moduleconfigs.main_path') . DIRECTORY_SEPARATOR . config('moduleconfigs.module.directory') . DIRECTORY_SEPARATOR . $moduleName;
            if ($relative && is_dir(base_path($relative))) {
                return $absolute ? base_path($relative) : $relative;
            }

        }
        return null;
    }

}


if (!function_exists('module_namespace')) {

    function module_namespace(string $moduleName, ?string $path = null): string
    {
        // Add any additional logic for your module namespaces
        $moduleBaseNamespace = config('lareon.module.namespace') . $moduleName . '\\';
        return $path
            ? $moduleBaseNamespace . $path
            : $moduleBaseNamespace;
    }
}

if (!function_exists('normalizePath')) {
    function normalizePath(string $path): string
    {
        // Replace all "/" and "\" with DIRECTORY_SEPARATOR
        $normalizedPath = str_replace(['/', '\\', '/\\', '\\/'], DIRECTORY_SEPARATOR, $path);

        // Ensure the path ends with DIRECTORY_SEPARATOR
        return rtrim($normalizedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
