<?php

namespace Teksite\Module\Services;

use Teksite\Extralaravel\Traits\StudyPathNamespace;

class ManagerServices
{
    use StudyPathNamespace;

    /**
     * Get absolute or relative root of modules or the specific module.
     *
     * @param string|null $path
     * @param bool $absolute
     * @return string
     */
    public function managerPath(?string $path = null, bool $absolute = true): string
    {
        $mainPath = config('moduleconfigs.main_path') && config('moduleconfigs.manager.directory')
            ? config('moduleconfigs.main_path') . DIRECTORY_SEPARATOR . config('moduleconfigs.manager.directory')
            : "Lareon/Modules";

        $relativePath = $this->normalizePath($mainPath .  ($path ? '/' . $path : ''));
        return $absolute ? base_path($relativePath) : $relativePath;
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function managerNamespace(?string $path = null): string
    {
        $mainNamespace = config('moduleconfigs.manager.namespace', "Lareon\ModuleManager");

        return $this->normalizeNamespace($mainNamespace . ($path ? '\\' . trim($path, "/\\") : ''));
    }



}
