<?php

if (!function_exists('normalizePath')) {
    /**
     * @param string $path
     * @return string
     */
    function normalizeSlashPath(string $path): string
    {
        // Replace all "/" and "\" with DIRECTORY_SEPARATOR
        $normalizedPath = str_replace(['/' ,'//', '\\', '/\\', '\\/' ,'\\\\'], DIRECTORY_SEPARATOR, $path);

        // Ensure the path ends with DIRECTORY_SEPARATOR
        return rtrim($normalizedPath, DIRECTORY_SEPARATOR);
    }
}



if (!function_exists('humanReadableVarExport')) {
    /**
     * @param $expression
     * @param bool $return
     * @return array|string|string[]|void|null
     */
    function humanReadableVarExport($expression, bool $return = false)
    {

        $export = var_export($expression, true);
        $patterns = [
            "/array \(/"                       => '[',
            "/^([ ]*)\)(,?)$/m"                => '$1]$2',
            "/=>[ ]?\n[ ]+\[/"                 => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);

        if ((bool)$return) return $export; else echo $export;
    }
}
