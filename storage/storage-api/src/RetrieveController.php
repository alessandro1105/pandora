<?php

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
                if($version!=NULL OR $info!=NULL) //cannot do any of them if I've a directory
                    throw new InvalidArgumentException();

                //if it is a directory, then the only op allowed here is listing the content
                echo json_encode($ss->list($user, $path));

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

            }



        }
        catch(InvalidArgumentException $e)
        {
            $this->error(400);
        }
        catch(DbException $e)
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
