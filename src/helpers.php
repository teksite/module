<?php

if (!function_exists('normalizePath')) {
    /**
     * @param string $path
     * @return string
     */
    function normalizeSlashPath(string $path): string
    {
        // Replace all "/" and "\" with DIRECTORY_SEPARATOR
        $normalizedPath = str_replace(['/', '\\', '/\\', '\\/'], DIRECTORY_SEPARATOR, $path);

        // Ensure the path ends with DIRECTORY_SEPARATOR
        return rtrim($normalizedPath, DIRECTORY_SEPARATOR);
    }
}
