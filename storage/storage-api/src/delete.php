<?php


namespace App\Components\Storage\Delete;

use \InvalidArgumentException;

use App\Components\Storage\Util\storage_service_util as util;
use App\Components\Storage\Model\storage_service_model as m;

//possible parameter in $_GET:
//user      MANDATORY (the user uuid)
//path      MANDATORY (the absolute name of the element to be removed, i.e. /path/andName)
//version   if a file, the version to be removed. If a file and not specified, the maximum version will be removed
class DeleteController
{

    public function action($router, $storageService)
    {
        try
        {

            if( (isset($_GET['user'])) AND (isset($_GET['path'])) )
            {

                $twopieces = util::divide_path_from_last($path);
                $path = $twopieces[0];
                $name = $twopieces[1];

                //check time!!
                if( (!util::is_uuid($_GET['user'])) )
                    throw new InvalidArgumentException();

                $stack = array();

                if(isset($_GET['version']))
                    if(!is_int($_GET['version']) OR $_GET['version']<0 )
                        throw new InvalidArgumentException();
                    else
                        $stack = remove_element($_GET['user'], $path, $name, $_GET['version']);
                else
                    $stack = remove_element($_GET['user'], $path, $name, NULL);

                foreach($stack as v_uuid) //each of them correspond to a file version, i.e. a physical file in the persistent
                    file_get_contents('http://persistent-api/deleter.php?fileToDelete='.$v_uuid);

                $this->success(200);

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
