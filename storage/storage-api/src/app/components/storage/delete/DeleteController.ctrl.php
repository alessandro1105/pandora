<?php

    namespace App\Components\Storage\Delete;

    use \InvalidArgumentException;

    use \App\Components\Storage\Exceptions\NoSuchFileOrDirectoryException;
    use \App\Components\Storage\Exceptions\FileOrDirectoryAlreadyExistsException;
    use \App\Components\Storage\Exceptions\NotADirectoryException;
    use \App\Components\Storage\Exceptions\NotAFileException;
    use \App\Components\Storage\Exceptions\NoSuchFileVersionException;


    // Controller class
    class DeleteController {

        // Action of the controller
        public function action($router, $request, $http, $storageService, $API_PERSISTENT) {
            // Obtain data from the request
            $user = $router->getParam('uuid');
            $pathExploded = $router->getParam('path');

            // If there is no path
            if (count($pathExploded) == 0) {
                $this->error(400, [
                    'errors' => [
                        'pathMissing' => 'The path is mandatory to upload a file or create a new directory'
                    ]
                ]);
                return false;
            }

            // Obtain path and name
            $path = '/';
            $name = $pathExploded[count($pathExploded) -1]; // Last element of path

            $separator = '';
            for ($i = 0; $i < count($pathExploded) -1; $i++) {
                $path .= $separator . $pathExploded[$i];
                $separator = '/';
            }

            try {
                
                $uuids = $storageService->delete($user, $path, $name);

                foreach($uuids as $uuid) {
                    // Remove version from persistent service
                    $response = $http->request(
                        'DELETE',
                        $API_PERSISTENT . '/' . $uuid
                    );

                    // We assume that the request has been successfully
                }

                $this->success(200);

            } catch (InvalidArgumentException $e) {
                $this->error(400, [
                    'errors' => [
                        'badRequest' => 'The data in the request is wrong.'
                    ]
                ]);
                return false;

            } catch (NoSuchFileOrDirectoryException $e) {
                $this->success(204);
                return false;

            } catch (NotADirectoryException $e) {
                // Should be a different error
                $this->success(204);
                return false;

            }
        }


        /* =============== Private =============== */

        // Generate the response
        private function success($status) {
            // Setting status code
            http_response_code($status); // OK
        }

        // Generate the error response
        private function error($errorCode, $errors = array()) {
            // Setting status code
            http_response_code($errorCode);

            if ($errors != array()) {
                // Setting the content type of the request
                header('Content-Type: application/json');

                // echo the response
                echo json_encode($errors, JSON_PRETTY_PRINT);
            }
        }
    }
