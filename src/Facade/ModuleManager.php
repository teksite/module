<?php

namespace Teksite\Module\Facade;

/**
 * @method static \Teksite\Module\Services\ModuleServices ModulePath(?string $moduleName = null, bool $absolute=true)
 * @method static \Teksite\Module\Services\ModuleServices ModuleNamespace(?string $moduleName = null, ?string $path=null)
 *
 * @see \Teksite\Module\Services\ModuleServices
 */
use Illuminate\Support\Facades\Facade;

class ModuleManager extends Facade
{
    protected static function getFacadeAccessor(){
        return 'ModuleManager';
    }

}
