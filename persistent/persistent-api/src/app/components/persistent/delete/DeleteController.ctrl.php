<?php

/*
    DeleteController
*/

    namespace App\Components\Persistent\Delete;

    use \InvalidArgumentException;
    

    class DeleteController {

        public function action($router, $persistentService) {

            // Get uuid
            $uuid = $router->getParam('uuid');

            try {
                // Check if file exists
                if (!$persistentService->exists($uuid)) {
                    // File not exists
                    $this->success(204);
                    return;
                }

                // Delete the file
                $persistentService->delete($uuid);

                // Success response
                $this->success(204);

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