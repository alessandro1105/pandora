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
			'common',
			'user'
		])

		// Config the module
		->config(function ($requestProvider, $routerProvider) {

			$requestProvider->session
				->setPrefix('pndr-')
				->setSavePath('/tmp');

			// Create simple 404 handler
			$routerProvider
				->otherwise(function () {
					http_response_code(404);
				});
		})

		// Run the module
		->run(function ($request) {
			$request->session->start();
		});