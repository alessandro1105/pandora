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
                'name' => 'login',
                'url' => '/user/login',
                'controller' => 'loginCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Singup state
                'name' => 'signup',
                'url' => '/user/signup',
                'controller' => 'signupCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Singup state
                'name' => 'logout',
                'url' => '/user/logout',
                'controller' => 'logoutCtrl',
                'methods' => 'POST'
            ])

            ->state([ // Singup state
                'name' => 'logged',
                'url' => '/user/logged',
                'controller' => 'loggedCtrl',
                'methods' => 'GET'
            ]);
    })

    // Register the Login Controller
    ->controller('loginCtrl', '\App\Components\User\Login\LoginController')

    // Register the Signup Controller
    ->controller('signupCtrl', '\App\Components\User\Signup\SignupController')

    // Register the Logout Controller
    ->controller('logoutCtrl', '\App\Components\User\Logout\LogoutController')

    // Register the Logged Controller
    ->controller('loggedCtrl', '\App\Components\User\Logged\LoggedController')

    // Register the User Service
    ->service('userService', '\App\Components\User\UserService');