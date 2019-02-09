<?php

class DeleteController
{

    public function action($router, $request, $API_PERSISTENT)
    {

        //PARAMETER RETRIEVAL-----------------------------------------------------------

                //parametri
                //presi dall'url (delle risorse) hardcored
                //quelli da query string prendo da $_GET
                $user = $router->getParam('uuid');
        $pathExploded = $router->getParam('path');
        $path = '/' . implode('/', $pathExploded);

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
