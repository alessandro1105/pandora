<?php
/*
	Injector class

	The injector is the base component of the framework. It resolve the dependency injection
	and create the varius components.


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

	namespace AzzurroFramework\Core\Injector;
	
	use \InvalidArgumentException;

	use \AzzurroFramework\Core\Injector\Exceptions\ComponentNotFoundException;
	use \AzzurroFramework\Core\Injector\Exceptions\FactoryFunctionResultException;

	use \AzzurroFramework\Core\Constant\Exceptions\ConstantNotFoundException;

	use \AzzurroFramework\Core\Controller\Exceptions\ControllerClassNotFoundException;
	use \AzzurroFramework\Core\Controller\Exceptions\ControllerNotFoundException;

	use \AzzurroFramework\Core\Filter\Exceptions\FilterNotFoundException;

	use \AzzurroFramework\Core\Module\Exceptions\ModuleNotFoundException;

	use \AzzurroFramework\Core\Service\Exceptions\ServiceClassNotFoundException;
	use \AzzurroFramework\Core\Service\Exceptions\ServiceNotFoundException;
	use \AzzurroFramework\Core\Service\Exceptions\ServiceProviderClassNotFoundException;
	use \AzzurroFramework\Core\Service\Exceptions\ServiceProviderClassNotValidException;
	use \AzzurroFramework\Core\Service\Exceptions\ServiceProviderNotFoundException;
	use \AzzurroFramework\Core\Service\Exceptions\ServiceProviderResultException;

	use \ReflectionFunction;
	use \ReflectionMethod;
	use \ReflectionClass;

	use \AzzurroFramework\Core\Service\Interfaces\ServiceProviderInterface;


	//--- Injector class ----
	final class Injector {

		// Modules
		private $modules;
		// Order in wich they have been resolved
		private $modulesResolved;


		// Contructor of the injector
		public function __construct(&$modules) {
			$this->modules = &$modules;
			$this->modulesResolved = array();
		}


		// Method to start the resolution of the module dependencies. Called by AzzurroFramework class
		public function resolveApplicationDependencies($app) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $app)) {
				throw new InvalidArgumentException("\$app argument must be a valid module name!");
			}

			// Adding AzzurroFramework core modules to the resolved module (standalone)
			$this->modulesResolved[] = "auto";
			$this->modulesResolved[] = "af";

			// Resolve the dependencies of the app module
			$this->resolveModuleDependencies($app);

			// Call all the config of the modules in the order in which they have been resolved
			foreach ($this->modulesResolved as $module) {
				if (isset($this->modules[$module]['config'])) {
					$this->executeModuleConfig($this->modules[$module]['config']);
				}
			}

			// Call all the run function of the modules in the order in which they have been resolved
			foreach ($this->modulesResolved as $module) {
				if (isset($this->modules[$module]['run'])) {
					$this->executeCallback($this->modules[$module]['run']);
				}
			}
		}

		// Method that resolve the dependencies of a callback
		public function resolve($callback) {
			// Checking arguments correctness
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($callback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}

			// Instance of the reflection method
			$reflection = null;

			if (is_array($callback)) {
				$reflection = new ReflectionMethod($callback[0], $callback[1]);
			} else {
				$reflection = new ReflectionFunction($callback);
			}

			$parameters = $reflection->getParameters();
			$arguments = array();

			// Resolving dependencies for each parameter
			foreach ($parameters as $parameter) {
				$argument = $this->searchServiceOrConstant($parameter->name);
				
				// If has not been found neither a service or a constant
				if (is_null($argument)) {
					throw new ComponentNotFoundException("Component '$parameter->name' has not been registered!");
				}

				$arguments[] = $argument;
			}

			// Return the dependencies array
			return $arguments;
		}


		// Method that resolve the dependencies of a callback and run it
		public function call($callback) {
			// Checking arguments correctness
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($callback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}

			return $this->executeCallback($callback);
		}

		// Search a service and return it. Raise an exception if the service is not found
		public function getService($name) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid service name!");
			}

			// Searching the service
			$service = $this->searchService($name);
			// If the service has not been found
			if (is_null($service)) {
				throw new ServiceNotFoundException("Service '$name' has not been registered!");
			}

			// return the service
			return $service;
		}

		// Search a constant and return it. Raise an exception if the constant is not found
		public function getConstant($name) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid constant name!");
			}

			// Searching the constant
			$const = $this->searchService($name);
			// If the constant has not been found
			if (is_null($const)) {
				throw new ConstantNotFoundException("Constant '$name' has not been registered!");
			}

			// return the constant
			return $const;
		}

		// Search a filter and return it. Raise an exception if the filter is not found
		public function getFilter($name) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid filter name!");
			}

			// Searching the filter
			$filter = $this->searchFilter($name);
			// If the filter has not been found
			if (is_null($filter)) {
				throw new FilterNotFoundException("Filter '$name' has not been registered!");
			}

			// return the filter
			return $filter;
		}

		// Search a controller and return it. Raise an exception if the controller is not found
		public function getController($name) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
				throw new InvalidArgumentException("\$name argument must be a valid controller name!");
			}

			// Searching the controller
			$controller = $this->searchController($name);
			// If the controller has not been found
			if (is_null($controller)) {
				throw new ControllerNotFoundException("Controller '$name' has not been registered!");
			}

			// return the controller
			return $controller;
		}


		// Search a service and return it. Return null if the service is not found
		private function searchService($name) {
			// Try to find the service requested
			foreach ($this->modulesResolved as $module) {
				// If the module has services registered
				if (isset($this->modules[$module]['services'])) {
					foreach ($this->modules[$module]['services'] as $serviceName => &$service) {
						// If the service name is the one needed to be found
						if ($serviceName == $name) {
							// If the service is in resolution there is a recursive dependency error
							if (isset($service['resolving'])) {
								throw new ServiceNotFoundException("Service '$serviceName' has not been registered!");
							}
							// If the service is not instantiated
							if (!isset($service['service'])) {								
								
								// If the service is registered with the factory function
								if (isset($service['factory'])) {
									// The service is currently in resolution
									$service['resolving'] = true;
									// Call the factory function
									$result = $this->executeCallback($service['factory']);
									if (!is_object($result)) {
										throw new FactoryFunctionResultException("The result got from the factory function of the service '$serviceName' is not valid!");
									}
									// Save the service instance
									$service['service'] = $result;
									unset($service['resolving']);
									unset($service['factory']);
								
								// If the service is register with provider function
								} else if (isset($service['provider'])) {
									// Check if the provider has been alredy instantiate
									if (isset($service['class'])) {
										// Check if the class exists
										if (!class_exists($service['class'])) {
											throw new ServiceProviderClassNotFoundException("Service provider class '" . $service['class'] . "' not found!");
										}
										// Instantiate the provider
										$service['provider'] = new $service['class']();
										// Check if the class implements ServiceProviderInterface
										if (!($service['provider'] instanceof ServiceProviderInterface)) {
											throw new ServiceProviderClassNotValidException("Service provider class '" . $service['class'] . "' not implements 'ServiceProviderInterface'!");
										}
										unset($service['class']);
									}
									// Getting the result of the provider get function
									$result = $service['provider']->get();
									// If the result is a factory function
									if (is_callable($result)) {
										// The service is currently in resolution
										$service['resolving'] = true;
										// Call the factory function
										$result = $this->executeCallback($result);
										if (!is_object($result)) {
											throw new ServiceProviderResultException("The result got from service provider of '$serviceName' is not valid!");
										}
										unset($service['resolving']);
									} else if (!is_object($result)) {
										throw new ServiceProviderResultException("The result got from service provider of '$serviceName' is not valid!");
									}
									// Save the service instance
									$service['service'] = $result;
								
								// If the service is registered with the service function
								} else if (isset($service['class'])) {
									// Check if the class exists
									if (!class_exists($service['class'])) {
										throw new ServiceClassNotFoundException("Service class '" . $service['class'] . "' not found!");
									}
									// Resolve dependencies of the constructor
									$arguments = $this->resolve(array($service['class'], "__construct"));

									// Instanstiare the service
									$reflection = new ReflectionClass($service['class']);
    								$service['service'] = $reflection->newInstanceArgs($arguments);
    								unset($service['class']);
								}

							}

							// The service is instantiated
							return $service['service'];
						}
					}
				}
			}

			// The service has not been found
			return null;
		}

		// Search a provider and return it. Return null if the provider is not found
		private function searchProvider($name) {
			// Try to find the provider requested
			foreach ($this->modulesResolved as $module) {
				// If the module has services registered
				if (isset($this->modules[$module]['services'])) {
					foreach ($this->modules[$module]['services'] as $serviceName => &$service) {
						// If the service name is the one needed to be found
						if ($serviceName == $name and isset($service['provider'])) {
							// Check if the provider has been alredy instantiate
							if (isset($service['class'])) {
								// Check if the class exists
								if (!class_exists($service['class'])) {
									throw new ServiceProviderClassNotFoundException("Service provider class '" . $service['class'] . "' not found!");
								}
								// Instantiate the provider
								$service['provider'] = new $service['class']();
								// Check if the class implements ServiceProviderInterface
								if (!($service['provider'] instanceof ServiceProviderInterface)) {
									throw new ServiceProviderClassNotValidException("Service provider class '" . $service['class'] . "' not implements 'ServiceProviderInterface'!");
								}
								unset($service['class']);
							}
							// Return the provider
							return $service['provider'];
						}
					}
				}
			}

			// The provider has not been found
			return null;
		}

		// Search a constant and return it. Return null if the constant is not found
		private function searchConstant($name) {
			// Try to find the provider requested
			foreach ($this->modulesResolved as $module) {
				// If the module has services registered
				if (isset($this->modules[$module]['constants'])) {
					foreach ($this->modules[$module]['constants'] as $constantName => $constant) {
						// If the service name is the one needed to be found
						if ($constantName == $name) {
							return $constant['const'];
						}
					}
				}
			}

			// The constant has not been found
			return null;
		}

		// Search a filter and return it. Return null if the filter is not found
		private function searchFilter($name) {
			// Try to find the provider requested
			foreach ($this->modulesResolved as $module) {
				// If the module has services registered
				if (isset($this->modules[$module]['filters'])) {
					foreach ($this->modules[$module]['filters'] as $filterName => &$filter) {
						// If the service name is the one needed to be found
						if ($filterName == $name) {
							// If the filter has not created yet
							if (!isset($filter['filter'])) {
								// Call the factory function
								$result = $this->executeCallback($filter['factory']);
								if (!is_callable($result)) {
									throw new FactoryFunctionResultException("The result got from the factory function of the filter '$filterName' is not valid!");
								}
								// Save the filter
								$filter['filter'] = $result;
								unset($filter['factory']);
							}

							// Return the filter
							return $filter['filter'];
						}
					}
				}
			}

			// The constant has not been found
			return null;
		}

		// Search a controller and return it. Return null if the controller is not found
		private function searchController($name) {
			// Try to find the provider requested
			foreach ($this->modulesResolved as $module) {
				// If the module has services registered
				if (isset($this->modules[$module]['controllers'])) {
					foreach ($this->modules[$module]['controllers'] as $controllerName => &$controller) {
						// If the service name is the one needed to be found
						if ($controllerName == $name) {
							if (!isset($controller['controller'])) {
								// Check if the class exists
								if (!class_exists($controller['class'])) {
									throw new ControllerClassNotFoundException("Controller class '" . $controller['class'] . "' not found!"); 
								}
								// Instanstiare the controller
								$reflection = new ReflectionClass($controller['class']);
								$controller['controller'] = $reflection->newInstance();
								unset($controller['class']);
							}

							// Return the controller
							return $controller['controller'];
						}
					}
				}
			}

			// The constant has not been found
			return null;
		}

		// Search for a service or a constant and return it
		private function searchServiceOrConstant($name) {
			// Search for a service
			$argument = $this->searchService($name);
			// If the service has not been found
			if (is_null($argument)) {
				// Search for a constant
				$argument = $this->searchConstant($name);
			}

			// Return the compoent found or null if has not been found
			return $argument;
		}

		// Resolve the dependencies and call config function of the module 
		private function executeModuleConfig($callback) {
			// Getting the callback parameters
			$function = new ReflectionFunction($callback);
			$parameters = $function->getParameters();

			$arguments = array();

			// Resolving dependencies for each parameter
			foreach ($parameters as $parameter) { // Parameter is of the type serviceNameProvider or constantName
				$argument = null;
				
				// If the parameter is in the form 'serviceNameProvider', it's a provider
				if (strpos($parameter->name, "Provider") !== false) {
					$argument = $this->searchProvider(substr($parameter->name, 0, -8)); // Need to call serviceName without Provider
					if (is_null($argument)) {
						throw new ServiceProviderNotFoundException("Service provider '$parameter' has not been found!");
					}

				} else {
					$argument = $this->searchConstant($parameter->name); // Need to call serviceName without Provider
					if (is_null($argument)) {
						throw new ConstantNotFoundException("Constant '$parameter' has not been registered!");
					}
				}

				$arguments[] = $argument;
			}

			// All the dependencies has been resolved
			call_user_func_array($callback, $arguments);
		}

		// Execute the callback resolving the dependencies first
		private function executeCallback($callback) {
			// Resolve the dependencie of the callback
			$arguments = $this->resolve($callback);

			// All the dependencies has been resolved
			return call_user_func_array($callback, $arguments);
		}

		// Method that resolves the dependencies of the specified module (called recursively)
		private function resolveModuleDependencies($module) {
			// Check if the module exists
			if (!array_key_exists($module, $this->modules)) {
				throw new ModuleNotFoundException("Module '$module' has not been registered!");
			}
			// If is set 'resolving' or the module has been resolved return
			if (isset($this->modules[$module]['resolving']) or in_array($module, $this->modulesResolved)) {
				return;
			}

			// Resolving dependencies of this module
			$this->modules[$module]['resolving'] = true;
			// Module dependencies
			$dependencies = $this->modules[$module]['dependencies'];
			// Recursively call this method to resolve all the dependencies
			foreach ($dependencies as $dependency) {
				$this->resolveModuleDependencies($dependency);
			}

			// Module resolved
			unset($this->modules[$module]['resolving']);
			// Push the module into the order resolution variable
			$this->modulesResolved[] = $module;
		}

	}