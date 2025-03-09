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
   "main_path"=>'Lareon',


    /*
   |--------------------------------------------------------------------------
   | registration
   |--------------------------------------------------------------------------
   |
   | The file contains registration and activation of modules.
   | CAUTION: if you don,t have enough information DO NOT CHANGE it.
   |
   */
    "registration_file"=>base_path('bootstrap').'/modules.php',


    /*
   |--------------------------------------------------------------------------
   | Module configuration
   |--------------------------------------------------------------------------
   |
   */
   'module'=>[
       "directory"=>"Modules",

       'namespace'=>'Lareon\Modules',
   ],

    'manager'=>[
        "provider"=>''
    ]
];

