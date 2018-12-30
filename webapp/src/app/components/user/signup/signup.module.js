angular
    .module('signup', [

    ])

    .config(function ($stateProvider, $locationProvider, $urlMatcherFactoryProvider) {
        // UI Router non strict mode and case insensitive
        $urlMatcherFactoryProvider.caseInsensitive(true);
        $urlMatcherFactoryProvider.strictMode(false);

        // Enabling HTML5 MURLs
        $locationProvider.html5Mode(true);

        // Sidebar container state (Used only to place the sidebar container tpl on the page)
        $stateProvider
            .state({
                name: 'signup',
                url: '/signup',
                templateUrl: 'app/components/user/signup/signup.tpl.html'
            });

    });