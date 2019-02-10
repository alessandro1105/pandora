angular
    .module('listing')

    .controller('listingCtrl', function ($scope, StorageService, UserService, API_BASE, ) {
        var vm = this;

        // Current listing
        vm.listing = [];
        vm.versions = [];
        vm.file = '';

        // Indicate if showing versions or not
        vm.showingVersions = false;

        // Current path
        vm.path = [{
            name: 'Pandora\'s box',
            path: '/'
        }];

        // Show the spinner
        vm.spinnerVisible = true;

        // Register the scope if the listing change
        $scope.$watch(function () {
            return StorageService.listing
        }, function () {
            vm.showingVersions = false;
            // Salve the listing
            vm.listing = StorageService.listing;
            // Regenerate the path
            regeneratePath(StorageService.current.path);
            // Hide the spinner
            vm.spinnerVisible = false;
        });

        // First time it's visible so call the changeDirectory
        StorageService.changeDirectory('/');

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

        // Show version of a file
        vm.showVersions = function (file) {
            // Save the file
            vm.file = file;
            // Show the spinner
            vm.spinnerVisible = true;

            // Get the versions
            StorageService.getVersions(file)
                .then(function (versions) {
                    vm.showingVersions = true;
                    vm.versions = versions.versions;

                    regeneratePath(versions.path, true);

                    // Hide the spinner
                    vm.spinnerVisible = false;
                })
            
        }


        // Regenerate path for the navigation
        function regeneratePath(path, version = false) {
            var pathSplitted = path.split('/');

            var currentPath = [{
                name: 'Pandora\'s box',
                fn: function () {
                    vm.changeDirectory('/')
                }
            }];

            var current = '/';

            for (i = 0; i < pathSplitted.length; i++) {
                if (pathSplitted[i] == '') {
                    continue;
                }

                current += pathSplitted[i] + '/'
                currentPath.push({
                    name: pathSplitted[i],
                    fn: function () {
                        vm.changeDirectory(current)
                    }
                });
            }

            if (version) {
                currentPath[currentPath.length -1].fn = function () {}; // Don't do anything
                currentPath.push({
                    name: 'File versions',
                    fn: function () {
                        vm.showVersions(path)
                    }
                })
            }

            vm.path = currentPath;

        }

    });