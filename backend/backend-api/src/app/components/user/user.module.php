<?php

    // User module
    $azzurro->module('user', [

    ])

    ->constant('USER_SERVICE_API_ENDPOINT', 'http://user-api')

    // Config the module
    ->config(function ($routerProvider) {

        // User login state
        $routerProvider
            ->state([ // Login state
                'name' => 'userLogin',
                'url' => '/user/login',
                'controller' => 'loginCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Singup state
                'name' => 'userSignup',
                'url' => '/user/signup',
                'controller' => 'signupCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Singup state
                'name' => 'userLogout',
                'url' => '/user/logout',
                'controller' => 'logoutCtrl',
                'methods' => 'POST'
            ]);
    })

    // Register the Login Controller
    ->controller('loginCtrl', '\App\Components\User\Login\LoginController')

    // Register the Signup Controller
    ->controller('signupCtrl', '\App\Components\User\Signup\SignupController')

    // Register the Logout Controller
    ->controller('logoutCtrl', '\App\Components\User\Logout\LogoutController')

    // Register the User Service
    ->service('userService', '\App\Components\User\UserService');