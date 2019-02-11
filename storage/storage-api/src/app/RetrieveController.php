<?php


use ConnectionToPersistentException\PersistentConnTimeout;
use ConnectionToPersistentException\PersistentException;

/*
usr and path are always required
Functionalities:
- list the content of a certain directory -> no additional parameter in query string

info parameter (set to true) is mandatory in this case:
- list all the versions data of a certain file (version_number, creation_time, file_size)

version parameter is optional in these cases:
- download a file version -> no additional parameter required, but last element of path needs to be an existent non-directory element

version and info parameters both set is an InvalidArgument condition
*/

class RetrieveController
{
    public function action($router, $request, $API_PERSISTENT)
    {

//PARAMETER RETRIEVAL-----------------------------------------------------------

        //parametri
        //presi dall'url (delle risorse) hardcored
        //quelli da query string prendo da $_GET
        $user = $router->getParam('uuid');
        $pathExploded = $router->getParam('path');
        $path = '/' . implode('/', $pathExploded);

        $version = ( (isset($_GET['version'])) ? $_GET['version'] : NULL);
        $info = ( (isset($_GET['info'])) ? TRUE : NULL);

//------------------------------------------------------END PARAMETERS RETRIEVAL


        //the object on which the methods will be called
        $ss = new StorageService();


        try
        {

            $scissor = StorageServiceUtil::dividePathFromLast($path);
            $startingPath = $scissor[0];
            $element = $scissor[1];


            if($ss->getIfIsDirByPath($user, $path)) //also $path='' will do (considered as the root directory)
            {
                if($version!=NULL OR $info!=NULL) //cannot do any of them if I've a directory
                    throw new DataNotFoundException();

                //if it is a directory, then the only op allowed here is listing the content
                
                //echo json_encode($ss->list($user, $path));

                $raw = $ss->list($user,$path));
                
                $refined = [
                    'type' => 'directory',
                    'name' => $element,
                    'path' => '/'.$startingPath.'/'.$element,
                    'listing' => []
                ];
                
                foreach($raw as $e)
                {
                    $elem = [
                      'type' => (($e['is_dir'] === true) ? 'directory' : 'file') ,
                      'name' => $e['file_name'],
                      'path' => '/'.$startingPath.'/'.$element.'/'.$e['file_name'],
                      'creationTime' => strtotime($e['creation_time'])
                    ];    
                    
                    array_push($refined['listing'], $elem);
                }    
                
                echo json_encode($refined);
                
                $this->success(200);
                return;
            }

            else
            {

                if($info AND $version) //cannot do both
                    throw new InvalidArgumentException();

                if($info)
                {

                    //if info is set, then I am asked for the listing of the versions of a certain file

                    //echo json_encode($ss->getAllVersionsData($user, $startingPath, $element));

                    $raw = getAllVersionsData($user, $startingPath, $element);
                    
                     $refined = [
                    'type' => 'versions',
                    'name' => $element,
                    'path' => '/'.$startingPath.'/'.$element,
                    'versions' => []
                    ];

                    foreach($raw as $e)
                    {
                        $elem = [
                          'type' => 'version' ,
                          'versionNumber' => $e['version_number'],
                          'creationTime' => strtotime($e['creation_time']),
                          'fileSize' => $e['file_size']
                        ];    

                        array_push($refined['versions'], $elem);
                    }    

                    echo json_encode($refined);
                    
                    
                    $this->success(200);
                }
                else
                {
                    //last element is not a directory and info not set... this means the actual version is needed
                    if($version == NULL)
                    {
                        $version = 0;
                    }

                    $persistentFilename = $ss->getVersionUuid($user, $startingPath, $element, $version);


                    $url = $API_PERSISTENT.'/'.$persistentFilename;


                    $remote = fopen($url, 'rb');

                    header('Content-Disposition: attachment; filename="'.$persistentFilename.'"');
                    header('Content-type: application/octet-stream');
                    header("Content-Transfer-Encoding: Binary");
                    //header ("Content-Length: " . filesize('mactex-20180417.pkg')); // NOT WORKING
                    while(!feof($remote)) {
                        echo(fread($remote, 4096));
                    }


                    $this->success(200);

                }

            }



        }
        catch(InvalidArgumentException $e)
        {
            $this->error(400, [
                                    'errors' => [
                                        'badRequest' => 'The data in the request is wrong.'
                                    ]
                                ]);

            return false;
        }
        catch(DbException $d)
        {
            $this->error(500, [
                                    'errors' => [
                                        'internalError' => 'Something went wrong inside the database server.'
                                    ]
                                ]);

            return false;
        }
        catch(DataNotFoundException $f)
        {
            $this->error(404, [
                                    'errors' => [
                                        'notFound' => 'The data in the request were not found.'
                                    ]
                                ]);

            return false;
        }
        catch(ConflictException $c)
        {
            $this->error(409, [
                                    'errors' => [
                                        'conflict' => 'The data in the request generated a conflict.'
                                    ]
                                ]);

            return false;
        }

    }


    private function success($statusCode)
    {
        http_response_code($statusCode);
    }

    private function error($errorCode, $errors = array())
    {
        // Setting status code
        http_response_code($errorCode);
        if ($errors != array())
        {
            // Setting the content type of the request
            header('Content-Type: application/json');
            // echo the response
            echo json_encode($errors, JSON_PRETTY_PRINT);
        }
    }

}
