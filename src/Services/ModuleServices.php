<?php

namespace Teksite\Module\Services;


use Illuminate\Support\Facades\File;

class ModuleServices
{
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
    public function exists(string $moduleName) :bool
    {
        return in_array($moduleName, $this->all());
    }


    /**
     * @param string $moduleName
     * @return array
     */
    public function info(string $moduleName): array
    {
        $path=$this->modulePath($moduleName ,'info.json');
        if(file_exists($path)){
            return json_decode(file_get_contents($path), true);
        }
        return [];
    }


}
