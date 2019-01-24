angular
    .module('user', [

    ])

    // API to log in a user
    .constant('API_USER_LOGIN', '/user/login')
    // API to log out a user
    .constant('API_USER_LOGOUT', '/user/logout')
    // API to signup a user
    .constant('API_USER_SIGNUP', '/user/signup')
    // API to check if a user is logged in
    .constant('API_USER_LOGGED', '/user/logged')

    // Define the states
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
                templateUrl: 'app/components/user/login/login.tpl.html',
                controller: 'loginCtrl',
                controllerAs: 'login',
                resolve: {
                    autentication: function ($q, $state, UserService) {
                        var deferred = $q.defer();

                        // Check if the user is not logged in
                        UserService.authenticated()
                            .then(
                                function () {
                                    deferred.reject();
                                    $state.go('listing');
                                },
                                function () {
                                    deferred.resolve();
                                }
                            );

                        return deferred.promise;
                    }
                }
            })

            .state({ // Sign up state
                name: 'signup',
                url: '/signup',
                templateUrl: 'app/components/user/signup/signup.tpl.html',
                resolve: {
                    autentication: function ($q, $state, UserService) {
                        var deferred = $q.defer();

                        // Check if the user is not logged in
                        UserService.authenticated()
                            .then(
                                function () {
                                    deferred.reject();
                                    $state.go('listing');
                                },
                                function () {
                                    deferred.resolve();
                                }
                            );

                        return deferred.promise;
                    }
                }
            });
    });