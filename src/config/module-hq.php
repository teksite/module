<?php

return [
    /*
     |--------------------------------------------------------------------------
     | CMS CONFIG
     |--------------------------------------------------------------------------
     |
     */
    "steward" => [
        /*
         |----------------------------------------------------------------------
         | Steward Routes
         |----------------------------------------------------------------------
         |
         | specify which route files should be registered, along with their corresponding middleware and prefix
         |
         */
        "routes"    =>
            [
                //Admin Routes
                [
                    'path'       => 'admin/web.php',
                    'middleware' => ['web', 'auth',],
                    'prefix'     => 'tkadmin',
                    'name'       => 'admin.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'admin/ajax.php',
                    'middleware' => ['api', 'web', 'auth',],
                    'prefix'     => 'tkadmin/ajax',
                    'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'admin/api.php',
                    'middleware' => ['api', 'auth', 'auth:sanctum'],
                    'prefix'     => 'admin/api/v1',
                    'name'       => 'admin.api.v1.',  //DO NOT CHANGE IT,
                ],

                //Panel Routes
                [
                    'path'       => 'panel/web.php',
                    'middleware' => ['web', 'auth',],
                    'prefix'     => 'panel',
                    'name'       => 'panel.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'panel/ajax.php',
                    'middleware' => ['api', 'web', 'auth',],
                    'prefix'     => 'panel/ajax',
                    'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'panel/api.php',
                    'middleware' => ['api', 'auth', 'auth:sanctum'],
                    'prefix'     => 'panel/api/v1',
                    'name'       => 'panel.api.v1.',  //DO NOT CHANGE IT,
                ],

                //Client Routes

                [
                    'path'       => 'web.php',
                    'middleware' => ['web'],
                    'prefix'     => '',
                    'name'       => '',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'ajax.php',
                    'middleware' => ['api', 'web'],
                    'prefix'     => 'ajax',
                    'name'       => 'ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'api.php',
                    'middleware' => ['api'],
                    'prefix'     => 'api/v1',
                    'name'       => 'api.v1.',  //DO NOT CHANGE IT,
                ],

                //AUTH Routes

                [
                    'path'       => 'auth/web.php',
                    'middleware' => ['web'],
                    'prefix'     => 'auth',
                    'name'       => 'auth.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'auth/ajax.php',
                    'middleware' => ['api', 'web'],
                    'prefix'     => 'auth/ajax',
                    'name'       => 'auth.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'auth/api.php',
                    'middleware' => ['api'],
                    'prefix'     => 'auth/api/v1',
                    'name'       => 'auth.api.v1.',  //DO NOT CHANGE IT,
                ],

            ],

        /*
         |----------------------------------------------------------------------
         | Steward Broadcast Channels
         |----------------------------------------------------------------------
         |
         | route files (relative to the "routes" directory) that only define
         | broadcast channels (Broadcast::channel(...) calls). Unlike the
         | "routes" list above, these are not wrapped in Route::group() /
         | middleware / prefix - they are simply require()'d.
         |
         */
        "broadcast" => [
            'channels.php',
        ],

        /*
       |--------------------------------------------------------------------------
       | Modules
       |--------------------------------------------------------------------------
       |
       */
        'modules'   => [
            /*
           |----------------------------------------------------------------------
           | Modules Routes
           |----------------------------------------------------------------------
           |
           | specify which route files should be registered by the CMS, along with their corresponding middleware and prefix
           |
           */
            'routes'    => [
                //Admin Routes
                [
                    'path'       => 'admin/web.php',
                    'middleware' => ['web', 'auth',],
                    'prefix'     => 'tkadmin',
                    'name'       => 'admin.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'admin/ajax.php',
                    'middleware' => ['api', 'web', 'auth',],
                    'prefix'     => 'tkadmin/ajax',
                    'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'admin/api.php',
                    'middleware' => ['api', 'auth', 'auth:sanctum'],
                    'prefix'     => 'admin/api/v1',
                    'name'       => 'admin.api.v1.',  //DO NOT CHANGE IT,
                ],

                //Panel Routes
                [
                    'path'       => 'panel/web.php',
                    'middleware' => ['web', 'auth',],
                    'prefix'     => 'panel',
                    'name'       => 'panel.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'panel/ajax.php',
                    'middleware' => ['api', 'web', 'auth',],
                    'prefix'     => 'panel/ajax',
                    'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'panel/api.php',
                    'middleware' => ['api', 'auth', 'auth:sanctum'],
                    'prefix'     => 'panel/api/v1',
                    'name'       => 'panel.api.v1.',  //DO NOT CHANGE IT,
                ],

                //Client Routes

                [
                    'path'       => 'web.php',
                    'middleware' => ['web'],
                    'prefix'     => '',
                    'name'       => '',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'ajax.php',
                    'middleware' => ['api', 'web'],
                    'prefix'     => 'ajax',
                    'name'       => 'ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'api.php',
                    'middleware' => ['api'],
                    'prefix'     => 'api/v1',
                    'name'       => 'api.v1.',  //DO NOT CHANGE IT,
                ],

                //AUTH Routes

                [
                    'path'       => 'auth/web.php',
                    'middleware' => ['web'],
                    'prefix'     => 'auth',
                    'name'       => 'auth.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'auth/ajax.php',
                    'middleware' => ['api', 'web'],
                    'prefix'     => 'auth/ajax',
                    'name'       => 'auth.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'auth/api.php',
                    'middleware' => ['api'],
                    'prefix'     => 'auth/api/v1',
                    'name'       => 'auth.api.v1.',  //DO NOT CHANGE IT,
                ],

            ],
            /*
            |----------------------------------------------------------------------
            | Modules Broadcast Channels
            |----------------------------------------------------------------------
            |
            | route files (relative to each module's "routes" directory) that
            | only define broadcast channels (Broadcast::channel(...) calls).
            | Unlike the "routes" list above, these are not wrapped in
            | Route::group() / middleware / prefix - they are simply require()'d.
            |
            */
            'broadcast' => [
                'channels.php',
            ],
        ],
    ],


];
