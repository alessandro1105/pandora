<?php
/*
	RequestService (request) service provider

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

    use \AzzurroFramework\Modules\AF\Request\RequestService;
    
    use \AzzurroFramework\Modules\AF\Request\Session\SessionProvider;
    
    
    final class RequestServiceProvider implements ServiceProvider {

        // Variable that contains all the configuration for the service
        private $config;
        
        // Elements provider
        private $sessionProvider;

        // Service Provider contructor
        public function __construct() {
            $this->config['session'] = [];

            $this->sessionProvider = new SessionProvider($this->config['session']);
        }

        // Get service instance
        public function get() {
            return new RequestService($this->config);
        }

        public function __get($property) {
            switch (strtolower($property)) {
                case 'session':
                    return $this->sessionProvider;

                default:
                    return null;
            }
        }
    }
