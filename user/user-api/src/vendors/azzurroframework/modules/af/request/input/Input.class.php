<?php
/*
	RequestService input class

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

	namespace AzzurroFramework\Modules\AF\Request\Input;

	use \Adbar\Dot; // Dot notation from composer package


	class Input {

		private $input = null;

		// Costruttore
		public function __construct() {
			global $_SERVER;
			global $_POST;

			$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

			// Check content-type of the request
			if (strpos($contentType, 'application/json') !== false) {
				// Get JSON body
				$body = file_get_contents('php://input');
            	//convert JSON into array
				$this->input = new Dot(json_decode($body, TRUE));
			
			} else {
				$this->input = new Dot($_POST);
			}
		}

		// Access inputs
		public function get(string $key, string $default = null) {
			return $this->input->has($key) ? $this->input->get($key) : $default;
		}

		// Check if an input exists
		public function has(string $key) {
			return $this->input->has($key);
		}
	}