<?php

namespace Teksite\Module\Services;


use Illuminate\Support\Facades\File;

class ModuleServices
{
    private string $bootstrapFilePath;

    public function __construct()
    {
        $this->bootstrapFilePath = module_bootstrap_path();
    }

    /**
     * @param string|null $moduleName
     * @param string|null $path
     * @param bool        $absolute
     * @return string
     * @throws \Exception
     */
    public function modulePath(?string $moduleName = null, ?string $path = null, bool $absolute = true): string
    {
        return modulePath($moduleName, $path, $absolute);
    }

    /**
     * @throws \Exception
     */
    public function stewardPath(?string $path = null, bool $absolute = true): string
    {
        return modulePath('Steward', $path, $absolute);
    }

    /**
     * @param string|null $moduleName
     * @return string
     * @throws \Exception
     */
    public function moduleNamespace(string $moduleName = null): string
    {
        return module_namespace($moduleName);
    }

    /**
     * @return string
     */
    public function stewardNamespace(): string
    {
        return steward_namespace();
    }

    /**
     * get all modules in bootstrap modules file
     *
     * @return array|string[]
     */
    public function all(bool $steward = false): array
    {
        $modules = array_keys(get_modules());

        return ($steward && $this->isStewardInstalled()) ? array_merge($modules, ['Steward']) : $modules;
    }

    /**
     * @return array|string[]
     */
    public function availableModules(): array
    {
        return get_modules();
    }


    /**
     * @param $moduleName
     * @return bool
     */
    public function isRegistered($moduleName): bool
    {
        return in_array($moduleName, array_keys(get_modules()));

    }

    /**
     * @param bool $onlyName
     * @return array
     */
    public function enables(bool $onlyName = false): array
    {
        return get_enabled_modules($onlyName);
    }

    /**
     * @param bool $onlyName
     * @return array
     */
    public function disables(bool $onlyName = false): array
    {
        return get_disabled_modules($onlyName);
    }

    /**
     * @param string $moduleName
     * @return bool|null
     */
    public function isEnabled(string $moduleName): null|bool
    {
        if (!$this->isRegistered($moduleName)) return null;
        return in_array($moduleName, array_keys($this->enables()));
    }

    /**
     * @param string $moduleName
     * @return bool|null
     */
    public function isDisable(string $moduleName): null|bool
    {
        if (!$this->isRegistered($moduleName)) return null;
        return in_array($moduleName, array_keys($this->disables()));
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function exists(string $moduleName): bool
    {
        return in_array($moduleName, $this->all());
    }


    /**
     * @param string $moduleName
     * @return string|null
     */
    public function getType(string $moduleName): ?string
    {
        return get_module_type($moduleName);
    }


    /**
     * @param string       $moduleName
     * @param string|array $key
     * @return mixed
     * @throws \Exception
     */
    public function info(string $moduleName, string|array $key = ['*']): mixed
    {
        $key = is_array($key) ? $key : [$key];

        $path = $this->modulePath($moduleName, 'info.json');

        if (file_exists($path)) {
            $info = json_decode(file_get_contents($path), true);
            $info['isEnabled'] = $this->isEnabled($moduleName);
            $info['type'] = $this->getType($moduleName);
        } else {
            $info = [];
        }
        if (in_array('*', $key)) return $info;

        return collect($info)->filter(function ($item, $index) use ($key) {
            return in_array($index, $key);
        })->toArray();
    }

    /**
     * @param $moduleName
     * @return int
     * @throws \Exception
     */
    public function enable($moduleName): int
    {
        if (!$this->isRegistered($moduleName)) throw new \Exception('the module is not registered or installed');

        if ($this->isEnabled($moduleName)) return 1;

        $registeredModules = get_modules();

        $registeredModules[$moduleName]['active'] = true;

        File::put(
            $this->bootstrapFilePath,
            '<?php return ' . humanReadableVarExport($registeredModules, true) . ';'
        );

        reset_modules_cache();

        return 1;
    }

    /**
     * @param $moduleName
     * @return int
     * @throws \Exception
     */
    public function disable($moduleName): int
    {
        if (!$this->isRegistered($moduleName)) throw new \Exception('the module is not registered or installed');

        if ($this->isDisable($moduleName)) return 0;

        $registeredModules = get_modules();

        $registeredModules[$moduleName]['active'] = false;

        File::put(
            $this->bootstrapFilePath,
            '<?php return ' . humanReadableVarExport($registeredModules, true) . ';'
        );

        reset_modules_cache();

        return 0;
    }

    /**
     * @return bool
     */
    public function isStewardInstalled(): bool
    {
        return isStewardInstalled();
    }

}
