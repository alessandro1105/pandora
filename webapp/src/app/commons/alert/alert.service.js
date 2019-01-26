angular
    .module('alert')

    .service("AlertService", function ($templateCache, $http, $compile, $rootScope, $timeout) {

        // Template urls
        var templateUrl = {
            info: 'app/commons/alert/alert-info.tpl.html',
            success: 'app/commons/alert/alert-success.tpl.html',
            warning: 'app/commons/alert/alert-warning.tpl.html',
            danger: 'app/commons/alert/alert-danger.tpl.html'
        }

        // Load the template
        function show(args) {
            // Check if the template is in the cache
            template = $templateCache.get(args.templateUrl);
            // If the template is in the cache
            if (template) {
                render(template, args);

                // The template is not in the cache
            } else {
                // load it via $http only if it isn't default template and template isn't exist in template cache
                // cache:true means cache it for later access.
                $http({
                    mathod: 'GET',
                    url: args.templateUrl,
                    cache: true
                }).then(function(response) {
                    // Put the template into the cache
                    $templateCache.put(args.templateUrl, response.data);

                    // Render the template
                    render(response.data, args);

                }).catch(function(error){
                    throw new Error('Template (' + args.templateUrl + ') could not be loaded. ' + error);
                });
            }
        }

        // Render the template following the specifications
        function render(template, args) {
            // Create a new scope for the alert
            var scope = $rootScope.$new();
            // Fill the scope
            scope.message = args.message;
            // Compile the element
            var element = $compile(template)(scope);
            // Append the element to the body
            angular.element(document.querySelector('body')).append(element);

            // After 1 second from the timeout destroy the element
            $timeout(function () {
                scope.$destroy();
                element.remove();
            }, args.timeout);
        }

        // Show info alert
        function info(message) {
            // Render the template
            show({
                templateUrl: templateUrl.info,
                message: message,
                timeout: 3000
            });
        }

        // Show success alert
        function success(message) {
            // Render the template
            show({
                templateUrl: templateUrl.success,
                message: message,
                timeout: 3000
            });
        }

        // Show warning alert
        function warning(message) {
            // Render the template
            show({
                templateUrl: templateUrl.warning,
                message: message,
                timeout: 3000
            });
        }

        // Show danger alert
        function danger(message) {
            // Render the template
            show({
                templateUrl: templateUrl.danger,
                message: message,
                timeout: 3000
            });
        }

        // Return the service object
        return {
            info: info,
            success: success,
            warning: warning,
            danger: danger
        }

    });
