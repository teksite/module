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
        "routes" =>
            [
                //Admin Routes
                [
                    'path'       => 'admin/web.php',
                    'middleware' => ['web', 'auth', 'verified'],
                    'prefix'     => 'tkadmin',
                    'name'       => 'admin.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'admin/ajax.php',
                    'middleware' => ['api', 'web', 'auth', 'verified'],
                    'prefix'     => 'tkadmin/ajax',
                    'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'admin/api.php',
                    'middleware' => ['api', 'auth', 'verified', 'auth:sanctum'],
                    'prefix'     => 'admin/api/v1',
                    'name'       => 'admin.api.v1.',  //DO NOT CHANGE IT,
                ],

                //Panel Routes
                [
                    'path'       => 'panel/web.php',
                    'middleware' => ['web', 'auth', 'verified'],
                    'prefix'     => 'panel',
                    'name'       => 'panel.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'panel/ajax.php',
                    'middleware' => ['api', 'web', 'auth', 'verified'],
                    'prefix'     => 'panel/ajax',
                    'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
                ],
                [
                    'path'       => 'panel/api.php',
                    'middleware' => ['api', 'auth', 'verified', 'auth:sanctum'],
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

    ],

    /*
       |--------------------------------------------------------------------------
       | Modules
       |--------------------------------------------------------------------------
       |
       */
    'modules' => [
        /*
       |----------------------------------------------------------------------
       | Modules Routes
       |----------------------------------------------------------------------
       |
       | specify which route files should be registered by the CMS, along with their corresponding middleware and prefix
       |
       */
        'routes' => [
            //Admin Routes
            [
                'path'       => 'admin/web.php',
                'middleware' => ['web', 'auth', 'verified'],
                'prefix'     => 'tkadmin',
                'name'       => 'admin.',  //DO NOT CHANGE IT,
            ],
            [
                'path'       => 'admin/ajax.php',
                'middleware' => ['api', 'web', 'auth', 'verified'],
                'prefix'     => 'tkadmin/ajax',
                'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
            ],
            [
                'path'       => 'admin/api.php',
                'middleware' => ['api', 'auth', 'verified', 'auth:sanctum'],
                'prefix'     => 'admin/api/v1',
                'name'       => 'admin.api.v1.',  //DO NOT CHANGE IT,
            ],

            //Panel Routes
            [
                'path'       => 'panel/web.php',
                'middleware' => ['web', 'auth', 'verified'],
                'prefix'     => 'panel',
                'name'       => 'panel.',  //DO NOT CHANGE IT,
            ],
            [
                'path'       => 'panel/ajax.php',
                'middleware' => ['api', 'web', 'auth', 'verified'],
                'prefix'     => 'panel/ajax',
                'name'       => 'admin.ajax.',  //DO NOT CHANGE IT,
            ],
            [
                'path'       => 'panel/api.php',
                'middleware' => ['api', 'auth', 'verified', 'auth:sanctum'],
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

    ],


];
