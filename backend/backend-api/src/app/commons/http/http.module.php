<?php

    // HTTP module
    $azzurro
        ->module('http', [
        
        ])

        ->service('http', '\App\Commons\Http\HTTPService');