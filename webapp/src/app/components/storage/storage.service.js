angular
    .module('storage')

    .factory('StorageService', function ($q, $http, UserService, API_BASE, API_STORAGE_SERVICE) {
        
        // Current listing
        var listing = [];
        
        // Current position
        var current = {
            name: '/',
            path: '/'
        }

        // Function to refresh thelisting of the directories
        function refreshListing(dir) {
            var deferred = $q.defer();
            
            $http({
                method: 'GET',
                url: API_BASE + API_STORAGE_SERVICE + '/' + UserService.user.uuid + '/' + dir
            }).then(function (response) {
                // Save the data
                current.name = response.data.name;
                current.path = response.data.path;
                listing = response.data.listing;

                deferred.resolve(current);

            }, function errorCallback(response) {

                deferred.reject(response);
            });
            
            return deferred.promise;
        }

        // Change the current directory
        function changeDirectory(dir) {
            return refreshListing(dir);
        }

        // Creare a new directory in the current position
        function newDirectory(dir) {
            var deferred = $q.defer();
            
            $http({
                method: 'PUT',
                url: API_BASE + API_STORAGE_SERVICE + '/' + UserService.user.uuid + '/' + current.path + '/' + dir + '?directory=true'
            }).then(function (response) {
                deferred.resolve();

                refreshListing(current.path);

            }, function errorCallback(response) {
                deferred.reject(response);

                console.log('error');
            });
            
            return deferred.promise;
        }

        // Upload a file in the current position
        function uploadFile() {

        }

        // Get all versions of a file
        function getVersions(file) {
            var deferred = $q.defer();
            
            $http({
                method: 'GET',
                url: API_BASE + API_STORAGE_SERVICE + '/' + UserService.user.uuid + '/' + file + '?info=true'
            }).then(function (response) {

                deferred.resolve(response.data);

            }, function errorCallback(response) {

                deferred.reject(response);
            });

            return deferred.promise;
        }

        // Delete an element
        function deleteElement(path) {
            var deferred = $q.defer();
            
            $http({
                method: 'DELETE',
                url: API_BASE + API_STORAGE_SERVICE + '/' + UserService.user.uuid + path
            }).then(function () {
                
                deferred.resolve();
                refreshListing(current.path);

            }, function errorCallback(response) {

                deferred.reject(response);
            });

            return deferred.promise;
        }

        // Return the service object
        return {
            changeDirectory: changeDirectory,
            newDirectory: newDirectory,
            uploadFile: uploadFile,
            getVersions: getVersions,
            deleteElement: deleteElement,
            get listing() {
                return listing;
            },
            get current() {
                return current;
            },
            get downloadBaseUrl() {
                return API_BASE +  API_STORAGE_SERVICE + '/' + UserService.user.uuid;
            }
        }

    });