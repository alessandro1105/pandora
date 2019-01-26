angular
    .module('user')

    .controller('loginCtrl', function ($state, $scope, UserService, AlertService) {
        var vm = this;

        vm.login = function (user, password) {

            if ($scope.loginForm.$valid) {

                UserService.login(user, password)
                    .then(
                        function () {
                            $state.go('listing');
                        }, 
                        function () {
                            AlertService.danger('Wrong combination! Try again :)')
                        }
                    );
            }
        }
        

    });