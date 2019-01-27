<?php

    namespace App\Components\Storage\Upload;

    use \InvalidArgumentException;

    use \App\Components\Storage\Exceptions\DirectoryNotFoundException;
    use \App\Components\Storage\Exceptions\FileAlreadyExistsException;


    // Controller class
    class UploadController {

       // Action of the controller
        public function action($router, $request, $storageService) {

            // Obtain data from the request
            $uuid = $router->getParam('uuid');
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

                } catch (DirectoryNotFoundException $e) {
                    $this->error(400, [
                        'errors' => [
                            'directoryNotFound' => 'A directory in the path has not been found.'
                        ]
                    ]);

                } catch (FileAlreadyExistsException $e) {
                    $this->error(409, [
                        'errors' => [
                            'fileAlredyExists' => 'A File already exists in the parent directory with the same name.'
                        ]
                    ]);
                }

                $this->success(201);
                return false;

            // We are uploading a file
            } else {
                try {
                    
                } catch (Exception $e) {

                }

                $this->success(201);
                return false;
            }

            

            // try {

            //     $scissor = StorageServiceUtil::dividePathFromLast($path);
            //     $path = $scissor[0];
            //     $name = $scissor[1];



            //     if ($isDir) {

            //         $storageService->makeDir($user, $path, $name);

            //         $this->success(201);
            //         return;
            //     }
            //     else
            //     {

            //         //NOTE-DO NOT TRASH---------------------------------------------
            //         //this check is necessary, otherwise I'll discover too late that the filename is invalid and I've already put it in the persistent
            //         //this check will save space on the persistent
            //         if($storageService->getIfExistsByPath($user, $path.'/'.$name) ===true AND $storageService->getIfIsDirByPath($user, $path.'/'.$name) === true)
            //         {
            //             //there is already a directory with the same name as the file version I want to insert... ko!
            //             throw new ConflictException();
            //         }
            //         //--------------------------------------------------DO NOT TRASH



            //         //version uuid (the name of the file physically stored in persistent storage)
            //         $version_uuid = StorageServiceUtil::uuidV4();



            //         set_time_limit(0);
            //         $length = (int) $_SERVER['CONTENT_LENGTH'];
            //         $GLOBALS['input'] = fopen('php://input','r');

            //         $c = curl_init();
            //         curl_setopt($c, CURLOPT_URL, "http://localhost/persistentService/uploader.php?fileToUpload=".$version_uuid);
            //         //curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            //         curl_setopt($c, CURLOPT_PUT, true);
            //         curl_setopt($c, CURLOPT_INFILESIZE, $length);
            //         curl_setopt($c, CURLOPT_READFUNCTION, function () {
            //         //global $input;
            //         return fread($GLOBALS['input'], 8192);
            //         });
            //         curl_exec($c);
            //         curl_close($c);
            //         fclose($GLOBALS['input']);


            //         $possible_uuid_toberemoved = $storageService->addVersion($user, $path, $name, $version_uuid, $length);

            //         //is a legal uuid? If so, I shall remove it from persistent (there are now 11 versions and this one is the oldest)
            //         if(preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $possible_uuid_toberemoved))
            //             file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$possible_uuid_toberemoved);

            //         $this->success(202);
            //         return;
            //     }

            // }
            // catch(InvalidArgumentException $e)
            // {
            //     $this->error(400);
            // }
            // catch(DbException $d)
            // {
            //     $this->error(500);
            // }
            // catch(DataNotFoundException $f)
            // {
            //     $this->error(404);
            // }
            // catch(ConflictException $c)
            // {
            //     $this->error(409);
            // }
        }


        /* =============== Private =============== */

        // Generate the response
        private function success() {
            // Setting status code
            http_response_code(200); // OK
            // // Setting the content type of the request
            // header('Content-Type: application/json');

            // // Prepare the response
            // $response = [
            //     'uuid' => htmlspecialchars($user->uuid),
            //     'username' => htmlspecialchars($user->username),
            //     'email' => htmlspecialchars($user->email),
            //     'emailConfirmed' => $user->emailConfirmed,
            //     'registrationDate' => $user->registrationDate,
            //     'lastLoginDate' => $user->lastLoginDate
            // ];

            // // echo the response
            // echo json_encode($response, JSON_PRETTY_PRINT);
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
