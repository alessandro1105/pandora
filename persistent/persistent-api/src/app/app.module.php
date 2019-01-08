<?php
/*
	User application main file

	This file is the main file of your application.
	Inside this file you can develop your main application module

	DO NOT DELETE THIS FILE otherwise your application will not be loaded.
*/

	// Application main module
	$azzurro
		->app("app", [
			'persistent'
		])

		->config(function ($routerProvider) {

			$routerProvider
				->otherwise(function () {
					http_response_code(404);
				});
		});