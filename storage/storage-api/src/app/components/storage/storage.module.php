<?php

    // User module
    $azzurro->module('storage', [

    ])
    
    // MAX file versions
    ->constant('MAX_FILE_VERSIONS', 10)
    // Persistent API
    ->constant('API_PERSISTENT', 'http://persistent-api')

    // Config the module
    ->config(function ($routerProvider) {

        // User login state
        $routerProvider
            ->state([ // Delete state
                'name' => 'delete',
                'url' => '/:uuid/*path',
                'controller' => 'deleteCtrl',
                'methods' => 'DELETE'
            ])

            ->state([ // Edit state
                'name' => 'edit',
                'url' => '/:uuid/*path',
                'controller' => 'editCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Delete state
                'name' => 'retrieve',
                'url' => '/:uuid/*path',
                'controller' => 'retrieveCtrl',
                'methods' => 'GET'
            ])

            ->state([ // Delete state
                'name' => 'upload',
                'url' => '/:uuid/*path',
                'controller' => 'uploadCtrl',
                'methods' => 'PUT'
            ]);
    })

    // Register the Delete Controller
    ->controller('deleteCtrl', '\App\Components\Storage\Delete\DeleteController')

    // Register the Edit Controller
    ->controller('editCtrl', '\App\Components\Storage\Edit\EditController')

    // Register the Retrieve Controller
    ->controller('retrieveCtrl', '\App\Components\Storage\Retrieve\RetrieveController')

    // Register the Upload Controller
    ->controller('uploadCtrl', '\App\Components\Storage\Upload\UploadController')

    // Register Storage Service
    ->service('storageService', '\App\Components\Storage\StorageService');