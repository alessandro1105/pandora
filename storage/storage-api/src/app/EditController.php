<?php

/*
//user      (the user uuid) MANDATORY
//path      (the absolute name of the element, i.e. /path/andName) MANDATORY
//rename    (cointaining the new name) OPTIONAL NOT EXCLUSIVE WITH MOVE
//move      (containing the new path to be placed) OPTIONAL NOT EXCLUSIVE WITH RENAME
//---
//please pay attention that:
//moving into a non-existent directory is not possible
//renaming into a file or a directory that already exists is not possible (it would be trickier managing the merge of versions)
//if rename and move are both set, the file will be moved in the move directory with the rename name
*/
class EditController
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

                $rename = ( (isset($_GET['rename'])) ? $_GET['rename'] : NULL);
                $move = ( (isset($_GET['move'])) ? $_GET['move'] : NULL);

        //------------------------------------------------------END PARAMETERS RETRIEVAL




                //the object on which the methods will be called
                $ss = new StorageService();




        try
        {



                if($move == NULL AND $rename == NULL)
                    throw new InvalidArgumentException(); //nothing to be done...

                $twopieces = StorageServiceUtil::dividePathFromLast($path);

                $path=$twopieces[0];
                $name=$twopieces[1];



                if($move == NULL)
                    $ss->renameElement($user, $path, $name, $rename);
                else if($rename == NULL)
                    $ss->moveElement($user, $path, $name, $move, $name);
                else if($rename != NULL AND $move != NULL)
                    $ss->moveElement($user, $path, $name, $move, $rename);
                else
                    throw new InvalidArgumentException();



                $this->success(200);

        }
        catch(InvalidArgumentException $e)
        {
            $this->error(400, [
                                    'errors' => [
                                        'badRequest' => 'The data in the request is wrong.'
                                    ]
                                ]);
            return false;
        }
        catch(DbException $e)
        {
            $this->error(500, [
                                    'errors' => [
                                        'internalError' => 'A problem in the storage database occured.'
                                    ]
                                ]);
            return false;
        }
        catch(DataNotFoundException $f)
        {
            $this->error(404, [
                                    'errors' => [
                                        'notFound' => 'The data in the request were not found.'
                                    ]
                                ]);

            return false;
        }
        catch(ConflictException $c)
        {
            $this->error(409, [
                                    'errors' => [
                                        'conflict' => 'The data in the request generated a conflict.'
                                    ]
                                ]);

            return false;
        }

    }

    private function success($statusCode)
    {
        http_response_code($statusCode);
    }

    private function error($errorCode, $errors = array())
    {
        // Setting status code
        http_response_code($errorCode);
        if ($errors != array())
        {
            // Setting the content type of the request
            header('Content-Type: application/json');
            // echo the response
            echo json_encode($errors, JSON_PRETTY_PRINT);
        }
    }

}
