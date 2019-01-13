<?php
/*
	LogServiceProvider (logProvider) service

	Service provider to configure $log service.


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

	namespace AzzurroFramework\Modules\AF\Logger;

	use \InvalidArgumentException;


	//--- LogServiceProvider provider ----
	final class LoggerServiceProvider implements ServiceProvider {

		// Variable that contains all the configuration for the service
		private $config;

		// Constructor
		public function __construct() {
			// Default settings
			$this->config = [
				"level" => "debug"
			];
		}

		// Set the minimun log level (valid: debug, info, warn, error)
		public function setLogLevel(string $level) {
			// Check the correctness of the data and save the level if valid
			switch ($level) {
				case "debug":
				case "info":
				case "warn":
				case "error":
					$this->config['level'] = $level;
					break;

				default:
					throw new InvalidArgumentException("\$level must be a valid log level ('debug', 'info', 'warn', 'error')!");
			}
		}

		// Get the service
		public function get() {
			return new LoggerService($this->config);
		}

	}