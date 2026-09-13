<?php

namespace Teksite\Module\Providers\Support\Concerns;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


trait PublishesModuleConfig
{
    /**
     * Scan $configPath for *.php config files, publish them, and merge each
     * one into the application config under a key derived from $keyPrefix.
     *
     * @param string $configPath absolute path to the module/steward config directory
     * @param string $keyPrefix  the base config key (usually the lowercase module name)
     * @return void
     */
    protected function publishModuleConfig(string $configPath, string $keyPrefix,): void
    {
        if (app()->configurationIsCached()) return;


        if (!is_dir($configPath)) return;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;

            $relative = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKey = str_replace('.php', '', str_replace(DIRECTORY_SEPARATOR, '.', $relative));

            $segments = explode('.', $keyPrefix.'.'.$configKey);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) $normalized[] = $segment;
            }

            $isRootConfig = $relative === 'config.php';
            $key = $isRootConfig ? $keyPrefix : implode('.', $normalized);

            $publishPath = $isRootConfig ? config_path($keyPrefix.'.php') : config_path($relative);

            $this->publishes([$file->getPathname() => $publishPath], 'config');
            $this->mergeModuleConfigFrom($file->getPathname(), $key);
        }
    }

    /**
     * Merge config from the given path recursively into the given key.
     */
    protected function mergeModuleConfigFrom(string $path, string $key,): void
    {
        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }

}
