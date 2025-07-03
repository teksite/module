<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait ModuleNameValidator
{
    protected function validateModuleName(string $moduleName): array
    {
        // Exact match
        if ($this->exactMatch($moduleName)) {
            return [true, $moduleName];
        }
        // Check for similar matches
        if ($suggest =$this->getSimilarMatches($moduleName)) {
            return [false, $suggest];
        }

        return [false, null];
    }

    /**
     * Get all existing module names.
     *
     * @return array
     */
    protected function getAllModuleNames(): array
    {
        $modulePath = base_path('Lareon/Modules');
        if (!File::exists($modulePath)) {
            return [];
        }

        return collect(File::directories($modulePath))
            ->map(fn($path) => basename($path))
            ->toArray();
    }

    /**
     * Check if the given name matches any module name exactly.
     *
     * @param string $moduleName
     * @return bool
     */
    protected function exactMatch(string $moduleName): bool
    {
        $allModules = $this->getAllModuleNames();

        return in_array($moduleName, $allModules);
    }

    /**
     * Check for similar matches in different cases and return suggestions.
     *
     * @param string $moduleName
     * @return array
     */
    protected function getSimilarMatches(string $moduleName): ?string
    {
        $allModules = $this->getAllModuleNames();
        return collect($allModules)->filter(function ($module) use ($moduleName) {
            return strtolower($module) === strtolower($moduleName);
        })->values()->first();
    }
}
