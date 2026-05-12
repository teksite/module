<?php

return [
    /*
   |--------------------------------------------------------------------------
   | Path
   |--------------------------------------------------------------------------
   |
   | Main path where modules folder placed there.
   |
   */
    "main_path"                 => 'lareon',

    /*
   |--------------------------------------------------------------------------
   | registration
   |--------------------------------------------------------------------------
   |
   | The file contains registration and activation of modules.
   | CAUTION: if you don,t have enough information DO NOT CHANGE it.
   |
   */
    "registration_modules_file" => base_path('bootstrap') . '/modules.php',

    "boot_all_modules" => 1, //1 for all modules (enabled and disabled)
    //0 only for enabled modules

    /*
    |--------------------------------------------------------------------------
    | Module and Steward configuration
    |--------------------------------------------------------------------------
    |
    */
    'module'           => [
        "directory"      => "modules",
        'namespace'      => 'Lareon\Modules',
        'lang_path'      => 'lang',
        'views'          => 'resources/views',
        'config_path'    => 'config',
        'migration_path' => 'database/migrations',
    ],

    'steward' => [
        "enable"           => true,
        "Name"             => "Steward",
        "directory"        => "steward",
        'namespace'        => 'Lareon\Steward',
        'lang_path'        => 'lang',
        'views'            => 'resources/views',
        'config_path'      => 'config',
        'migration_path'   => 'database/migrations',
        'module_manager'   => '\\Lareon\\Steward\\App\\Providers\\ModulesHeadquarterServiceProvider',
        'steward_provider' => '\\Lareon\\Steward\\App\\Providers\\StewardServiceProvider',

    ],

];
