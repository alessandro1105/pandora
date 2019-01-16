<?php

namespace App\Components\Storage\Get;

use \InvalidArgumentException;

use App\Components\Storage\Util\StorageServiceUtil as util;
use App\Components\Storage\Model\StorageServiceModel as m;
/*
The controller that dispatch to the right function in StorageServiceUtil.php
- list the content of a directory
- download a file version
*This file expects the following argument:
* user and path are mandatory. Empty path will be interpreted as the root directory for the choosen user
* filename is optional. If present, also version could be present
*/

class GetController
{

    public function action($router, $storageService)
    {
        try
        {
            if( (isset($_GET['user'])) AND (isset($_GET['path'])) )
            {

                $path = util::pathify($_GET['path']); // removing the possible initial / (that surely is present) and final /

                //check on useruuid and on path
                if( (!util::isUuid($_GET['user'])) )
                    throw new InvalidArgumentException();

                //if necessary activate a connection with the database using the proper function



                //call the proper function accorting to what it is set
                if(!isset($_GET['fileName']))
                    echo json_encode(m::list($_GET['user'], $path));

                else if(!util::isFileName($_GET['fileName']))
                    throw new InvalidArgumentException();

                else if(!isset($_GET['version']) AND isset($_GET['info']))
                    echo json_encode(m::getAllVersionsData($_GET['user'], $path, $_GET['fileName']));

                else if(!isset($_GET['version']))
                    self::fileDownloading(m::getFileUuid($_GET['user'], $path, $_GET['fileName'], 0));

                else if(!is_int($_GET['version')) OR ($_GET['version']<0) ) //0 or version non set has the same meaning: take the highest version
                        throw new InvalidArgumentException();
                else
                    self::fileDownloading(m::getFileUuid($_GET['user'], $path, $_GET['fileName'], $_GET['version']));

                this->success(200);

            }
        }
        catch(InvalidArgumentException $e)
        {
            $this->error(400);
        }
        catch(DataNotFoundException $e)
        {
            $this->error(400);
        }
    }


    private static function fileDownloading($file_uuid)
    {
        $url = 'http://persistent-api/downloader.php?fileToDownload='.$file_uuid;

        // Open php output
        $output = fopen('php://output','w');

        while(!feof(file_get_contents($url))) {
              fwrite($file, fread(file_get_contents($url), 8192), 8192);
          }

         // Close php output
        fclose($output);
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
