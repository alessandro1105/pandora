<?php
/*
	EventService (event) service

	Service used to generate and handle events.


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

	namespace AzzurroFramework\Modules\Auto\Event;

	use \InvalidArgumentException;


	//--- EventService service ----
	final class EventService {

		// Injector service
		private $injector;
		//Callback
		private $callbacks;


		// Constructor
		public function __construct($injector) {
			$this->callbacks = array();

			// Save the injector service
			$this->injector = $injector;
		}

		// Register a callback to the event
		public function on(string $event, $callback) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_:.\x7f-\xff]*$/', $event)) {
				throw new InvalidArgumentException("\$event argument must be a valid event name!");
			}
			if (!is_callable($callback) and !(is_array($callback) and (is_object($callback[0]) or class_exists($allback[0])) and method_exists($callback[0], $callback[1]))) {
				throw new InvalidArgumentException("\$callback must be a valid callable!");
			}

			// Register the callback to the event
			if (isset($this->callbacks[$event])) {
				$this->callbacks[$event] = array();
			}
			$this->callbacks[$event][] = $callback;
		}

		// Emit an event
		public function emit(string $event) {
			// Checking arguments correctness
			if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_:.\x7f-\xff]*$/', $event)) {
				throw new InvalidArgumentException("\$event argument must be a valid event name!");
			}

			// Execute all the callbacks associated with this event
			if (isset($this->callbacks[$event])) {
				foreach ($this->callbacks[$event] as $callback) {
					$this->injector->call($callback);
				}
			}

		}
	}