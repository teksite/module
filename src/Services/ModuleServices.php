<?php

namespace Teksite\Module\Services;

use Teksite\Module\Traits\StudyPathNamespace;

class ModuleServices
{
    use StudyPathNamespace;

    /**
     * Get absolute or relative root of modules or the specific module.
     *
     * @param string|null $moduleName
     * @param bool $absolute
     * @return string
     */
    public function ModulePath(?string $moduleName = null, ?string $path = null, bool $absolute = true): string
    {
        $mainPath = config('moduleconfigs.main_path') && config('moduleconfigs.module.path')
            ? config('moduleconfigs.main_path') . DIRECTORY_SEPARATOR . config('moduleconfigs.module.path')
            : "Lareon/Modules";

        $relativePath = $moduleName ? $this->normalizePath($mainPath . '/' . $moduleName . ($path ? '/' . $path : '')) : $mainPath;
        return $absolute ? base_path($relativePath) : $relativePath;
    }

    public function ModuleNamespace(string $moduleName = null, ?string $path = null): string
    {
        $mainNamespace = config('moduleconfigs.module.namespace', "Lareon\Module");

        return $moduleName ? $this->normalizeNamespace($mainNamespace . '\\' . $moduleName . ($path ? '\\' . trim($path, "/\\") : '')) : $mainNamespace;
    }

    public function ModuleViewPath(string $moduleName = null, ?string $path = null): string
    {
        return $this->ModulePath($moduleName, 'resources/views' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }


}
