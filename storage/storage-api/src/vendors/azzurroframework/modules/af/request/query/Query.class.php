<?php
/*
	RequestService query class

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

	namespace AzzurroFramework\Modules\AF\Request\Query;

	
	class Query {

		// Access query string
		public function get(string $key, string $default = null) {
			global $_GET;
			
			return isset($_GET[$key]) ? $_GET[$key] : $default;
		}

		// Check if a query string parameter exists
		public function has(string $key) {
			global $_GET;

			return isset($_GET[$key]);
		}
	}