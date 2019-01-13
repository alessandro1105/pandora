<?php
/*
	RequestService (request) service

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

	namespace AzzurroFramework\Modules\AF\Request;

	use \AzzurroFramework\Modules\AF\Request\Cookie\Cookie;
	use \AzzurroFramework\Modules\AF\Request\File\File;
	use \AzzurroFramework\Modules\AF\Request\Input\Input;
	use \AzzurroFramework\Modules\AF\Request\Query\Query;
	use \AzzurroFramework\Modules\AF\Request\Session\Session;


	//--- Request service ----
	final class RequestService {

		// Class to handle particular part of the request
		private $cookie;
		private $file;
		private $input;
		private $query;
		private $session;

		// Service contructor
		public function __construct($config) {
			$this->cookie = new Cookie();
			$this->file = new File();
			$this->input = new Input();
			$this->query = new Query();
			$this->session = new Session($config['session']);
		}


		// Check if the current method is the one specified
		public function isMethod(string $method) {
			return strtoupper($method) == $method;
		}


		// Return object
		public function __get($property) {
			switch (strtolower($property)) {
				case 'cookie':
					return $this->cookie;
				
				case 'file':
					return $this->file;

				case 'input':
					return $this->input;

				case 'query':
					return $this->query;

				case 'session':
					return $this->session;
				
				case 'url':
					return $this->getUrl();
				
				case 'method':
					return $this->getMethod();

				default:
					return null;
			}
		}


		// Return full url of the request
		private function getUrl() {
			global $_SERVER;
			
			return $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";;
		}

		// Return HTTP method
		private function getMethod() {
			global $_SERVER;

			return strtoupper($_SERVER['REQUEST_METHOD']);
		}

	}