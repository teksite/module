<?php

namespace Teksite\Module\Facade;

/**
 * @method static string modulePath(?string $moduleName = null, bool $absolute=true)
 * @method static string moduleNamespace(?string $moduleName = null, ?string $path=null)
 * @method static array all()
 * @method static array enables()
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
