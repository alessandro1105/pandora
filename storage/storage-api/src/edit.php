<?php

namespace App\Components\Storage\Edit;

use \InvalidArgumentException;

use App\Components\Storage\Util\storage_service_util as util;
use App\Components\Storage\Model\storage_service_model as m;

//possible parameter in $_GET:
//user      (the user uuid)
//path      (the absolute name of the element, i.e. /path/andName)
//rename    (cointaining the new name)
//move      (containing the new path to be placed)
//---
//please pay attention that:
//moving into a non-existent directory will throw an exception
//renaming into a file or a directory that already exists will throw an exception (it would be trickier managing the merge of versions)
//if rename and move are both set, the file will be moved in the move directory with the rename name

class EditController
{

    public function action($router, $storageService)
    {
        try
        {
            if( (isset($_GET['user'])) AND (isset($_GET['path'])) )
            {
                try{
                if(!(isset($_GET['rename'])) AND !(isset($_GET['move'])) )
                    throw new InvalidArgumentException(); //nothing to be done...

                $twopieces = util::divide_path_from_last($path);

                $path=$twopieces[0];
                $name=$twopieces[1];


                //check time!!
                if( (!util::is_uuid($_GET['user'])) )
                    throw new InvalidArgumentException();

                if(isset($_GET['rename']) AND !(util::is_file_name($_GET['rename'])) )
                    throw new InvalidArgumentException();
                //no check on paths, because pathify will check them and throw an exception if necessary



                //create a connection with the database using the proper function defined in storage_service_util.php
                m::getConnection();



                if(!(isset($_GET['move'])))
                    m::rename_element($_GET['user'], $path, $name, $_GET['rename']);
                else
                    if(!(isset($_GET['rename'])))
                        m::move_element(($_GET['user'], $path, $name, util::pathify($_GET['move']), $name);
                    else //at this point every field is set
                        m::move_element(($_GET['user'], $path, $name, util::pathify($_GET['move']), $_GET['rename']);


                $this->success(200);
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
