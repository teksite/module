<?php

namespace Teksite\Module\Console\Make\traits;

use RuntimeException;

trait ModuleGeneratorTrait
{
    protected string $base_namespace;

    /**
     * Get the application namespace.
     *
     * @param string $module
     * @param string $path
     * @return string
     */
    public function getModuleNamespace(string $module, string $path): string
    {
        $modulePath = $module === 'steward' ? steward_path() : module_path($module);
        $composerPath = $modulePath . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerPath)) throw new RuntimeException('composer.json not found.');

        $composer = json_decode(file_get_contents($composerPath), true);
        $autoload = data_get($composer, 'autoload.psr-4', []);

        $path = str_replace('\\', '/', $path);
        $relativePath = '/' . trim($this->path(), '/\\') . '/' ;

        foreach ($autoload as $namespacePrefix => $baseDir) {
            $baseDir ='/'. rtrim(str_replace('\\', '/', $baseDir), '/') . '/';
            if (str_starts_with($relativePath, $baseDir)) {
                $namespace = preg_replace($baseDir, $namespacePrefix, $path, 1);
                $namespace = normalizeSlashNamespace($namespace);
                $this->base_namespace = $namespace;
                return $namespace;

            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

}
