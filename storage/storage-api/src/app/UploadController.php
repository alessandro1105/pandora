<?php

include 'ConnectionToPersistentException.php';
/*
user, path are mandatory parameters

if directory parameter of the query string is present:
- a new directory will be created with the name equals to the last part of the path

else
- a file version of the file indicated in path will be uploaded to persistentService and then inserted into the storage-db

*/
class UploadController
{

public function action($router, $request, $API_PERSISTENT)
{

    //PARAMETER RETRIEVAL-------------------------------------------------------

            //parametri
            //presi dall'url (delle risorse) hardcored
            //quelli da query string prendo da $_GET
            $user = $router->getParam('uuid');
        $pathExploded = $router->getParam('path');
        $path = '/' . implode('/', $pathExploded);

            $isDir = ( (isset($_GET['directory'])) ? TRUE : NULL);

    //--------------------------------------------------END PARAMETERS RETRIEVAL


            //the object on which the methods will be called
            $ss = new StorageService();




    try
    {

            $scissor = StorageServiceUtil::dividePathFromLast($path);
            $path = $scissor[0];
            $name = $scissor[1];



            if($isDir)
            {

                $ss->makeDir($user, $path, $name);

                $this->success(201);
                return;
            }
            else
            {

                //NOTE-DO NOT TRASH---------------------------------------------
                //this check is necessary, otherwise I'll discover too late that the filename is invalid and I've already put it in the persistent
                //this check will save space on the persistent
                if($ss->getIfExistsByPath($user, $path.'/'.$name) ===true AND $ss->getIfIsDirByPath($user, $path.'/'.$name) === true)
                {
                    //there is already a directory with the same name as the file version I want to insert... ko!
                    throw new ConflictException();
                }
                //--------------------------------------------------DO NOT TRASH



                //version uuid (the name of the file physically stored in persistent storage)
                $version_uuid = StorageServiceUtil::uuidV4();



                $connection_timeout_sec = 20; //in seconds

                set_time_limit(0);
                $length = (int) $_SERVER['CONTENT_LENGTH'];
                $GLOBALS['input'] = fopen('php://input','r');

                $c = curl_init();
                curl_setopt($c, CURLOPT_URL,  $API_PERSISTENT . '/'. $version_uuid);
                curl_setopt($c, CURLOPT_PUT, true);

                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $connection_timeout_sec); //NEW!! timeout in seconds to await for a connection

                curl_setopt($c, CURLOPT_INFILESIZE, $length);
                curl_setopt($c, CURLOPT_READFUNCTION, function () {
                   //global $input;
                   return fread($GLOBALS['input'], 8192);
                });
                curl_exec($c);


                //new part... managing timeout:
                if(curl_errno($c)) //for example, now if I have errno 28 it's a timed out, so it behaves consistently with our failure model
                {
                    throw new PersistentConnException();
                }

                if(curl_getinfo($c, CURLINFO_HTTP_CODE) == 409) //generated an uuid that already exists as a name of a physical file in the persistent
                {
                    throw new PersistentException();

                }





                curl_close($c);
                fclose($GLOBALS['input']);


                $possible_uuid_toberemoved = $ss->addVersion($user, $path, $name, $version_uuid, $length);

                //is a legal uuid? If so, I shall remove it from persistent (there are now 11 versions and this one is the oldest)
                if(preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $possible_uuid_toberemoved))
                {
                    //file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$possible_uuid_toberemoved);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $API_PERSISTENT.'/'.$possible_uuid_toberemoved);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $connection_timeout_sec); //NEW!! timeout in seconds to await for a connection
                    //curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    //$result = json_decode($result);

                    //new part... managing timeout:
                    if(curl_errno($c)) //for example, now if I have errno 28 it's a timed out, so it behaves consistently with our failure model
                    {
                        throw new PersistentConnException();
                    }

                    //the non-existence (http response 204 from Persistent) is considered ok, so it's not handled in a particular way

                    curl_close($ch);




                }
                $this->success(202);
                return;


            }

    }

    catch(PersistentConnException $e)
    {

        $this->error(500, [
                                'errors' => [
                                    'Connection' => '[debug explanation] curl failed to connect with Persistent Service. Probably a timeout occured.'
                                ]
                            ]);
        return false;
    }

    catch(PersistentException $e)
    {
        $this->error(500, [
                                'errors' => [
                                    'badUuidGeneration' => 'Uuid was already an existent file in persistent. Suggested operation: retry again.'
                                ]
                            ]);

        return false;
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


/*
private function error($statusCode)
{
    http_response_code($statusCode);
}
*/

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
