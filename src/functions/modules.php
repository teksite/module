<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

if (!function_exists('module_bootstrap_path')) {
    /**
     * get path of modules registration files
     *
     * @return string
     */
    function module_bootstrap_path(): string
    {
        return config('modules.registration_modules_file', base_path('bootstrap') . '/modules.php');
    }
}

if (!function_exists('get_modules_bootstrap')) {
    /**
     * get arrays of installed modules
     *
     * @param string|array $modules
     * @return array|null
     */
    function get_modules_bootstrap(): null|array
    {
        return File::exists(module_bootstrap_path()) ? require module_bootstrap_path() : [];
    }
}

if (!function_exists('get_modules')) {
    /**
     * get arrays of installed modules
     *
     * @return array
     */
    function get_modules(): array
    {
        return get_modules_bootstrap();
    }
}

if (!function_exists('get_module')) {
    /**
     * get data of the module
     *
     * @param string $moduleName
     * @return array
     */
    function get_module(string $moduleName): array
    {
        $allModules = get_modules();
        return in_array($moduleName, array_keys($allModules))
            ? $allModules[$moduleName]
            : [];

    }
}

if (!function_exists('get_modules_status')) {
    /**
     * get arrays of modules and their activation status
     *
     * @return array
     */
    function get_modules_status(): array
    {
        return collect(get_modules())
            ->map(fn($module) => $module['active'] ?? false)
            ->toArray();
    }
}

if (!function_exists('get_modules_name')) {
    /**
     * get arrays of registered modules name
     *
     * @return array
     */
    function get_modules_name(): array
    {
        return array_keys(get_modules() ?? []);
    }
}

if (!function_exists('get_enabled_modules')) {
    /**
     * get arrays of installed and enabled modules
     *
     * @param bool $onlyName
     * @return array
     */
    function get_enabled_modules(bool $onlyName = false): array
    {
        $modules = collect(get_modules())
            ->filter(fn($data, $key) => isset($data['active']) && $data['active'] === true)
            ->toArray();
        return $onlyName ? array_keys($modules) : $modules;
    }
}

if (!function_exists('get_disabled_modules')) {
    /**
     * get arrays of installed and disabled modules
     *
     * @param bool $onlyName
     * @return array
     */
    function get_disabled_modules(bool $onlyName = false): array
    {
        $modules = collect(get_modules())
            ->filter(fn($data, $key) => !isset($data['active']) || $data['active'] === false)
            ->toArray();
        return $onlyName ? array_keys($modules) : $modules;

    }
}

if (!function_exists('get_all_modules')) {
    /**
     * get arrays of installed modules
     *
     * @param bool $onlyName
     * @return array
     */
    function get_all_modules(bool $onlyName = false): array
    {
        $modules = get_modules_bootstrap();

        return $onlyName ? array_keys($modules) : $modules;

    }
}

if (!function_exists('get_module_type')) {
    /**
     *
     *
     * @param string $modules
     * @return string|null return self|steward|null , null means not registered in modules bootstrap file
     */
    function get_module_type(string $modules): null|string
    {
        $moduleData = get_module($modules) ?? [];
        return $moduleData['type'] ?? null;
    }
}

if (!function_exists('module_path')) {
    /**
     * @param string|null $moduleName name of the module or module root path
     * @param string|null $path desired path
     * @param bool $absolute absolut or relevant from project path
     * @return string|null
     */
    function module_path(?string $moduleName = null, ?string $path = null, bool $absolute = true): ?string
    {
        $modulesRootPath = config('modules.main_path', 'lareon') . DIRECTORY_SEPARATOR . config('modules.module.directory', 'modules');

        $moduleName = $moduleName ? Str::ucfirst($moduleName) : null;

        $modulePath = $modulesRootPath . ($moduleName ? DIRECTORY_SEPARATOR . $moduleName : '');

        $finalPath = $modulePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : '');
        $normalized = normalizeSlashPath($finalPath);

        return $absolute ? base_path($normalized) : $normalized;
    }

}

if (!function_exists('module_namespace')) {
    /**
     *  get namespace of module(s)
     *
     * @param string|null $moduleName
     * @return string
     */
    function module_namespace(?string $moduleName = null): string
    {
        return config('modules.module.namespace', 'Lareon\Modules') . ($moduleName ? '\\' . Str::ucfirst($moduleName) : '');
    }
}

if (!function_exists('module_view_path')) {
    /**
     * return module view path
     *
     * @param string $modules
     * @param bool $absolute
     * @return string return string
     */
    function module_view_path(string $modules, bool $absolute = false): string
    {
        return module_path($modules, config('modules.module.view', 'resources/views'), $absolute);
    }
}

if (!function_exists('module_resource_path')) {
    /**
     * @param string $moduleName name of the module or module root path
     * @param string|null $path desired path view
     * @param bool $absolute
     * @return string|null
     */
    function module_resource_path(string $moduleName, ?string $path = null, bool $absolute = false): ?string
    {
        return module_path($moduleName, '/resources/' . $path, $absolute);
    }

}



if (!function_exists('steward_data')) {
    /**
     * get arrays of steward data and its activation status
     *
     * @return array
     */
    function steward_data(): array
    {
        $stewardData = [];
        if (isStewardInstalled()) {
            $stewardData['Steward'] = [
                'provider' => 'Lareon\\Steward\\App\\Providers\\StewardServiceProvider',
                'active'   => true,
                'type'     => 'steward',
            ];
        } else {
            $stewardData['Steward'] = [
                'provider' => null,
                'active'   => false,
                'type'     => 'null',
            ];
        }
        return $stewardData;
    }
}

if (!function_exists('steward_namespace')) {
    /**
     *  get namespace steward
     *
     * @return string
     */
    function steward_namespace(): string
    {
        return config('modules.steward.namespace', 'Lareon\Modules');
    }
}

if (!function_exists('steward_path')) {
    /**
     * @param string|null $path desired path
     * @param bool $absolute absolut or relevant from project path
     * @return string|null
     */
    function steward_path(?string $path = null, bool $absolute = true): ?string
    {
        $stewardRootPath = config('modules.main_path', 'lareon') . DIRECTORY_SEPARATOR . config('modules.steward.directory', 'steward');

        $finalPath = $stewardRootPath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : '');
        $normalized = normalizeSlashPath($finalPath);

        return $absolute ? base_path($normalized) : $normalized;
    }

}

if (!function_exists('steward_view_path')) {
    /**
     * return steward view path
     *
     * @return string return string
     */
    function steward_view_path(bool $absolute = false): string
    {
        return steward_path(config('modules.module.view', 'resources/views'), $absolute);
    }
}

if (!function_exists('steward_resource_path')) {
    /**
     * @param string|null $path desired path view
     * @param bool $absolute
     * @return string|null
     */
    function steward_resource_path(?string $path = null, bool $absolute = false): ?string
    {
        return steward_path('/resources/' . $path, $absolute);
    }

}

if (!function_exists('isStewardInstalled')) {

    /**
     * @return bool
     */
    function isStewardInstalled(): bool
    {
        return config('modules.steward.enable', true) && is_dir(steward_path());
    }
}



if (!function_exists('getModulesStatus')) {
    /**
     * get arrays of modules and steward and their activation status
     *
     * @return array
     */
    function getModulesStatus(): array
    {
        $modules = get_modules_bootstrap();
        return collect($modules)
            ->map(fn($module) => $module['active'] ?? false)
            ->when(isStewardInstalled(), function ($collection) {
                return collect(['Steward' => true])->merge($collection);
            })
            ->toArray();
    }
}

if (!function_exists('getEnabledModules')) {
    /**
     * get arrays of installed and enabled modules
     *
     * @param bool $onlyName
     * @return array
     */
    function getEnabledModules(bool $onlyName = false): array
    {
        $stewardData = steward_data();
        $modules = get_modules_bootstrap();

        $allModules = collect($stewardData)
            ->merge($modules)
            ->filter(fn($data, $key) => isset($data['active']) && $data['active'] === true)
            ->toArray();
        return $onlyName ? array_keys($allModules) : $allModules;


    }
}

if (!function_exists('getAllModules')) {
    /**
     * get arrays of modules and steward and their activation status
     *
     * @param bool $onlyName
     * @return array
     */
    function getAllModules(bool $onlyName): array
    {
        $modules = get_modules_bootstrap();
        if (isStewardInstalled()) {
            $modules = array_merge(steward_data(), $modules);
        }
        $modules = collect($modules)
            ->map(fn($module) => $module['active'] ?? false)
            ->toArray();

        return $onlyName ? array_keys($modules) : $modules;
    }
}

if (!function_exists('modulePath')) {
    /**
     * return path for both modules or steward
     * @param string $module
     * @param bool $absolute
     * @param bool $throwOnSteward
     * @return string|null
     * @throws Exception
     */
    function modulePath(string $module, bool $absolute = false , bool $throwOnSteward=true): ?string
    {
        if ($module === 'Steward' && isStewardInstalled()) {
            return steward_path($module, $absolute);
        } elseif ($module === 'Steward' && !isStewardInstalled()) {
            return $throwOnSteward ? throw new Exception('Steward is not installed') : null;
        } else {
            return module_path($module, $absolute);
        }
    }
}

if (!function_exists('moduleNamespace')) {
    /**
     *  get namespace of module
     *
     * @param string|null $moduleName
     * @param bool $throwOnSteward
     * @return string|null
     * @throws Exception
     */
    function module_namespace(string $moduleName = null ,bool $throwOnSteward=true): string|null
    {
        if ($moduleName === 'Steward' && isStewardInstalled()) {
            return steward_namespace();
        } elseif ($moduleName === 'Steward' && !isStewardInstalled()) {
            return $throwOnSteward ? throw new Exception('Steward is not installed') : null;
        } else {
            return module_namespace($moduleName);
        }
    }
}

