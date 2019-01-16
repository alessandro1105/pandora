angular
    .module('user')

    .factory('UserService', function () {

        // Log the user
        function login() {

        }

        // Logout the user
        function logout() {

        }

        // Check if the user is logged
        function logged() {

        }

        // Sign up a user
        function signup() {

        }


        // Return the service object
        return {
            login: login,
            logout: logout,
            logged: logged,
            signup: signup,
            get user() {
                if (isLogged) {
                    return user;
                }
                return {}
            },
            get isLogged() {
                return isLogged;
            }
        }

    });