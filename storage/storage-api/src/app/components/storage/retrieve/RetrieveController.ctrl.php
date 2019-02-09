<?php

    namespace App\Components\Storage\Retrieve;

    use \InvalidArgumentException;

    use \App\Components\Storage\Exceptions\NoSuchFileOrDirectoryException;
    use \App\Components\Storage\Exceptions\FileOrDirectoryAlreadyExistsException;
    use \App\Components\Storage\Exceptions\NotADirectoryException;
    use \App\Components\Storage\Exceptions\NotAFileException;
    use \App\Components\Storage\Exceptions\NoSuchFileVersionException;


    // Controller class
    class RetrieveController {

        // Action of the controller
        public function action($router, $request, $http, $storageService, $API_PERSISTENT) {
            // Obtain data from the request
            $user = $router->getParam('uuid');
            $pathExploded = $router->getParam('path');

            // If there is no path
            if (count($pathExploded) == 0) {
                $path = '/';
                $name = '/';

            } else {
                // Obtain path and name
                $path = '/';
                $name = $pathExploded[count($pathExploded) -1]; // Last element of path

                $separator = '';
                for ($i = 0; $i < count($pathExploded) -1; $i++) {
                    $path .= $separator . $pathExploded[$i];
                    $separator = '/';
                }
            }

            try {
                // Check if the request is for a file or a directory
                if ($storageService->isADirectory($user, $path, $name)) {

                    // Get the listing
                    $listing = $storageService->listDirectory($user, $path, $name);

                    // Prepare the data
                    $directory = [
                        'type' => 'directory',
                        'name' => $name,
                        'path' => $name == '/' ? '/' : $path . '/' . $name,
                        'listing' => []
                    ];

                    foreach ($listing as $item) {
                        $directory['listing'][] = [
                            'type' => $item['directory'] ? 'directory' : 'file',
                            'name' => $item['name'],
                            'path' => $item['path'],
                            'creationTime' => $item['creation_time']
                        ];
                    }

                    $this->success($directory);

                    return false;

                // It's a file
                } else {

                    // Only one of these
                    if ($request->query->has('info') && $request->query->has('version')) {
                        $this->error(400, [
                            'errors' => [
                                'badRequest' => 'The data in the request is wrong.'
                            ]
                        ]);
                        return false;
                    }

                    // Listing file version
                    if ($request->query->has('info')) {
                        // Get the listing
                        $versions = $storageService->getAllFileVersions($user, $path, $name);

                        // Prepare the data
                        $file = [
                            'type' => 'versions',
                            'name' => $name,
                            'path' => ($path == '/' ? '/' : $path . '/') . $name,
                            'versions' => []
                        ];

                        foreach ($versions as $version) {
                            $file['versions'][] = [
                                'versionNumber' => $version['version_number'],
                                'creationTime' => $version['creation_time'],
                                'file_size' => $version['file_size']
                            ];
                        }

                        $this->success($file);
                        
                        return false;

                    // Get the file
                    } else {
                        $version_number = $request->query->get('version', null);

                        if (is_numeric($version_number)) {
                            $version_number = intval($version_number);
                        }

                        $version = $storageService->getFileVersion($user, $path, $name, $version_number);

                        $file = $http->download($API_PERSISTENT . '/' . $version['uuid']);                        

                        $this->successFile($name, $file, $version);

                        fclose($file);

                        return false;
                    }
                }

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

            } catch (NoSuchFileVersionException $e) {
                $this->error(404, [
                    'errors' => [
                        'fileVersionNotFound' => 'The version for this file cannot be found'
                    ]
                ]);
                return false;
            }
            
        }


        /* =============== Private =============== */

        // Generate the response
        private function success($listing) {
            // Setting status code
            http_response_code(200); // OK
            // Setting the content type of the request
            header('Content-Type: application/json');

            // echo the response
            echo json_encode($listing, JSON_PRETTY_PRINT);
        }

        // Generate the response
        private function successFile($name, $file, $version) {
            // Setting status code
            http_response_code(200); // OK

            // Download the file and send to the user
            header('Content-Disposition: attachment; filename="' . $name . '"');
            header('Content-type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length: " . $version['file_size']);

            fpassthru($file);
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
