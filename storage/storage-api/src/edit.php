<?php

include 'storage_service_util.php';

//possible parameter in $_POST:
//user      (the user uuid)
//path      (the absolute name of the element, i.e. /path/andName)
//rename    (cointaining the new name)
//move      (containing the new path to be placed)
//---
//please pay attention that:
//moving into a non-existent directory will throw an exception
//renaming into a file or a directory that already exists will throw an exception (it would be trickier managing the merge of versions)
//if rename and move are both set, the file will be moved in the move directory with the rename name

if( (isset($_POST['user'])) AND (isset($_POST['path'])) )
{
    try{
    if(!(isset($_POST['rename'])) AND !(isset($_POST['move'])) )
        throw new IllegalDataException(); //nothing to be done...

    $twopieces = divide_path_from_last($path);



    //check time!!
    if( (!is_uuid($_POST['user'])) )
        throw new IllegalDataException();

    if(isset($_POST['rename']) AND !(is_file_name($_POST['rename'])) )
        throw new IllegalDataException();
    //no check on paths, because pathify will check them and throw an exception if necessary




    //create a connection with the database using the proper function defined in storage_service_util.php
    $myConn = getConnection();



    if(!(isset($_POST['move'])))
        rename_element($conn, $_POST['user'], $twopieces[0], $twopieces[1], $_POST['rename']);
    else
        if(!(isset($_POST['rename'])))
            move_element(($conn, $_POST['user'], $twopieces[0], $twopieces[1], pathify($_POST['move']), $twopieces[1]);
        else //at this point every field is set
            move_element(($conn, $_POST['user'], $twopieces[0], $twopieces[1], pathify($_POST['move']), $_POST['rename']);


    http_response_code(200);
    }
    finally{$myConn = null;}



}
