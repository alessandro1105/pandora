angular
    .module('file')


    .directive('fileBrowser', function (readFile, StorageService) {
        'use strict';

        return {
            template: '<input type="file" style="display: none;" />' +
                '<ng-transclude></ng-transclude>',
            transclude: true,
            link: function (scope, element) {
                var fileInput = element.children('input[file]');
                
                fileInput.on('change', function (event) {
                    var file = event.target.files[0];
                    readFile(file).then(function (content) {
                        StorageService.uploadFile(file, content);
                    });
                });
                
                element.on('click', function () {
                    fileInput[0].click();
                });
            }
        };
    });