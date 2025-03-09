<?php

namespace Teksite\Module\Facade;

/**
 * @method static string modulePath(?string $moduleName = null, bool $absolute=true)
 * @method static string moduleNamespace(?string $moduleName = null, ?string $path=null)
 * @method static array all()
 * @method static array registeredModules()
 * @method static array enables()
 * @method static bool isEnabled(string $moduleName)
 * @method static bool has(string $moduleName)
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
