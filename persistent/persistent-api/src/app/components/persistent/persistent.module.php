<?php

/*
    persistent module declaration
*/

    $azzurro
        ->module('persistent', [

        ])

        // Config function
        ->config(function ($routerProvider) {
            
            // Define all state
            $routerProvider

                ->state([
                    'name' => 'download',
                    'url' => '/:uuid',
                    'controller' => 'downloadCtrl',
                    'methods' => 'GET'
                ])

                ->state([
                    'name' => 'upload',
                    'url' => '/:uuid',
                    'controller' => 'uploadCtrl',
                    'methods' => 'PUT'
                ])

                ->state([
                    'name' => 'delete',
                    'url' => '/:uuid',
                    'controller' => 'deleteCtrl',
                    'methods' => 'DELETE'
                ]);

        })

        // Download controller
        ->controller('downloadCtrl', '\App\Components\Persistent\Download\DownloadController')
        // Upload controller
        ->controller('uploadCtrl', '\App\Components\Persistent\Upload\UploadController')
        // Delete controller
        ->controller('deleteCtrl', '\App\Components\Persistent\Delete\DeleteController')

        // Persistent service
        ->service('persistentService', '\App\Components\Persistent\PersistentService');

