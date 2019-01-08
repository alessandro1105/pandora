<?php
/*
	auto module declaration

	This script will register the components of the af module into the framework.


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

	use \AzzurroFramework\Modules\Auto\Injector\InjectorService;
	use \AzzurroFramework\Modules\Auto\Controller\ControllerService;
	use \AzzurroFramework\Modules\Auto\Filter\FilterService;

    
    // --- DECLARATION ---
	$azzurro
        ->module('auto', [

        ])

        // --- SERVICES ----
        
        // $azzurro service
        ->provider('azzurro', '\AzzurroFramework\Modules\Auto\Azzurro\AzzurroServiceProvider')
		// $callback service
        ->service('callback', '\AzzurroFramework\Modules\Auto\Callback\CallbackService')
        // $controller service
		->service('event', '\AzzurroFramework\Modules\Auto\Event\EventService')

		// $injector service
		->factory('injector', function () use ($azzurro) {
			return new InjectorService($azzurro->injector);
		})
		// $controller service
        ->factory('controller', function () use ($azzurro) {
			return new ControllerService($azzurro->injector);
		})
		// $filter service
        ->factory('filter', function () use ($azzurro) {
			return new FilterService($azzurro->injector);
		});