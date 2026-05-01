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

        $composerContent = file_get_contents($composerPath);
        $composer = json_decode($composerContent, true);
        $autoloadPsr4 = data_get($composer, 'autoload.psr-4', []);

        if (!is_array($autoloadPsr4)) $autoloadPsr4 = [];

        $normalizedInputPath = trim(str_replace('\\', '/', $path), '/');

        $path = str_replace('\\', '/', $path);

        foreach ($autoloadPsr4 as $namespacePrefix => $baseDir) {
            $normalizedBaseDir = trim(str_replace('\\', '/', $baseDir), '/');

            if (str_starts_with($normalizedInputPath ,$normalizedBaseDir)) {
                $remainingPath = substr($normalizedInputPath, strlen($normalizedBaseDir));
                $remainingPath = trim($remainingPath, '/');

                $finalNamespace = rtrim($namespacePrefix, '\\') . '\\'; // اطمینان از وجود بک‌اسلش در انتهای پیشوند namespace


                if (!empty($remainingPath)) {
                    $finalNamespace .= str_replace('/', '\\', $remainingPath);
                }

                $this->base_namespace = $finalNamespace;
                return $finalNamespace;

            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

}
