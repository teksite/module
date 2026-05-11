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
    function get_modules_bootstrap(string|array $modules = ['*']): null|array
    {
        $bootstrapContent = File::exists(module_bootstrap_path()) ? require module_bootstrap_path() : [];

        $modulesArray = is_array($modules) ? $modules : [$modules];

        if (in_array('*', $modulesArray)) return $bootstrapContent;

        $filteredModules = collect($bootstrapContent)
            ->filter(fn($data, $key) => in_array($key, $modulesArray))
            ->toArray();
        return is_array($modules) ? $filteredModules : array_first($filteredModules ?? []);
    }
}

if (!function_exists('get_modules')) {
    /**
     * get arrays of installed modules
     *
     * @param string|array $modules
     * @return array
     */
    function get_modules(string|array $modules = ['*']): array
    {
        return get_modules_bootstrap($modules);
    }
}

if (!function_exists('get_modules_status')) {
    /**
     * get arrays of modules and their activation status
     *
     * @param bool $steward
     * @return array
     */
    function get_modules_status(bool $steward = false): array
    {
        $modules = get_modules_bootstrap();
        return collect($modules)
            ->map(fn($module) => $module['active'] ?? false)
            ->when($steward && isStewardInstalled(), function ($collection) {
                return collect(['Steward' => true])->merge($collection);
            })
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
        $modules = collect(get_modules_bootstrap())
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
        $modules = collect(get_modules_bootstrap())
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
        return (get_modules_bootstrap($modules))['type'] ?? null;
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
        return is_dir(steward_path());
    }
}


if (!function_exists('modulePath')) {
    function modulePath(string $module, bool $absolute = false): ?string
    {
        if ($module === 'steward' && isStewardInstalled()) {
            return steward_path($module, $absolute);
        } elseif ($module === 'steward' && !isStewardInstalled()) {
            return null;
        } else {
            return module_path($module, $absolute);
        }
    }
}


