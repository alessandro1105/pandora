<?php
/*
	RouterServiceProvider (routerProvider) service

	Service provider for $router service. It can register states, when conditions or one otherwhise condition.


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

	namespace AzzurroFramework\Modules\AF\Router;

	use \InvalidArgumentException;


	/*
		STATE DEFINITION

		$routeProvider.state([
			"name" => "state_name", 				// Optional, must be a valid variable name and be unique
			"url" => "state/url", 					// Optional, must be a valid url path
			"controller" => "controller_name", 		// Optional
			"template" => "template in string", 	// Optional, only template or templateUrl in the same state definition
			"templateUrl" => "path/to/find/tpl",	// Optional, only template or templateUrl in the same state definition
			"methods" => "GET|POST|PUT|..."			// Optional, if not present, it is interpreted as all methods
		]);
	*/


	//--- RouterServiceProvider provider ----
	final class RouterServiceProvider implements ServiceProvider {

		// Variable that contains all the configuration for the service
		private $config;


		// Constructor
		public function __construct() {
			// Default settings
			$this->config = [
				"states" => array(),
				"whenConditions" => array(),
				"otherwhise" => null,
				"templateProcessor" => "template" // Default templateProcessor service
			];
		}

		// Register a state
		public function state(array $state) {
			// State definition cannot be empty
			if ($state == array()) {
				throw new InvalidArgumentException("State definition cannot be empty!");
			}
			// url
			if (isset($state['url']) and !preg_match("/^(\/(\:?[a-zA-Z0-9_]+))*(\/|(\/?\?[a-zA-Z0-9_]+(\&[a-zA-Z0-9_]+)*))?$/", $state['url'])) {
				throw new InvalidArgumentException("'url' must be a valid url path!");
			}
			// name
			if (isset($state['name'])) {
				// Check if the name is valid
				if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_-\x7f-\xff]*$/', $state['name'])) {
					InvalidArgumentException("'name' field must be a valid state name!");
				}
				// Check the uniqueness of the name
				foreach ($this->config['states'] as $stateDefined) {
					if ($stateDefined['name'] == $state['name']) {
						throw new InvalidArgumentException("'name' field must be unique!");
					}
				}
			}
			// controller
			if (isset($state['controller']) and !preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_-\x7f-\xff]*$/', $state['controller'])) {
				throw new InvalidArgumentException("'controller' field must be a valid controller name!");
			}
			// template
			if (isset($state['template']) and !is_string($state['template'])) {
				throw new InvalidArgumentException("'template' field must be a string!");
			}
			// templateUrl
			if (isset($state['templateUrl']) and !file_exists($state['templateUrl'])) {
				throw new InvalidArgumentException("'templateUrl' field must be a valid template file!");
			}
			// method
			if (isset($state['methods'])) {
				// Explode the methods
				$methods = explode('|', $state['methods']);

				// Check if the methods name are correct
				foreach($methods as $method) {
					switch ($method) {
						case 'GET':
						case 'HEAD':
						case 'POST':
						case 'PUT':
						case 'PUT':
						case 'DELETE':
						case 'CONNECT':
						case 'OPTIONS':
						case 'TRACE':
						case 'PATCH':
							// Valid methods
							break;
						default:
							throw new InvalidArgumentException("'methods' field must contains valid methods name (they must be uppercase)!");
					}
				}

				$state['methods'] = $methods;
			}

			// Save the state definition
			$this->config['states'][] = $state;

			// Chain API
			return $this;
		}

		// Register a callback executed when the requested url matches
		public function when(string $url, $callback) {
			//check the argument correctness
			if (!preg_match("/^(\/[a-zA-Z0-9_]+)*(\/|(\/?\?[a-zA-Z0-9_]+(\&[a-zA-Z0-9_]+)*))?$/", $url)) { // Url must be a valid url path
				throw new InvalidArgumentException("\$url must be a valid url path!");
			}
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($callback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}

			// Save the when condition and callback
			$this->config['whenConditions'][] = [
				"url" => $url,
				"callback" => $callback
			];

			// Chain API
			return $this;
		}

		// Register a callback executed when no states and no when conditions matches
		public function otherwise($callback) {
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($callback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}

			// Save the otherwhise callback
			$this->config['otherwhise'] = $callback;

			// Chain API
			return $this;
		}

		// Set a custom message processor service
		public function setTemplateProcessor(string $name) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid service name!");
			}

			$this->config['templateProcessor'] = $name;

			// Chain API
			return $this;
		}

		// Getting the service
		public function get() {

			// Prepare the data to pass to the service
			$config = &$this->config;

			// Factory function
			return function ($injector, $controller) use ($config) {
				return new RouterService($config, $injector, $controller);
			};
		}

	}
