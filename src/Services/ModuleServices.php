<?php

namespace Teksite\Module\Services;


use Illuminate\Support\Facades\File;

class ModuleServices
{
    private string $bootstrapFilePath;
    private array $bootstrapFile;

    public function __construct()
    {
        $this->bootstrapFilePath = config('modules.registration_modules_file', base_path('bootstrap') . '/modules.php');
        $this->bootstrapFile = file_exists($this->bootstrapFilePath) ? require $this->bootstrapFilePath : [];
    }

    /**
     * @param string|null $moduleName
     * @param string|null $path
     * @param bool $absolute
     * @return string
     */
    public function modulePath(?string $moduleName = null, ?string $path = null, bool $absolute = true): string
    {
        return module_path($moduleName, $path, $absolute);
    }

    public function stewardPath(?string $path = null, bool $absolute = true): string
    {
        return steward_path($path, $absolute);
    }

    /**
     * @param string|null $moduleName
     * @return string
     */
    public function moduleNamespace(string $moduleName = null): string
    {
        return module_namespace($moduleName);
    }

    /**
     * @param string|null $moduleName
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
    public function registeredModules(): array
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
     * @param string $moduleName
     * @param string|array $key
     * @return mixed
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
        if (!$this->isRegistered($moduleName)) return throw new \Exception('the module is not registered or installed');

        if ($this->isEnabled($moduleName)) return 1;

        $registeredModules = $this->bootstrapFile;
        $registeredModules[$moduleName]['active'] = true;

        File::put(
            $this->bootstrapFilePath,
            '<?php return ' . humanReadableVarExport($registeredModules, true) . ';'
        );
        return 1;
    }

    /**
     * @param $moduleName
     * @return int
     * @throws \Exception
     */
    public function disable($moduleName): int
    {
        if (!$this->isRegistered($moduleName)) return throw new \Exception('the module is not registered or installed');

        if ($this->isDisable($moduleName)) return 0;

        $registeredModules = $this->bootstrapFile;
        $registeredModules[$moduleName]['active'] = false;

        File::put(
            $this->bootstrapFilePath,
            '<?php return ' . humanReadableVarExport($registeredModules, true) . ';'
        );
        return 0;
    }

    /**
     * @return bool
     */
    public function isStewardInstalled(): bool
    {
        return is_dir($this->stewardPath()) && class_exists(steward_namespace().'\\App\\Providers\\StewardServiceProvider\\StewardServiceProvider');
    }

}
