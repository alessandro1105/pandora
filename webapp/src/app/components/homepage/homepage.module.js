// Page module
angular
    .module('homepage', [

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
                name: 'homepage',
                url: '/',
                parent: 'page',
                templateUrl: 'app/components/homepage/homepage.tpl.html'
            });

    });


