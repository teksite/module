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


    /*
    |--------------------------------------------------------------------------
    | Module configuration
    |--------------------------------------------------------------------------
    |
    */
    'module'                    => [
        "directory" => "modules",
        'namespace' => 'Lareon\Modules',
    ],
    'manager'                   => [
        "directory"      => "steward",
        'namespace'      => 'Lareon\Steward',
        'module_manager' => '\\Lareon\\CMS\\App\\Providers\\ModulesManagerServiceProvider',

        'view_path' => 'resources/views',
    ],

];

