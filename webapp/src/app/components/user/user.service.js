angular
    .module('user')

    .factory('UserService', function ($q, $state, $http, API_BASE, API_USER_LOGIN, API_USER_LOGOUT, API_USER_SIGNUP, API_USER_LOGGED) {

        // User object
        var user = {};

        // Tells if the user is currently logged in
        var isUserLogged = false;

        // Array with all the promises to be resolved/rejected when the app check if the user is logged
        var promisesForCheck = [];
        // Tells if the first attempt has been made
        var isCheckDone = false;
        // True if there is already a login check request on fly
        var isCheckRequestOnFly = false;


        // Factory function to create a User object
        function User(data) {
            var user = data;

            return {
                get uuid() {
                    return user.uuid
                },
                get username() {
                    return user.username;
                },
                get email() {
                    return user.email;
                },
                get emailConfirmed() {
                    return user.emailConfirmed;
                },
                get registrationDate() {
                    return user.registrationDate;
                },
                get lastLoginDate() {
                    return user.lastLoginDate;
                }
            }
        }

        // Log the user
        function login(account, password) {
            // Create a promise
            var deferred = $q.defer();

            $http({
                method: 'POST',
                url: API_BASE + API_USER_LOGIN,
                headers: {
                    'Content-Type': 'application/json'
                },
                data: {
                    user: account,
                    password: password
                }

            // Success
            }).then(function (response) {
                // Saving the success login status
                isUserLogged = true;
                //Cerate a new user object
                user = User(response.data);
                // Resolve the promise
                deferred.resolve();

            // Error
            }, function () {
                isUserLogged = false;
                // Reset user object
                user = {};
                // Resolve the promises
                deferred.reject();
            });

    		// Return the promise
    		return deferred.promise;
        }

        // Logout the user
        function logout() {
            var deferred = $q.defer();

            // The prosise is always resolved because of the logout pourpose
    		$http({
    			method: 'POST',
    			url: API_BASE + API_USER_LOGOUT,
    			headers: {
       				'Content-Type': 'application/json'
     			}
    		}).then(
                function () {
                    // Resolving the promise
                    deferred.resolve();

                },
                function (data) {
                    // Resolving the promise
                    deferred.resolve();
                }
            );

            // Saving the success logout status
            isUserLogged = false;
            // Reste user object
            user = {};

    		return deferred.promise;
        }

        // Check if the user is logged
        function logged() {
            // Create a promise
    		var deferred = $q.defer();

            // If the first call has been made
            if (isCheckDone && isUserLogged) {
                deferred.resolve();

            } else if (isCheckDone && !isUserLogged) {
                deferred.reject();

            } else {

                // If the check request is on fly
                if (!isCheckRequestOnFly) {
                    isCheckRequestOnFly = true;

        			$http({
        				method: 'GET',
                        url: API_BASE + API_USER_LOGGED,
                        
        	        }).then(
                        // Success
                        function (response) {
                            // Saving the success login status
                            isUserLogged = true;

                            // Create a new user object
                            user = User(response.data);

                            // Resolve the promises
                            angular.forEach(promisesForCheck, function (value, key) {
                                value.resolve();
                            });
                        }, 
                        // Error
                        function () {
                            isUserLogged = false;
                            // Reset user object
                            user = {};
                            // Resolve the promises
                            angular.forEach(promisesForCheck, function (value, key) {
                                value.reject();
                            });
                        }
                    ).finally(function () {
        				// Request completed
        				isCheckRequestOnFly = false;
                        // The first check has been done
                        isCheckDone = true;
                        // The promises array can be empty
                        promisesForCheck = [];
        			});
                }

                // Push the promise in the promise array
                promisesForCheck.push(deferred);
            }

    		// Return the promise
    		return deferred.promise;
        }

        // Sign up a user
        function signup(username, email, password) {
            // Create a promise
            var deferred = $q.defer();
            
            $http({
    			method: 'POST',
    			url: API_BASE + API_USER_SIGNUP,
    			headers: {
       				'Content-Type': 'application/json'
     			},
    			data: {
                    username: username,
                    email: email,
    				password: password
    			}

            // Success
            }).then(
                function () {
                    // Resolve the promise
                    deferred.resolve();
                // Error
                }, 
                function (response) {
                    // reject the promise and pass error information
                    deferred.reject(response);
                }
            );
            
            // Return the promise
    		return deferred.promise;
        }

        // Return a promise that will be resolved if the user is logged in, rejected otherwise
        function authenticated() {
            var deferred = $q.defer();

            logged()
                .then(
                    function () {
                        deferred.resolve();
                    },
                    function () {
                        deferred.reject();
                        $state.go('login');
                    }
                );

            return deferred.promise;
        }

        // Return the service object
        return {
            login: login,
            logout: logout,
            logged: logged,
            signup: signup,
            authenticated: authenticated,
            get user() {
                if (isLogged) {
                    return user;
                }
                return {};
            },
            get isLogged() {
                return isLogged;
            }
        }

    });