<?php
if (!function_exists('module_path')) {
    function module_path(string $name = null, ?string $path = null, bool $absolute = true): ?string
    {
        if (is_null($name)) {
            return config('lareon.module.path');
        }
        if (in_array($name, array_keys(config('modules.modules')))) {

            $absolutePath = $path
                ? config('lareon.module.path') . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $path
                : config('lareon.module.path') . DIRECTORY_SEPARATOR . $name;
            if (is_dir($absolutePath) || file_exists($absolutePath)) {
                return $absolute ? $absolutePath : str_replace(base_path(), '', $absolutePath);
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
            ? $moduleBaseNamespace .$path
            : $moduleBaseNamespace;
    }


}
