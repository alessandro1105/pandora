<?php
/*
	RequestService file class

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

	namespace AzzurroFramework\Modules\AF\Request\File;

	
	class File {

		// Access files
		public function get(string $key, $default = null) {
			$_FILES;

			return isset($_FILES[$key]) ? $_FILES[$key] : $default;
		}

		// Check if a file exists
		public function has(string $key) {
			return isset($_FILES[$key]);
        }

        // Check if a file is valid
        public function isValid(string $key) {
			return isset($_FILES[$key]) ? $_FILES[$key]['error'] == UPLOAD_ERR_OK : false;
        }

	}