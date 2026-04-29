<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Teksite\Module\Facade\Module;
use Teksite\Module\Services\ModuleServices;

trait ModuleValidationGeneratorTrait
{
    /**
     * Checks whether the given name is reserved.
     *
     * @param string $module
     * @return bool
     */
    protected function isModuleExist(string $module): bool
    {
        $modules = get_modules_status(true);

        if (!in_array($module, array_keys($modules))) return false;
        if ($modules[$module] === false) $this->line("<fg=yellow;options=bold>{$module} in not active<>");
        return true;
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $name
     * @return bool
     */
    protected function isReservedName(string $name): bool
    {
        return in_array(
            strtolower($name),
            (new Collection($this->reservedNames))
                ->transform(fn($name) => strtolower($name))
                ->all()
        );
    }



}
