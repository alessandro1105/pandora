<?php

    // User module
    $azzurro->module('user', [

    ])

    // Config the module
    ->config(function ($routerProvider) {

        // User login state
        $routerProvider
            ->state([ // Login state
                'name' => 'userLogin',
                'url' => '/login',
                'controller' => 'loginCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Singup state
                'name' => 'userSignup',
                'url' => '/signup',
                'controller' => 'signupCtrl',
                'methods' => 'POST'
            ]);
    })

    // Register the Login Controller
    ->controller('loginCtrl', '\App\Components\User\Login\LoginController')

    // Register the Signup Controller
    ->controller('signupCtrl', '\App\Components\User\Signup\SignupController')

    // Register the User Service
    ->service('userService', '\App\Components\User\UserService');