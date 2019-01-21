<?php

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

    public function action()
    {

        //PARAMETER RETRIEVAL-----------------------------------------------------------

                //parametri
                //presi dall'url (delle risorse) hardcored
                //quelli da query string prendo da $_GET
                $user = '32f84ae0-2f55-4110-b3ec-ba8a1eb452f1';

                $path = $_GET['path']; //supposed to be in the query string just for testing...

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
                {
                    $this->error(400);
                    return;
                }

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
