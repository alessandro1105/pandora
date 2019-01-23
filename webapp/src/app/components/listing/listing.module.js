// Page module
angular
    .module('listing', [

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
                name: 'listing',
                url: '/',
                parent: 'page',
                templateUrl: 'app/components/listing/listing.tpl.html',
                resolve: {
                    // Check if the user is allowed to access this type of pages
                    autentication: function (UserService) {
                        UserService.authenticated(); // Check if the user is autenticated
                    }
                }
            });

    });


