<?php

namespace Teksite\Module\Console\Make\traits;

use RuntimeException;
use Teksite\Module\Contract\TestGenerator;
use Teksite\Module\Exception\FileNotFoundException;

trait ModuleGeneratorTrait
{
    protected string $base_namespace;

    /**
     * Cache of decoded composer.json content per module, to avoid re-reading
     * and re-decoding the same file multiple times during a single command.
     *
     * @var array<string, array>
     */
    private array $composerCache = [];

    /**
     * Get the application namespace.
     *
     * @param string $module
     * @param string $path
     * @return string
     * @throws FileNotFoundException
     */
    public function getModuleDirNamespace(string $module, string $path,): string
    {
        $composer = $this->getComposer($module);

        $autoloadPsr4 = $this instanceof TestGenerator
            ? data_get($composer, 'autoload-dev.psr-4', [])
            : data_get($composer, 'autoload.psr-4', []);

        return $this->setBaseNameSpace($autoloadPsr4, $path);
    }


    /**
     * @param mixed  $autoloadPsr4
     * @param string $path
     * @return string
     */
    private function setBaseNameSpace(mixed $autoloadPsr4, string $path,): string
    {
        if (!is_array($autoloadPsr4)) $autoloadPsr4 = [];

        $normalizedInputPath = trim(str_replace('\\', '/', $path), '/');

        foreach ($autoloadPsr4 as $namespacePrefix => $baseDir) {
            $normalizedBaseDir = trim(str_replace('\\', '/', $baseDir), '/');

            if (str_starts_with($normalizedInputPath, $normalizedBaseDir)) {
                $remainingPath = substr($normalizedInputPath, strlen($normalizedBaseDir));
                $remainingPath = trim($remainingPath, '/');

                $finalNamespace = rtrim($namespacePrefix, '\\').'\\';

                if (!empty($remainingPath))  $finalNamespace .= str_replace('/', '\\', $remainingPath);

                $this->base_namespace = $finalNamespace;

                return $finalNamespace;
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

    /**
     * @param string $module
     * @return mixed
     * @throws FileNotFoundException
     * @throws \Exception
     */
    private function getComposer(string $module,): mixed
    {
        if (array_key_exists($module, $this->composerCache)) return $this->composerCache[$module];

        $modulePath = modulePath($module);

        $composerPath = $modulePath.DIRECTORY_SEPARATOR.'composer.json';

        if (!file_exists($composerPath)) throw new FileNotFoundException('composer.json not found.');

        $composerContent = file_get_contents($composerPath);

        return $this->composerCache[$module] = json_decode($composerContent, true);
    }

}
