<?php
/*
	AzzurroServiceProvider (azzurroProvider) service

	Service provider for $azzurro, it can be used to configure the framework.


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

	// Strict type hint
	declare(strict_types = 1);

	namespace AzzurroFramework\Modules\Auto\Azzurro;

	use \InvalidArgumentException;


	//--- AzzurroServiceProvider provider ---
	final class AzzurroServiceProvider implements ServiceProvider {

		// Variable that contains all the configuration for the service $azzurro (AzzurroFramework)
		private $config;

		// Constructor
		public function __construct() {
			// Default settings
			$this->config = [
				"routeEvent" => "",
				"callbackEvent" => ""
			];
		}

		// Set the event that the framework will generate to start the routing process
		public function setRouteEvent(string $event) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_:.\x7f-\xff]*$/', $event)) {
				throw new InvalidArgumentException("\$event argument must be a valid event name!");
			}

			// Save the route event
			$this->config['routeEvent'] = $event;
		}

		// Set the event that the framework will generate to start all the registered callbacks
		public function setCallbackEvent(string $event) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_:.\x7f-\xff]*$/', $event)) {
				throw new InvalidArgumentException("\$event argument must be a valid event name!");
			}

			// Save the route event
			$this->config['callbackEvent'] = $event;
		}

		// Getting the service
		public function get() {
			return new AzzurroService($this->config);
		}

	}