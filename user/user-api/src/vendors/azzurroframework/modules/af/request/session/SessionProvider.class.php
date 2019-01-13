<?php
/*
	RequestService session provider class

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

	namespace AzzurroFramework\Modules\AF\Request\Session;

	
	class SessionProvider {

        // Variable that contains all the configuration for the service
        private $config;

        public function __construct(&$config) {
            // Save config
            $this->config = &$config;

            // Default settings
            $this->config = [
                'expiration' => 3600, // 60 minutes
                'lifespan' => 2400, // 40 minutes
                'timeout' => 300, // 5 minutes
                'prefix' => '', // Prefix for generating a session id
                'save_path' => ini_get('session.save_path') // Session save path
            ];
        }

        // Set session expiration timeout
        public function setExpiration($expiration) {
            $this->config['expiration'] = $expiration;

            return $this;
        }

        // Set session lifespan
        public function setLifespan($lifespan) {
            $this->config['lifespan'] = $lifespan;

            return $this;
        }

        // Set session timeout for session id replacement
        public function setTimeout($timeout) {
            $this->config['timeout'] = $timeout;

            return $this;
        }

        // Set session timeout for session id replacement
        public function setPrefix($prefix) {
            $this->config['prefix'] = $prefix;

            return $this;
        }

        // Set session save path if different from the ini setting
        public function setSavePath($path) {
            $this->config['save_path'] = $path;

            return $this;
        }

	}