<?php

namespace Teksite\Module\Facade;

/**
 * @method static \Teksite\Module\Services\ModuleServices ModulePath(?string $moduleName = null, bool $absolute=true)
 *
 * @see \Teksite\Module\Services\ModuleServices
 */
use Illuminate\Support\Facades\Facade;

class Module extends Facade
{
    protected static function getFacadeAccessor(){
        return 'Module';
    }

}
