<?php

namespace Teksite\Module\Services;


use Illuminate\Support\Facades\File;

class ModuleServices
{
    public function __construct()
    {
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

    /**
     * @param string|null $moduleName
     * @param string|null $path
     * @return string
     */
    public function moduleNamespace(string $moduleName = null, ?string $path = null): string
    {
        return module_namespace($moduleName, $path);
    }


    /**
     * @return array|string[]
     */
    public function all(): array
    {
        $bootstrapModulePath = base_path('bootstrap/modules.php');
        if (File::exists($bootstrapModulePath)) {
            $bootstrapModule = include $bootstrapModulePath;
            return array_keys($bootstrapModule);
        }

        return [];
    }

    /**
     * @return array|string[]
     */
    public function registeredModules(): array
    {
        $bootstrapModulePath = base_path('bootstrap/modules.php');
        if (File::exists($bootstrapModulePath)) {
            return include $bootstrapModulePath;
        }

        return [];
    }

    /**
     * @return array
     */
    public function enables(): array
    {
        $bootstrapModulePath = base_path('bootstrap/modules.php');
        $modules = [];
        if (File::exists($bootstrapModulePath)) {
            $bootstrapModule = include $bootstrapModulePath;
            foreach ($bootstrapModule as $name => $data) {
                if ($data['active']) $modules[] = $name;
            }
        }
        return $modules;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function isEnabled(string $moduleName): bool
    {
        return in_array($moduleName, $this->enables());
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
        $bootstrapModulePath = base_path('bootstrap/modules.php');

        $registeredModule = File::exists($bootstrapModulePath) ? require $bootstrapModulePath : throw new \Exception('bootstrap/modules.php is not exist');

        if (array_key_exists($moduleName, $registeredModule) ) {
           $inEnable=$registeredModule[$moduleName]['active'] ?? false;
           if ($inEnable) return 1;
            $registeredModule[$moduleName]['active']=true;
            
            File::put(
                $bootstrapModulePath,
                '<?php return ' . var_export_short($registeredModule, true) . ';'
            );
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param $moduleName
     * @return int
     * @throws \Exception
     */
    public function disable($moduleName): int
    {
        $bootstrapModulePath = base_path('bootstrap/modules.php');

        $registeredModule = File::exists($bootstrapModulePath) ? require $bootstrapModulePath : throw new \Exception('bootstrap/modules.php is not exist');

        if (array_key_exists($moduleName, $registeredModule) ) {
            $inEnable=$registeredModule[$moduleName]['active'] ?? false;
            if (!$inEnable) return -1;
            $registeredModule[$moduleName]['active']=false;


            File::put(
                $bootstrapModulePath,
                '<?php return ' . var_export_short($registeredModule, true) . ';'
            );
            return -1;
        } else {
            return 0;
        }
    }

}
