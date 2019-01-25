<?php


//user      MANDATORY (the user uuid)
//path      MANDATORY (the absolute name of the element to be removed, i.e. /path/andName)
//version   if a file, the version to be removed. If a file and not specified, the maximum version will be removed
//behaviour: if the version number, the path or the filename does not respect the general rules, it's InvalidArgumentException

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

                $version = ( (isset($_GET['version'])) ? $_GET['version'] : NULL);

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

                if(!empty($stack))
                {
                    foreach($stack as $v_uuid) //each of them correspond to a file version, i.e. a physical file in the persistent
                        file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$v_uuid);
                }

                $this->success(200);


        }

        catch(NoContentException $n)
        {
            $this->error(204);
        }
        catch(InvalidArgumentException $e)
        {
            $this->error(400);
        }
        catch(DbException $e)
        {
            $this->error(500);
        }
        catch(DataNotFoundException $f)
        {
            $this->error(404);
        }
        catch(ConflictException $c)
        {
            $this->error(409);
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
