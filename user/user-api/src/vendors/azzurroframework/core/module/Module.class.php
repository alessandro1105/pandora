<?php
/*
	Module class

	This class defines a module and permits the registration of the components.


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

	namespace AzzurroFramework\Core\Module;

	use \InvalidArgumentException;

	use \AzzurroFramework\Core\Constant\Exceptions\ConstantAlreadyRegisteredException;

	use \AzzurroFramework\Core\Controller\Exceptions\ControllerAlreadyRegisteredException;

	use \AzzurroFramework\Core\Filter\Exceptions\FilterAlreadyRegisteredException;

	use \AzzurroFramework\Core\Module\Exceptions\ModuleConfigAlreadyRegisteredException;
	use \AzzurroFramework\Core\Module\Exceptions\ModuleRunAlreadyRegisteredException;

	use \AzzurroFramework\Core\Service\Exceptions\ServiceAlreadyRegisteredException;


	use \AzzurroFramework\Core\Service\Interfaces\ServiceProviderInterface;


	//--- Module class ----
	final class Module {

		// Module
		private $module;

		// Contructor of the module
		public function __construct(array &$module) {
			// Save the module reference
			$this->module = &$module;
		}


		// Register a function that will be runned during the dependencies resolution
		public function config(callable $callback) {
			// Check if there is already a config function
			if (array_key_exists('config', $this->module)) {
				throw new ModuleConfigAlreadyRegisteredException("Config callback has alredy been registered inside this module!");
			}

			$this->module['config'] = $callback;

			// Chain API
			return $this;
		}

		// Register a function that will be runned after all the dependencies have been resolved
		public function run(callable $callback) {
			// Check if there is already a run function
			if (array_key_exists('run', $this->module)) {
				throw new ModuleRunAlreadyRegisteredException("Run callback has alredy been registered inside this module!");
			}

			$this->module['run'] = $callback;

			// Chain API
			return $this;
		}

		// Register a service without the provider
		public function service(string $name, string $class) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid service name!");
			}
			if (!preg_match('/^(\\\[a-zA-Z_\x7f-\xff]|[a-zA-Z_\x7f-\xff])[a-zA-Z0-9_\x7f-\xff\\\\]*$/', $class)) {
				throw new InvalidArgumentException("\$class argument must be a valid class name!");
			}
			// Check if there is already registered a service with this name
			if (array_key_exists('services', $this->module) and array_key_exists($name, $this->module['services'])) {
				throw new ServiceAlreadyRegisteredException("Service '$name' has alredy been registered inside this module!");
			}

			// Create the service
			if (!array_key_exists('services', $this->module)) {
				$this->module['services'] = array();
			}
			$this->module['services'][$name] = [
				"class" => $class
			];

			// Chain API
			return $this;
		}

		// Register a service factory function to create the service
		public function factory(string $name, callable $factory) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid service name!");
			}
			// Check if there is already registered a service with this name
			if (array_key_exists('services', $this->module) and array_key_exists($name, $this->module['services'])) {
				throw new ServiceAlreadyRegisteredException("Service '$name' has alredy been registered inside this module!");
			}

			// Create the service
			if (!array_key_exists('services', $this->module)) {
				$this->module['services'] = array();
			}
			$this->module['services'][$name] = [
				"factory" => $factory
			];

			// Chain API
			return $this;
		}

		// Register a service with provider
		public function provider(string $name, string $class) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid service name!");
			}
			if (!preg_match('/^(\\\[a-zA-Z_\x7f-\xff]|[a-zA-Z_\x7f-\xff])[a-zA-Z0-9_\x7f-\xff\\\\]*$/', $class)) {
				throw new InvalidArgumentException("\$class argument must be a valid class name!");
			}
			// Check if there is already registered a service with this name
			if (array_key_exists('services', $this->module) and array_key_exists($name, $this->module['services'])) {
				throw new ServiceAlreadyRegisteredException("Service '$name' has alredy been registered inside this module!");
			}

			// Create the service
			if (!array_key_exists('services', $this->module)) {
				$this->module['services'] = array();
			}
			$this->module['services'][$name] = [
				"class" => $class,
				"provider" => true
			];
			
			// Chain API
			return $this;
		}

		// Register a service with value
		public function value(string $name, $value) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid service name!");
			}
			// Check if there is already registered a service with this name
			if (array_key_exists('services', $this->module) and array_key_exists($name, $this->module['services'])) {
				throw new ServiceAlreadyRegisteredException("Service '$name' has alredy been registered inside this module!");
			}

			// Create the service
			if (!array_key_exists('services', $this->module)) {
				$this->module['services'] = array();
			}
			$this->module['services'][$name] = [
				"service" => $value
			];
			
			// Chain API
			return $this;
		}

		// Register a controller
		public function controller(string $name, string $class) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid controller name!");
			}
			if (!preg_match('/^(\\\[a-zA-Z_\x7f-\xff]|[a-zA-Z_\x7f-\xff])[a-zA-Z0-9_\x7f-\xff\\\\]*$/', $class)) {
				throw new InvalidArgumentException("\$class argument must be a valid class name!");
			}
			// Check if there is already registered a controller with this name
			if (array_key_exists('controllers', $this->module) and array_key_exists($name, $this->module['controllers'])) {
				throw new ControllerAlreadyRegisteredException("Controller '$name' has alredy been registered inside this module!");
			}

			// Create the controller
			if (!array_key_exists('controllers', $this->module)) {
				$this->module['controllers'] = array();
			}
			$this->module['controllers'][$name] = [
				"class" => $class
			];

			// Chain API
			return $this;
		}

		// Register a filter
		public function filter(string $name, callable $factory) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid filter name!");
			}
			// Check if there is already registered a filter with this name
			if (array_key_exists('filters', $this->module) and array_key_exists($name, $this->module['filters'])) {
				throw new FilterAlreadyRegisteredException("Filter '$name' has alredy been registered inside this module!");
			}

			// Create the controller
			if (!array_key_exists('filters', $this->module)) {
				$this->module['filters'] = array();
			}
			$this->module['filters'][$name] = [
				"factory" => $factory
			];

			// Chain API
			return $this;

		}

		// Register a constant
		public function constant(string $name, $constant) {
			// Check the correctness of the the arguments
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid constant name!");
			}
			// Check if there is already registered a service with this name
			if (array_key_exists('constants', $this->module) and array_key_exists($name, $this->module['constants'])) {
				throw new ConstantAlreadyRegisteredException("Constant '$name' has alredy been registered inside this module!");
			}

			// Create the service
			if (!array_key_exists('constants', $this->module)) {
				$this->module['constants'] = array();
			}
			$this->module['constants'][$name] = [
				"const" => $constant
			];
			
			// Chain API
			return $this;
		}

	}
