angular
    .module('file')

    .factory('readFile', function ($window, $q) {
        'use strict';
    
        var readFile = function (file) {
            var deferred = $q.defer(),  
                reader = new $window.FileReader();
    
            reader.onload = function (ev) {
                var content = ev.target.result;
                deferred.resolve(content);
            };
    
            reader.readAsText(file, file.type);
            return deferred.promise;
        };
    
        return readFile;
    });