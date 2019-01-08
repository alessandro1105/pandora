<?php
/*
	User application autolaoder file

	This file is the file which will load your application.

    DO NOT DELETE THIS FILE otherwise your application will not be loaded.
*/

    /* =============== LOAD MODULES DECLARATIONS =============== */

    // Glob function to search recursively
    function glob_recursive($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }

    // Require all modules
    foreach (glob_recursive(__AF_APP_DIR__ . '/*/*.module.php') as $module) {
        require_once($module);
    }

    // Require app module
    require_once(__AF_APP_DIR__ . '/app.module.php');



    /* =============== AUTOLOADER COMPONENTS =============== */

    function start_with($string, $query) {
        return substr($string, 0, strlen($query)) === $query;
    }

    // Autoloader to automatically load all components classes
    spl_autoload_register(function ($class) {

        // Remove starting \ from the start of the class
        $classname = ltrim($class, '\\');
        $filename  = __AF_APP_DIR__ . DIRECTORY_SEPARATOR;
        $namespace = "";

        // echo $class . '<br>';

        // If there is a namespace
        if ($last = strrpos($class, '\\')) {
            $namespace = substr($classname, 0, $last);
            $classname = substr($classname, $last + 1);

            // If the namespace starts with App\
            if (start_with($namespace, 'App\\')) {
                // Remove it
                $namespace = substr($namespace, 4, strlen($namespace));
            }
        }

        // convert the namespace from camel case to dash case (it is the path from app)
        $path = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $namespace));

        // Replace \ with directory separator
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

        // Update filename
        $filename .= $path . DIRECTORY_SEPARATOR;

        // Compose the probable filename of the file
        // If it's a provider
        if (strpos($classname, "Provider") !== false) {
            $filename .= $classname . ".provider.php";

        // If it's a service
        } else if (strpos($classname, "Service") !== false) {
            $filename .= $classname . ".service.php";

        // If it's a controller
        } else if (strpos($classname, "Controller") !== false) {
            $filename .= $classname . ".ctrl.php";

        // If it's an exception
        } else if (strpos($classname, "Exception") !== false) {
            $filename .= $classname . ".exception.php";

        // If it's a class
        } else {
            $filename .= $classname . ".class.php";
        }

        // If the file exists
        if (file_exists($filename) and is_file($filename)) {
            require_once($filename);
        }
    });