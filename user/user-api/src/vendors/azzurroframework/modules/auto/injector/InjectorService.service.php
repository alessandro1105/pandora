<?php
/*
	InjectorService (injector) service

	Injector service, it exponse the dependency injection resolution for service and constant.
	It can be used to call a function wich depends on services or constants.


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

	namespace AzzurroFramework\Modules\Auto\Injector;

	use \InvalidArgumentException;

	use \AzzurroFramework\Core\Injector\Injector;
	

	//--- InjectorService service ----
	final class InjectorService {

		// Injector instance
		private $injector;


		// Constructor
		public function __construct(Injector $injector) {
			$this->injector = $injector;
		}

		// Get method that returns the service or constant specified
		public function get(string $name) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$app argument must be a valid service name!");
			}
			return $this->injector->getService($name);
		}

		// Returns the resolution of the dependencies of the callback
		public function resolve($callback) {
			// Checking arguments correctness
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($callback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}
			return $this->injector->resolve($callback);
		}

		// Resolve the dependencies of the callaback and execute it
		public function call($callback) {
			// Checking arguments correctness
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($callback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}
			return $this->injector->call($callback);
		}

	}