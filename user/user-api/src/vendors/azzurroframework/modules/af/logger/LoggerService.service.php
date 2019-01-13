<?php
/*
	LogService (log) service

	Service that permits to log information into a file.


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
	final class LoggerService {

		// File into wich the log messages will be written
		const LOG_FILE = __AF_LOGS_DIR__ . "/log.txt";

		// Variable that contains all the configuration for the service
		private $config;

		// Constructor
		public function __construct(&$config) {
			$this->config = &$config;
		}


		// Log message
		public function log(string $message) {
			error_log($message . "\n", 3, self::LOG_FILE);
		}

		// Log info message
		public function info(string $message) {
			// If the log message can be logged
			if ($this->logLevel($this->config['level']) <= $this->logLevel('info')) {
				error_log($message . "\n", 3, self::LOG_FILE);
			}
		}

		// Log warn message
		public function warn(string $message) {
			// If the log message can be logged
			if ($this->logLevel($this->config['level']) <= $this->logLevel('warn')) {
				error_log($message . "\n", 3, self::LOG_FILE);
			}
		}

		// Log error message
		public function error(string $message) {
			// If the log message can be logged
			if ($this->logLevel($this->config['level']) <= $this->logLevel('error')) {
				error_log($message . "\n", 3, self::LOG_FILE);
			}
		}

		// Log debug message
		public function debug(string $message) {
			// If the log message can be logged
			if ($this->logLevel($this->config['level']) <= $this->logLevel('debug')) {
				error_log($message . "\n", 3, self::LOG_FILE);
			}
		}


		// Return the correspondig level into numerical form
		private function logLevel(string $level) {
			// Return the numerical form of the level
			switch ($level) {
				case "debug":
					return 0;

				case "info":
					return 1;

				case "warn":
					return 2;

				case "error":
					return 3;
			}
		}

	}