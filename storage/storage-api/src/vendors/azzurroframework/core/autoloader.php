<?php
/*
	Azzurro Framework core loader

	Load the core files


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

	//--- CORE CLASS AUTOLOADER FUNCTION ---
	spl_autoload_register(function ($class) {

		// Check if it's competence of the core
		if (strpos($class, 'AzzurroFramework\\Core\\') == 0) {

			$classExploded = explode('\\', $class);
			$className = $classExploded[count($classExploded) -1];
			$file = __AF_CORE_DIR__ . '/';
			
			// Create path for the file
			for ($i = 2; $i < count($classExploded) - 1; $i++) {
				$file .= strtolower($classExploded[$i]) . '/';
			}

			//If it's an interface
			if (strpos($className, 'Interface') !== false) {
				$file .= $className . '.interface.php';

			// If it's an exception
			} else if (strpos($className, 'Exception') !== false) {
				$file .= $className . '.exception.php';

			// If it's a class
			} else {
				$file .= $className . '.class.php';
			}

			// If the file exists
			if (file_exists($file) and is_file($file)) {
				require_once($file);
			}

		}

	});

	// --- EXPOSE CORE INTERFACES/CLASSES GLOBALLY
	spl_autoload_register(function ($class) {
		$classExploded = explode('\\', $class);
		$className = $classExploded[count($classExploded) -1];

		switch ($className) {
			case 'ServiceProvider': // Expose ServiceProviderInterface as ServiceProvider
				class_alias('\AzzurroFramework\Core\Service\Interfaces\ServiceProviderInterface', $class);
		}
	});