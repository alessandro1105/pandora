<?php
/*
	TemplateService (template) service

	- service that permits to handle the template of a controller


	---- Changelog ---
	Rev 1.0 - November 20th, 2017
			- Basic functionality


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

	namespace AzzurroFramework\Modules\AF\Template;

	use \InvalidArgumentException;

	use AzzurroFramework\Modules\AF\Template\Exceptions\TemplateNotSetException;


	//--- TemplateService service ----
	final class TemplateService {

		// Template
		private $template;

		// Contructor
		public function __construct() {
			$this->reset();
		}

		// Reset the template and parameters
		public function reset() {
			// Prepare the variables
			$this->template = null;
			$this->parameters = array();
		}

		// Set the template
		public function setTemplate(string $tpl, bool $url) {
			// Check the arguments correctness
			if ($url and !file_exists($tpl)) {
				throw new InvalidArgumentException("\$tpl must be a valid template file!");
			}

			// Loading the template
			if ($url) {
				$this->template = file_get_contents($tpl);
			
			} else {
				$this->template = $tpl;
			}
		}

		// Render the template
		public function render(bool $process) {
			// If the template has not been 
			if (is_null($this->template)) {
				throw new TemplateNotSetException("The template has not been set!");
			}

			// If the template must be processed
			if ($process) {
				// Replace the parameters inside the template
				foreach ($this->parameters as $key => $value) {
					$this->template = preg_replace('/{{' . $key . '}}/', "" . $value, $this->template);
				}
			}
			
			// Print the template
			echo $this->template;

			// Reset the service
			$this->reset();
		}

		// Assign a parameter
		public function assign(string $key, $value) {
			$this->parameters[$key] = $value;
		}

	}