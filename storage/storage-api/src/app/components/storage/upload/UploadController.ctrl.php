<?php

    namespace App\Components\Storage\Upload;

    use \InvalidArgumentException;

    use \App\Components\Storage\Exceptions\NoSuchFileOrDirectoryException;
    use \App\Components\Storage\Exceptions\FileOrDirectoryAlreadyExistsException;
    use \App\Components\Storage\Exceptions\NotADirectoryException;
    use \App\Components\Storage\Exceptions\NotAFileException;


    // Controller class
    class UploadController {

       // Action of the controller
        public function action($router, $request, $http, $storageService, $MAX_FILE_VERSIONS, $API_PERSISTENT) {

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

            // Check if we need to create a new directory
            if ($directory = $request->query->has('directory')) {
                try {
                    $storageService->makeDir($user, $path, $name);

                } catch (InvalidArgumentException $e) {
                    $this->error(400, [
                        'errors' => [
                            'badRequest' => 'The data in the request is wrong.'
                        ]
                    ]);
                    return false;

                } catch (NoSuchFileOrDirectoryException $e) {
                    $this->error(400, [
                        'errors' => [
                            'directoryNotFound' => 'No such file or directory'
                        ]
                    ]);
                    return false;

                } catch (FileOrDirectoryAlreadyExistsException $e) {
                    $this->error(409, [
                        'errors' => [
                            'fileOrDirectoryAlreadyExists' => 'A file or a directory already exists in the parent directory with the same name.'
                        ]
                    ]);
                    return false;

                } catch (NotADirectoryException $e) {
                    $this->error(400, [
                        'errors' => [
                            'notADirectory' => 'The path is not a directory'
                        ]
                    ]);
                    return false;
                }

                $this->success(201);
                return false;

            // We are uploading a file
            } else {

                try {
                    $versions = $storageService->getAllFileVersions($user, $path, $name);
                    
                    // If there are too many versions, remove the first one
                    if (count($versions) >= $MAX_FILE_VERSIONS) {
                        $versionToRemove = $versions[0]['uuid'];
                        $storageService->removeFileVersion($versionToRemove);

                        // Remove version from persistent service
                        $response = $http->request(
                            'DELETE',
                            $API_PERSISTENT . '/' . $versionToRemove
                        );

                        // We assume that the request has been successfully
                    }

                    // Get the content length
                    $filesize = (int) $_SERVER['CONTENT_LENGTH'];

                    // Let's create the file version entry on the database
                    $version = $storageService->addFileVersion($user, $path, $name, $filesize);

                    // Upload the file on the persistent service
                    // Open input file
                    $input = fopen('php://input','r');

                    $request = $http->upload(
                        $API_PERSISTENT . '/' . $version,
                        $input,
                        $filesize
                    );
                    // Close file handler
                    fclose($input);

                    // We assume that the request has been successfully

                    // Set that the file has been correctly updated
                    $storageService->setFileVersionUploaded($version);

                } catch (InvalidArgumentException $e) {
                    $this->error(400, [
                        'errors' => [
                            'badRequest' => 'The data in the request is wrong.'
                        ]
                    ]);
                    return false;
    
                } catch (NoSuchFileOrDirectoryException $e) {
                    $this->error(400, [
                        'errors' => [
                            'directoryNotFound' => 'No such file or directory'
                        ]
                    ]);
                    return false;

                } catch (NotADirectoryException $e) {
                    $this->error(400, [
                        'errors' => [
                            'notADirectory' => 'The path is not a directory'
                        ]
                    ]);
                    return false;

                } catch (NotAFileException $e) {
                    $this->error(400, [
                        'errors' => [
                            'notAFile' => 'It\'s not a file'
                        ]
                    ]);
                    return false;
                }
                

                $this->success(201);
                return false;
            }
        }


        /* =============== Private =============== */

        // Generate the response
        private function success() {
            // Setting status code
            http_response_code(200); // OK
            
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
