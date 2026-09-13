<?php

if (!function_exists('normalizeSlashPath')) {
    /**
     * @param string $path
     * @param string $separator
     * @return string
     */
    function normalizeSlashPath(string $path, string $separator = DIRECTORY_SEPARATOR,): string
    {
        // Collapse any run of "/" and "\" into a single DIRECTORY_SEPARATOR
        $normalizedPath = preg_replace('#[\\\\/]+#', DIRECTORY_SEPARATOR, $path);

        // Trim a trailing separator
        return rtrim($normalizedPath, $separator);
    }
}

if (!function_exists('normalizeSlashNamespace')) {
    /**
     *  Normalize any mix of "/" and "\" into a valid PHP namespace separator ("\").
     *
     * @param string $namespace
     * @return string
     */
    function normalizeSlashNamespace(string $namespace,): string
    {
        // Replace all "/" and "\" with DIRECTORY_SEPARATOR
        $normalizedNamespace = preg_replace('#[\\\\/]+#', '\\', $namespace);

        // Ensure the path ends with DIRECTORY_SEPARATOR
        return rtrim($normalizedNamespace, '\\');
    }
}


if (!function_exists('humanReadableVarExport')) {
    /**
     * @param      $expression
     * @param bool $return
     * @return array|string|string[]|void|null
     */
    function humanReadableVarExport($expression, bool $return = false,)
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


if (!function_exists('isBroadcastingInstalled')) {
    /**
     * Whether Laravel's broadcasting is actually available to use.
     *
     * Starting with Laravel 11, broadcasting is no longer bundled by
     * default - it's a separate, optional install
     * (`php artisan install:broadcasting`, package `illuminate/broadcasting`).
     * Calling `Broadcast::channel(...)` when it isn't installed would throw
     * a fatal "class not found" error, so anything that loads a module's
     * routes/channels.php must check this first.
     *
     * @return bool
     */
    function isBroadcastingInstalled(): bool
    {
        return class_exists(\Illuminate\Support\Facades\Broadcast::class)
            && class_exists(\Illuminate\Broadcasting\BroadcastManager::class);
    }
}
