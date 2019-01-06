<?php

include 'storage_service_util.php';

//possible parameter in $_POST:
//user      MANDATORY (the user uuid)
//path      MANDATORY (the absolute name of the element to be removed, i.e. /path/andName)
//version   if a file, the version to be removed. If a file and not specified, the maximum version will be removed

if( (isset($_POST['user'])) AND (isset($_POST['path'])) )
{
    try
    {
    $twopieces = divide_path_from_last($path);

    //check time!!
    if( (!is_uuid($_POST['user'])) )
        throw new IllegalDataException();



    if(isset($_POST['version']))
        if(!is_int($_POST['version']) OR $_POST['version']<0 )
            throw new IllegalDataException();
        else
            remove_element($conn, $_POST['user'], $twopieces[0], $twopieces[1], $_POST['version']);
    else
        remove_element($conn, $_POST['user'], $twopieces[0], $twopieces[1], NULL);


    http_response_code(200);
    }
    finally{$myConn = null;}


}
