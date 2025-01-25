<?php

return [
    "main_path"=>'lareon',

   'module'=>[
       "directory"=>"modules",

       'namespace'=>'Lareon\\Modules\\',

       'path'=>base_path('Lareon/Modules'),

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
   ]
];

