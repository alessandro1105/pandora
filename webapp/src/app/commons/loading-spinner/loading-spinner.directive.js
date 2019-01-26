// Loader directive
angular
	.module('loading-spinner')

	.directive("loadingSpinner", function() {

		return {
			restrict: "E",
			replace: true,
			templateUrl: "app/commons/loading-spinner/loading-spinner.tpl.html"
		}

	});
