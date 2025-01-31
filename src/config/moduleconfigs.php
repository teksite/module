<?php

return [
    "main_path"=>'Lareon',

   'module'=>[

       "directory"=>"Modules",

       'namespace'=>'Lareon\Modules',

       'view_path'=>'resources/views',

       'lang_path'=>'lang',

       'database'=>[
           'path'=>'Database',
           'migration_path'=>'Database/Migrations',
           'factory_path'=>'Database/Factories',
           'seeder_path'=>'Database/Seeders',
       ],
      'config'=>[
          'path'=>'config',

          'default_file'=>'config',
      ]
   ],

    'manager'=>[
        "directory"=>"ModuleManager",

        'namespace'=>'Lareon\ModuleManager',

        'provider' =>\Lareon\ModuleManager\App\Providers\ModuleMangerServiceProvider::class,
    ]
];

