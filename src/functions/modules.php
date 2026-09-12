<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


if (!function_exists('reset_modules_cache')) {
    /**
     * Clear the in-memory modules cache. Call this after writing to the
     * bootstrap file (enable/disable/register/unregister) so subsequent
     * reads in the same request see fresh data.
     *
     * @return void
     */
    function reset_modules_cache(): void
    {
        get_modules_bootstrap(true);
    }
}

// modules helper function

if (!function_exists('module_bootstrap_path')) {
    /**
     * get path of modules registration files
     *
     * @return string
     */
    function module_bootstrap_path(): string
    {
        return config('modules.registration_modules_file', base_path('bootstrap/modules.php'));
    }
}

if (!function_exists('get_modules_bootstrap')) {
    /**
     * Get the raw array of installed modules from the bootstrap file (cached).
     *
     * @param bool $fresh force re-reading the file from disk
     * @return array
     */
    function get_modules_bootstrap(bool $fresh = false,): array
    {
        static $cache = null;

        if ($fresh || $cache === null) {
            $path = module_bootstrap_path();
            $cache = File::exists($path) ? (require $path) : [];
            $cache = is_array($cache) ? $cache : [];
        }

        return $cache;
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
    function get_module(string $moduleName,): array
    {
        $allModules = get_modules();
        return in_array($moduleName, array_keys($allModules))
            ? $allModules[$moduleName]
            : [];

    }
}

if (!function_exists('get_modules_name')) {
    /**
     * get array of registered module names
     *
     * @return array
     */
    function get_modules_name(): array
    {
        return array_keys(get_modules());
    }
}

if (!function_exists('get_module_type')) {
    /**
     * @param string $module
     * @return string|null returns self|steward|null, null means not registered in the modules bootstrap file
     */
    function get_module_type(string $module,): ?string
    {
        return get_module($module)['type'] ?? null;
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
        return array_map(fn($module,) => $module['active'] ?? null, get_modules());
    }
}

if (!function_exists('get_enabled_modules')) {
    /**
     * get arrays of installed and enabled modules
     *
     * @param bool $onlyName
     * @return array
     */
    function get_enabled_modules(bool $onlyName = false,): array
    {
        $modules = array_filter(get_modules(), fn($data,) => ($data['active'] ?? false) === true);
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
    function get_disabled_modules(bool $onlyName = false,): array
    {
        $modules = array_filter(get_modules(), fn($data,) => ($data['active'] ?? false) === false);
        return $onlyName ? array_keys($modules) : $modules;
    }
}

if (!function_exists('module_path')) {
    /**
     * @param string|null $moduleName name of the module or module root path
     * @param string|null $path       desired path
     * @param bool        $absolute   absolute or relevant from project path
     * @return string|null
     */
    function module_path(?string $moduleName = null, ?string $path = null, bool $absolute = true,): ?string
    {
        $modulesRootPath = config('modules.main_path', 'lareon').DIRECTORY_SEPARATOR.config('modules.module.directory', 'modules');

        $moduleName = $moduleName ? Str::ucfirst($moduleName) : null;

        $modulePath = $modulesRootPath.($moduleName ? DIRECTORY_SEPARATOR.$moduleName : '');

        $finalPath = $modulePath.($path ? DIRECTORY_SEPARATOR.ltrim($path, '\/') : '');
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
     * @throws Exception
     */
    function module_namespace(?string $moduleName = null,): string
    {
        return config('modules.module.namespace', 'Lareon\Modules').($moduleName ? '\\'.Str::ucfirst($moduleName) : '');
    }
}

if (!function_exists('module_view_path')) {
    /**
     * return module view path
     *
     * @param string $modules
     * @param bool   $absolute
     * @return string return string
     */
    function module_view_path(string $modules, bool $absolute = false,): string
    {
        return module_path($modules, config('modules.module.view', 'resources/views'), $absolute);
    }
}

if (!function_exists('module_resource_path')) {
    /**
     * return module resource path
     *
     * @param string      $moduleName name of the module or module root path
     * @param string|null $path       desired path view
     * @param bool        $absolute
     * @return string|null
     */
    function module_resource_path(string $moduleName, ?string $path = null, bool $absolute = false,): ?string
    {
        return module_path($moduleName, '/resources/'.$path, $absolute);
    }

}

// steward helper function

if (!function_exists('isStewardInstalled')) {

    /**
     * is steward installed and enabled or not
     *
     * @return bool
     */
    function isStewardInstalled(): bool
    {
        return config('modules.steward.enable', true) && is_dir(steward_path());
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
        if (!isStewardInstalled()) return ['Steward' => ['provider' => null, 'active' => false, 'type' => null]];
        return [
            'Steward' => [
                'provider' => config('modules.steward.steward_provider', 'Lareon\\Steward\\App\\Providers\\StewardServiceProvider'),
                'active'   => true,
                'type'     => 'steward',
            ],
        ];

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
     * @param string|null $path     desired sub path
     * @param bool        $absolute absolute or relative to the project root
     * @return string
     */
    function steward_path(?string $path = null, bool $absolute = true,): string
    {
        $stewardRootPath = config('modules.main_path', 'lareon').DIRECTORY_SEPARATOR.config('modules.steward.directory', 'steward');

        $finalPath = $stewardRootPath.($path ? DIRECTORY_SEPARATOR.ltrim($path, '\/') : '');
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
    function steward_view_path(bool $absolute = false,): string
    {
        return steward_path(config('modules.module.view', 'resources/views'), $absolute);
    }
}

if (!function_exists('steward_resource_path')) {
    /**
     *  return steward resource path
     *
     * @param string|null $path desired path view
     * @param bool        $absolute
     * @return string|null
     */
    function steward_resource_path(?string $path = null, bool $absolute = false,): ?string
    {
        return steward_path('resources'.($path ? '/'.ltrim($path, '/\\') : ''), $absolute);
    }

}


// steward and modules together

if (!function_exists('getAllModules')) {
    /**
     * get arrays of modules and steward and their activation status
     *
     * @param bool $onlyName
     * @return array
     */
    function getAllModules(bool $onlyName = false,): array
    {
        $modules = get_modules();

        if (isStewardInstalled()) $modules = steward_data() + $modules;

        return $onlyName ? array_keys($modules) : $modules;
    }
}

if (!function_exists('getEnabledModules')) {
    /**
     * get array of installed and enabled modules, including Steward
     *
     * @param bool $onlyName
     * @return array
     */
    function getEnabledModules(bool $onlyName = false,): array
    {
        $modules = array_filter(getAllModules(), fn($data,) => ($data['active'] ?? false) === true);

        return $onlyName ? array_keys($modules) : $modules;
    }
}

if (!function_exists('getModulesStatus')) {
    /**
     * get array of modules (including Steward) and their activation status
     *
     * @return array
     */
    function getModulesStatus(): array
    {
        return array_map(fn($module,) => $module['active'] ?? false, getAllModules());
    }
}

if (!function_exists('modulePath')) {
    /**
     * return path for both modules or steward
     *
     * @param string      $module
     * @param string|null $path
     * @param bool        $absolute
     * @param bool        $throwOnSteward
     * @return string|null
     * @throws Exception
     */
    function modulePath(string $module, ?string $path = null, bool $absolute = false, bool $throwOnSteward = true,): ?string
    {
        return match (true) {
            $module === 'Steward' && isStewardInstalled()  => steward_path($path, $absolute),
            $module === 'Steward' && !isStewardInstalled() => $throwOnSteward ? throw new Exception('Steward is not installed') : null,
            default                                        => module_path($module, $path, $absolute),
        };
    }
}

if (!function_exists('moduleNamespace')) {
    /**
     *  get namespace of a module or steward
     *
     * @param string|null $module
     * @param bool        $throwOnSteward
     * @return string|null
     * @throws Exception
     */
    function moduleNamespace(?string $module = null, bool $throwOnSteward = true,): ?string
    {
        return match (true) {
            $module === 'Steward' && isStewardInstalled()  => steward_namespace(),
            $module === 'Steward' && !isStewardInstalled() => $throwOnSteward ? throw new Exception('Steward is not installed') : null,
            default                                        => module_namespace($module),
        };
    }
}

