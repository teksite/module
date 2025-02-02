<?php
if (!function_exists('module_path')) {
    function module_path(string $moduleName = null, ?string $path = null, bool $absolute = true ): ?string
    {
        $mainPath = config('moduleconfigs.main_path') && config('moduleconfigs.module.path')
            ? config('moduleconfigs.main_path') . DIRECTORY_SEPARATOR . config('moduleconfigs.module.path')
            : "Lareon/Modules";

        $relativePath = $moduleName ? normalizePath($mainPath . '/' . $moduleName . ($path ? '/' . $path : '')) : $mainPath;
        return $absolute ? base_path($relativePath) : $relativePath;
    }

}


if (!function_exists('module_namespace')) {

    function module_namespace(string $moduleName, ?string $path = null): string
    {
        // Add any additional logic for your module namespaces
        $moduleBaseNamespace = config('moduleconfigs.module.namespace') . $moduleName . '\\';
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
        return rtrim($normalizedPath, DIRECTORY_SEPARATOR);
    }
}
