<?php
/*
	User application main file

	This file is the main file of your application.
	Inside this file you can develop your main application module

	DO NOT DELETE THIS FILE otherwise your application will not be loaded.
*/

	// Application main module
	$azzurro
		->app("app", [
        ])
        
        // MAX file versions
    ->constant('MAX_FILE_VERSIONS', 10)
    // Persistent API
    ->constant('API_PERSISTENT', 'http://persistent-api')

    // Config the module
    ->config(function ($routerProvider) {

        $routerProvider
            ->otherwise(function () {
                http_response_code(404);
            });

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
    ->controller('deleteCtrl', 'DeleteController')

    // Register the Edit Controller
    ->controller('editCtrl', 'EditController')

    // Register the Retrieve Controller
    ->controller('retrieveCtrl', 'RetrieveController')

    // Register the Upload Controller
    ->controller('uploadCtrl', 'UploadController');