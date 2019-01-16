<?php
/*
	Azzurro Framework main class

	Main class of the framework. It permits to register modules and handle all the framework functionality.


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

	namespace AzzurroFramework\Core;

	use \InvalidArgumentException;

	use \AzzurroFramework\Core\App\Exceptions\AppModuleNotRegisteredException;

	use \AzzurroFramework\Core\Module\Exceptions\ModuleAlreadyRegisteredException;
	use \AzzurroFramework\Core\Module\Exceptions\ModuleNotFoundException;

	use \AzzurroFramework\Core\Injector\Injector;
	use \AzzurroFramework\Core\Module\Module;


	//--- AzzurroFramework class ----
	final class AzzurroFramework {

		// Events generated by the framework when all the framework is ready
		const EVENT_READY = "AF:ready";
		// Events generated by the framework at the shutdown
		const EVENT_SHUTDOWN = "AF:shutdown";
		// Events generated by the framework to execute all the callbacks
		const EVENT_CALLBACK = "AF:callback";


		// Singleton instance
		private static $self = null;

		// Application modules
		private $modules;
		// App module
		private $app;
		// Injector
		private $injector;


		//--- SINGLETON COSTRUCTOR ----
		// Singleton object
		public static function getInstance() {
			// If there isn't an instance, then create it
			if (self::$self == null) {
				self::$self = new self();
			}

			// Return the instance
			return self::$self;
		}

		// Private contructor because is singleton
		private function __construct() {
			// Prepare the variables
			$this->modules = array();
			$this->app = null;
			$this->injector = new Injector($this->modules);

		}

		//--- CERATING AND GETTING THE MAIN MODULE ---
		// Method to create and get the main module of the application
		public function app(string $name, array $dependencies = null) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid module name!");
			}
			if (!is_null($this->app) and $app != $name) {
				throw new ModuleAlreadyRegisteredException("App module has been already registered!");
			}

			// Use module method to retrive or create the module
			$module = $this->module($name, $dependencies);

			if (!is_null($dependencies)) {
				$this->app = $name;
			}

			// Return the Module instance
			return $module;
		}

		//--- CREATING AND GETTING MODULES ---
		// Method to create and get a module
		public function module(string $name, array $dependencies = null) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid module name!");
			}
			if (!is_null($dependencies)) {
				foreach ($dependencies as $dependency) {
					if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $dependency)) {
						throw new InvalidArgumentException("\$dependencies argument must be a valid array of module names!");
					}
				}
			}
			// Check if the module requested exists
			if (!array_key_exists($name, $this->modules) and is_null($dependencies)) {
				throw new ModuleNotFoundException("Module '$name' has not been registered!");
			}

			// If the module doesn't exists, create a new one
			if (!array_key_exists($name, $this->modules) and !is_null($dependencies)) {
				// Create the new module
				$this->modules[$name] = [
					'dependencies' => $dependencies
				];
			}

			// Check if the module is the app one
			if ($this->app == $name) {
				return $this->app($name, $dependencies);
			}

			// Return the Module instance
			return new Module($this->modules[$name]);
		}

		//--- EXECUTING THE FRAMEWORK ---
		// Boostrap the framework
		public function bootstrap() {
			if (is_null($this->app)) {
				throw new AppModuleNotRegisteredException("App module has not been defined!");
			}

			// Resolve the dependencies of the app module
			$this->injector->resolveApplicationDependencies($this->app);

			// Getting $event service
			$event = $this->injector->getService("event");
			$azzurro = $this->injector->getService("azzurro");

			// Generate event indicating the framework is ready
			$event->emit(self::EVENT_READY);

			// Generate event to start the routing process
			$event->emit($azzurro->getRouteEvent());

			// Generate event to start all the callbacks
			$event->emit(self::EVENT_CALLBACK);

			// generate event indicating the framework is shutting down
			$event->emit(self::EVENT_SHUTDOWN);

		}

		public function __get($property) {
			switch ($property) {
				case 'injector':
					return $this->getInjector();
					break;
				case 'version':
					return $this->getVersion();
					break;
				
				default:
					return null;
			}
		}

		// Return framework injector instance
		private function getInjector() {
			return $this->injector;
		}

		// Return the version of the framework
		private function getVersion() {
			return __AF_VERSION__;
		}

	}
