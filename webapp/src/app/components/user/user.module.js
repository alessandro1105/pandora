angular
    .module('user', [

    ])

    .config(function ($stateProvider, $locationProvider, $urlMatcherFactoryProvider) {
        // UI Router non strict mode and case insensitive
        $urlMatcherFactoryProvider.caseInsensitive(true);
        $urlMatcherFactoryProvider.strictMode(false);

        // Enabling HTML5 MURLs
        $locationProvider.html5Mode(true);

        // Sidebar container state
        $stateProvider
            .state({ // Login state
                name: 'login',
                url: '/login',
                templateUrl: 'app/components/user/login/login.tpl.html'
            })

            .state({ // Sign up state
                name: 'signup',
                url: '/signup',
                templateUrl: 'app/components/user/signup/signup.tpl.html'
            });
    })