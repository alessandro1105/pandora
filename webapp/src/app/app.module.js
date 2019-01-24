angular
    .module('pandora', [
        'ui.router', // UI Router
        'ui.bootstrap', // UI Bootstrap

        'page', // Page Module
        'listing', // Homepage Module
        'error404', // Error 404 Module
        'user', // User Module

        'loading-spinner' // Loading Spinner Module
    ])

    // API base endpoint
    .constant('API_BASE', '/api')

    .config(function ($urlRouterProvider, $locationProvider, $urlMatcherFactoryProvider) {
        // UI Router non strict mode and case insensitive
        $urlMatcherFactoryProvider.caseInsensitive(true);
        $urlMatcherFactoryProvider.strictMode(false);

        // Enabling HTML5 MURLs
        $locationProvider.html5Mode(true);

        // Automatically redirect to homepage at startup
        $urlRouterProvider
            .when('/', function ($state) {
                $state.go('listing');
            })
            .otherwise(function ($injector) {
                // Get $state service from $injector
                $state = $injector.get("$state");
                // Transit to error404 state
                $state.go('error404');
            });
    })

    .controller('mainCtrl', function ($timeout, UserService) {
        var vm = this;

        // set that the content is not loaded
        vm.spinnerVisible = true;

        // When the promise is resolved hide the page loader
        UserService.logged()
            .then(
                function () {},
                function () {}
            ).finally(function () {
                // Hide the page loader after 1 seconds the request
                $timeout(function () {
                    vm.spinnerVisible = false;
                }, 1000);
            });

    });