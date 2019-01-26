angular
    .module('user')

    .controller('signupCtrl', function ($state, $scope, UserService, AlertService) {
        var vm = this;

        vm.signup = function (username, email, password, tos) {
            if ($scope.signupForm.$valid && tos) {
                // Try to signup the user
                UserService.signup(username, email, password)
                    .then(
                        function () {
                            $state.go('listing');

                        }, function (error) {
                            if (error.status == 409 && error.data.errors.usernameRegistered) {
                                AlertService.warning('Your username is already registered sob sob :(');
                            } else if (error.status == 409 && error.data.errors.emailRegistered) {
                                AlertService.warning('Do you already have an account? The email is already registered');
                            } else {
                                AlertService.danger('Something goes terribly wrong :(');
                            }
                        }
                    );
            }

            if (!tos) {
                AlertService.danger('You need to accept our TOS if you\'d like an account ;)');
            }

        };

    });