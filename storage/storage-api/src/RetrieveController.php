<?php

/*
include 'StorageService.php';
include 'StorageServiceUtil.php';
*/

/*
Functionalities:
- list the content of a certain directory
- list all the versions data of a certain file (version_number, creation_time, file_size)
- download a file version

*This file expects the following argument:
* user and path are mandatory. Empty path will be interpreted as the root directory for the choosen user
*/

class RetrieveController
{
    public function action()
    {

//PARAMETER RETRIEVAL-----------------------------------------------------------

        //parametri
        //presi dall'url (delle risorse) hardcored
        //quelli da query string prendo da $_GET
        $user = '32f84ae0-2f55-4110-b3ec-ba8a1eb452f1';

        $path = $_GET['path']; //let's assume for testing that is in the query string

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
                //if it is a directory, then the only op allowed here is listing the content
                echo json_encode($ss->list($user, $path));

                $this->success(200);
                return;
            }

            else if($info)
            {
                //if info is set, then I am asked for the listing of the versions of a certain file
                echo json_encode($ss->getAllVersionsData($user, $startingPath, $element));

                $this->success(200);
                return;
            }
            else
            {
                //last element is not a directory and info not set... this means the actual version is needed
                if($version == NULL)
                {
                    $version = 0;
                }

                $persistentFilename = $ss->getVersionUuid($user, $startingPath, $element, $version);


                $url = 'http://localhost/persistentService/downloader.php?fileToDownload='.$persistentFilename;



                $remote = fopen($url, 'rb');
                //http_response_code(404);
                header('Content-Disposition: attachment; filename="'.$persistentFilename.'"');
                header('Content-type: application/x-compress');
                header("Content-Transfer-Encoding: Binary");
                //header ("Content-Length: " . filesize('mactex-20180417.pkg')); // NOT WORKING
                while(!feof($remote)) {
                    echo(fread($remote, 4096));
                }


                $this->success(200);
                return;

            }

            $this->error(400);

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


    private function success($statusCode)
    {
        http_response_code($statusCode);
    }

    private function error($statusCode)
    {
        http_response_code($statusCode);
    }


}
