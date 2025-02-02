<?php

namespace Teksite\Module\Facade;

/**
 * @method static \Teksite\Module\Services\LareonServices ModulePath(?string $moduleName = null, bool $absolute=true)
 * @method static \Teksite\Module\Services\LareonServices ModuleNamespace(?string $moduleName = null, ?string $path=null)
 *
 * @see \Teksite\Module\Services\LareonServices
 */
use Illuminate\Support\Facades\Facade;

class ModuleManager extends Facade
{
    protected static function getFacadeAccessor(){
        return 'ModuleManager';
    }

}
