<?php

/*
include_once 'StorageService.php';
include_once 'StorageServiceUtil.php';
*/

//possible parameter in $_GET: user, path, isDir
//if isDir is not set or false then there is a file (version) to upload to persistentService and to insert into the storage-db

class UploadController
{

public function action()
{

    //PARAMETER RETRIEVAL-----------------------------------------------------------

            //parametri
            //presi dall'url (delle risorse) hardcored
            //quelli da query string prendo da $_GET
            $user = '32f84ae0-2f55-4110-b3ec-ba8a1eb452f1';

            $path = $_GET['path']; //supposed to be in the query string just for testing...

            $isDir = ( (isset($_GET['directory'])) ? TRUE : NULL);

    //------------------------------------------------------END PARAMETERS RETRIEVAL


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
            }
            else
            {

                //version uuid (the name of the file physically stored in persistent storage)
                $version_uuid = StorageServiceUtil::uuidV4();



                set_time_limit(0);
                $length = (int) $_SERVER['CONTENT_LENGTH'];
                $input = fopen('php://input','r');
    
                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, "http://localhost/persistentService/uploader.php?fileToUpload=".$version_uuid);
                //curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($c, CURLOPT_PUT, true);
                curl_setopt($c, CURLOPT_INFILESIZE, $length);
                curl_setopt($c, CURLOPT_READFUNCTION, function () {
                   global $input;
                   return fread($input, 8192);
                });
                curl_exec($c);
                curl_close($c);
                fclose($input);



                $possible_uuid_toberemoved = $ss->addVersion($user, $path, $name, $version_uuid, $length);

                //is a legal uuid? If so, I shall remove it from persistent (there are now 11 versions and this one is the oldest)
                if(preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $possible_uuid_toberemoved))
                    file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$possible_uuid_toberemoved);

                $this->success(202);



            }

    }
    catch(InvalidArgumentException $e)
    {
        $this->error(400);
    }
}

private function success($statusCode)
{
    http_response_code($statusCode);
}

private function error($statusCode)
{
    http_response_code($statusCode);
}


}
