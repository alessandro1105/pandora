<?php

namespace App\Components\Storage\Post;

use \InvalidArgumentException;

use App\Components\Storage\Util\storage_service_util as util;
use App\Components\Storage\Model\storage_service_model as m;


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
            if( (!util::is_uuid($_GET['user'])) )
                throw new InvalidArgumentException();

            $scissor = util::divide_path_from_last($path);
            $path = $scissor[0];
            $name = $scissor[1];


            //create a connection with the database using the proper function defined in storage_service_util.php
            m::getConnection();


            if( (isset($_GET['isDir'])) AND ($_GET['isDir'] == true) )
            {

                m::make_dir($_GET['user'], $path, $name);

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
                    $version_uuid = util::uuid_v4();

                    // Open php input (the file I want to upload)
                    $input = file_get_contents('php://input');


                    $url = 'http://persistent-api/uploader.php?fileToUpload='.$version_uuid;

                    while(!feof($input)) {
                          echo fread($input, 8192);
                      }

                     // Close php input
                    fclose($input);

                    $possible_uuid_toberemoved = m::add_version($_GET['user'], $path, $name, $version_uuid, $_GET['size']); //WARNING: is 'size' given?

                    if(u::is_uuid($possible_uuid_toberemoved))
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
    catch(DbException $d)
    {
        $this->error(503);
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
