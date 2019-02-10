<?php

include 'ConnectionToPersistentException.php';


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



        //------------------------------------------------------END PARAMETERS RETRIEVAL




                //the object on which the methods will be called
                $ss = new StorageService();





        try
        {

                $twopieces = StorageServiceUtil::dividePathFromLast($path);
                $path = $twopieces[0];
                $name = $twopieces[1];


                $stack = array();




                $stack = $ss->removeElement($user, $path, $name);

                if(!empty($stack))
                {
                    foreach($stack as $v_uuid) //each of them correspond to a file version, i.e. a physical file in the persistent
                    {
                        //file_get_contents('http://localhost/persistentService/deleter.php?fileToDelete='.$v_uuid);

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $API_PERSISTENT.'/'.$possible_uuid_toberemoved);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $connection_timeout_sec); //NEW!! timeout in seconds to await for a connection


                        $result = curl_exec($ch);
                        //$result = json_decode($result);

                        //new part... managing timeout:
                        if(curl_errno($c)) //for example, now if I have errno 28 it's a timed out, so it behaves consistently with our failure model
                        {
                            throw new PersistentConnException();
                        }

                        //the non-existence (http response 204 from Persistent) is considered ok, so it's not handled in a particular way

                        curl_close($ch);
                    }
                }

                $this->success(200);


        }

        catch(PersistentConnException $p)
        {
            $this->error(500, [
                                    'errors' => [
                                        'connectionError' => '[debug explanation] curl failed to connect with Persistent Service. Probably a timeout occured.'
                                    ]
                                ]);
            return false;
        }

        catch(NoContentException $n)
        {
            $this->error(204, [
                                    'errors' => [
                                        'noContent' => 'Element not found.'
                                    ]
                                ]);
            return false;
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
            $this->error(204, [
                                    'errors' => [
                                        'NoContent' => 'Element not found.'
                                    ]
                                ]);
            return false;
        }
        catch(ConflictException $c)
        {
            $this->error(204, [
                                    'errors' => [
                                        'NoContent' => 'Element not found.'
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
