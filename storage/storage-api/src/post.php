<?php

namespace App\Components\Storage\Post;

use \InvalidArgumentException;

use App\Components\Storage\Util\StorageServiceUtil as util;
use App\Components\Storage\Model\StorageServiceModel as m;


//possible parameter in $_GET: user, path, isDir
//if isDir is not set or false then there must be a parameter in $_FILE: fileToUpload

class PostController
{

public function action($router, $storageService)
{
    try
    {
        if( (isset($_GET['user'])) AND (isset($_GET['path'])) )
        {


            $path = util::pathify($_GET['path']); // check ( not empty & not containing // ) removing the possible initial / (that surely is present) and final /

            //check on useruuid and on path
            if( (!util::isUuid($_GET['user'])) )
                throw new InvalidArgumentException();

            $scissor = util::dividePathFromLast($path);
            $path = $scissor[0];
            $name = $scissor[1];


            //create a connection with the database using the proper function defined in StorageServiceUtil.php



            if( (isset($_GET['isDir'])) AND ($_GET['isDir'] == true) )
            {

                m::makeDir($_GET['user'], $path, $name);

                $this->success(201);
            }
            else
            {

            /*
                if( !(isset($_FILES["fileToUpload"]))
                    OR (trim($_FILES["fileToUpload"]["name"]) == '')
                    OR (!is_uploaded_file($_FILES["fileToUpload"]["tmp_name"]))
                    OR ($_FILES["fileToUpload"]["error"]>0)
                  )
                    throw new InvalidArgumentException();

                else
                {
            */
                    //version uuid (the name of the file physically stored in persistent storage)
                    $version_uuid = util::uuidV4();

                    // Open php input (the file I want to upload)
                    $input = file_get_contents('php://input');


                    $url = 'http://persistent-api/uploader.php?fileToUpload='.$version_uuid;

                    while(!feof($input)) {
                          echo fread($input, 8192);
                      }

                     // Close php input
                    fclose($input);

                    $possible_uuid_toberemoved = m::addVersion($_GET['user'], $path, $name, $version_uuid, $_GET['size']); //WARNING: is 'size' given?

                    if(u::isUuid($possible_uuid_toberemoved))
                        file_get_contents('http://persistent-api/deleter.php?fileToDelete='.$possible_uuid_toberemoved);

                    $this->success(202);

                //}

            }
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
