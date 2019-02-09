<?php
/*
	Index file

	- define basic constants
	- require the vendros/azzurroframework/bootstrap.php file
	

	---- Changelog ---
	Rev 1.0 - November 20th, 2017
			- Basic functionality


	Copyright 2017 Alessandro Pasqualini
	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at
    	http://www.apache.org/licenses/LICENSE-2.0
	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.

	@author    Alessandro Pasqualini <alessandro.pasqualini.1105@gmail.com>
	@url       https://github.com/alessandro1105
*/

	// Debug
	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	use \AzzurroFramework\Core\AzzurroFramework;


	// --- VERSION ---
	define('__AF_VERSION__', "0.0.1-pre-alfa");


	// --- DEFINE DIRECTORIES ---
	// Server root
	define('__AF_ROOT__', __DIR__);
	// Vendors directory
	define('__AF_VENDORS_DIR__', __AF_ROOT__ . '/vendors');
	// Logs directory
	define('__AF_LOGS_DIR__', __AF_ROOT__ . '/logs');
	// User application directory
	define('__AF_APP_DIR__', __AF_ROOT__ . '/app');

	// Azzurro Framework core directory
	define('__AF_CORE_DIR__', __AF_VENDORS_DIR__ . '/azzurroframework/core');
	// Azzurro Framework core modules
	define('__AF_MODULES_DIR__', __AF_VENDORS_DIR__ . '/azzurroframework/modules');


	// --- BOOTSTRAP ---

	// Require vendors autoloader
	require_once(__AF_VENDORS_DIR__ . "/autoload.php");

	// Require core autoloader
	require_once(__AF_CORE_DIR__ . "/autoloader.php");

	// // Instantiate framework main class
	$azzurro = AzzurroFramework::getInstance();

	// // Require core modules autoloader
	require_once(__AF_MODULES_DIR__ . '/autoloader.php');
	// // Require user app autolaoder
	require_once(__AF_APP_DIR__ . "/autoloader.php");

	// // Bootstrap and launch user app
	$azzurro->bootstrap();
