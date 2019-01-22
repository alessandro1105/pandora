<?php

/*
user, path are mandatory parameters

if directory parameter of the query string is present:
- a new directory will be created with the name equals to the last part of the path

else
- a file version of the file indicated in path will be uploaded to persistentService and then inserted into the storage-db

*/
class UploadController
{

public function action()
{

    //PARAMETER RETRIEVAL-------------------------------------------------------

            //parametri
            //presi dall'url (delle risorse) hardcored
            //quelli da query string prendo da $_GET
            $user = '32f84ae0-2f55-4110-b3ec-ba8a1eb452f1';

            $path = $_GET['path']; //supposed to be in the query string just for testing...

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
                    throw new InvalidArgumentException();
                }
                //--------------------------------------------------DO NOT TRASH



                //version uuid (the name of the file physically stored in persistent storage)
                $version_uuid = StorageServiceUtil::uuidV4();



                set_time_limit(0);
                $length = (int) $_SERVER['CONTENT_LENGTH'];
                $GLOBALS['input'] = fopen('php://input','r');

                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, "http://localhost/persistentService/uploader.php?fileToUpload=".$version_uuid);
                //curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($c, CURLOPT_PUT, true);
                curl_setopt($c, CURLOPT_INFILESIZE, $length);
                curl_setopt($c, CURLOPT_READFUNCTION, function () {
                   //global $input;
                   return fread($GLOBALS['input'], 8192);
                });
                curl_exec($c);
                curl_close($c);
                fclose($GLOBALS['input']);


                $possible_uuid_toberemoved = $ss->addVersion($user, $path, $name, $version_uuid, $length);

                //is a legal uuid? If so, I shall remove it from persistent (there are now 11 versions and this one is the oldest)
                if(preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $possible_uuid_toberemoved))
                    file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$possible_uuid_toberemoved);

                $this->success(202);
                return;


            }

    }
    catch(InvalidArgumentException $e)
    {
        $this->error(400);
    }
    catch(DbException $d)
    {
        $this->error(500);
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
