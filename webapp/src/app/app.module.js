angular
    .module('pandora', [
        'ui.router', // UI Router

        'page', // Page Module
        'homepage', // Homepage Module
        'error404', // Error 404 Module
        'user' // User Module
    ])

    .config(function ($urlRouterProvider, $locationProvider, $urlMatcherFactoryProvider) {
        // UI Router non strict mode and case insensitive
        $urlMatcherFactoryProvider.caseInsensitive(true);
        $urlMatcherFactoryProvider.strictMode(false);

        // Enabling HTML5 MURLs
        $locationProvider.html5Mode(true);

        // Automatically redirect to homepage at startup
        $urlRouterProvider
            .when('/', function ($state) {
                $state.go('homepage');
            })
            .otherwise(function ($injector) {
                // Get $state service from $injector
                $state = $injector.get("$state");
                // Transit to error404 state
                $state.go('error404');
            });
    });