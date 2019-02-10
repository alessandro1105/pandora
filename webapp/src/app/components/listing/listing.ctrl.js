angular
    .module('listing')

    .controller('listingCtrl', function ($scope, StorageService, UserService, API_BASE, ) {
        var vm = this;

        // Current listing
        vm.listing = [];

        // Show the spinner
        vm.spinnerVisible = true;

        // First time it's visible so call the changeDirectory
        StorageService.changeDirectory('/')
            .then(function (current) {
                // Salve the listing
                vm.listing = StorageService.listing;
                // Hide the spinner
                vm.spinnerVisible = false;

            }, function () { });

        // Register the scope if the listing change
        $scope.$watch(function () {
            return StorageService.listing
        }, function () {
            // Salve the listing
            vm.listing = StorageService.listing;
            // Hide the spinner
            vm.spinnerVisible = false;
        });

        // Change current directory
        vm.changeDirectory = function (dir) {
            StorageService.changeDirectory(dir);
            // Show the spinner
            vm.spinnerVisible = true;
        }

        // Download file
        vm.download = function (path) {
            var downloadLink = angular.element('<a></a>');
            downloadLink.attr('href', StorageService.downloadBaseUrl + path);
            downloadLink[0].click();
        }

        // Handle double click
        vm.doubleClick = function (element) {
            if (element.type == 'directory') {
                vm.changeDirectory(element.path);
            } else {
                vm.download(element.path);
            }
        }


        console.log(UserService.user.uuid);

        vm.test = function () {
            alert('test');
        }

    });