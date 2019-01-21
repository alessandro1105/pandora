<?php


//possible parameter in $_GET:
//user      MANDATORY (the user uuid)
//path      MANDATORY (the absolute name of the element to be removed, i.e. /path/andName)
//version   if a file, the version to be removed. If a file and not specified, the maximum version will be removed
class DeleteController
{

    public function action()
    {

        //PARAMETER RETRIEVAL-----------------------------------------------------------

                //parametri
                //presi dall'url (delle risorse) hardcored
                //quelli da query string prendo da $_GET
                $user = '32f84ae0-2f55-4110-b3ec-ba8a1eb452f1';

                $path = $_GET['path']; //supposed to be in the query string just for testing...

                $version = ( (isset($version)) ? $version : NULL);

        //------------------------------------------------------END PARAMETERS RETRIEVAL




                //the object on which the methods will be called
                $ss = new StorageService();





        try
        {

                $twopieces = StorageServiceUtil::dividePathFromLast($path);
                $path = $twopieces[0];
                $name = $twopieces[1];


                $stack = array();


                $stack = $ss->removeElement($user, $path, $name, $version); //version can be null


                foreach($stack as $v_uuid) //each of them correspond to a file version, i.e. a physical file in the persistent
                    file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$v_uuid);

                $this->success(200);


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
