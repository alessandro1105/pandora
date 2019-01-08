<?php

/*
    UploadController
*/

    namespace App\Components\Persistent\Upload;

    use \InvalidArgumentException;


    class UploadController {

        public function action($router, $persistentService) {

            // Get uuid
            $uuid = $router->getParam('uuid');

            try {
                // Check if file already exists
                if ($persistentService->exists($uuid)) {
                    // Error file already exists
                    $this->error(409);
                    return;
                }

                // Create file handler
                $file = $persistentService->create($uuid);

                // Open php input
                $input = fopen('php://input','r');

                while(!feof($input)) {
                    fwrite($file, fread($input, 8192), 8192);
                }

                // Close file handler
                fclose($input);
                fclose($file);

                // Success response
                $this->success(201);

            } catch (InvalidArgumentException $e) {
                // Success response
                $this->error(400);
                return;
            }
            
        }

        // Success response
        private function success($statusCode) {
            http_response_code($statusCode);
        }

        private function error($statusCode) {
            http_response_code($statusCode);
        }

    }