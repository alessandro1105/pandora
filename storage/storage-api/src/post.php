<?php

//the controller that dispatch to the right function in storage_service_util.php

include 'storage_service_util.php';

//possible parameter in $_POST: user, path, isDir
//if isDir is not set or false then there must be a parameter in $_FILE: fileToUpload

if( (isset($_POST['user'])) AND (isset($_POST['path'])) )
{

try{
    $path = pathify($_POST['path']); // check ( not empty & not containing // ) removing the possible initial / (that surely is present) and final /

    //check on useruuid and on path
    if( (!is_uuid($_POST['user'])) )
        throw new IllegalDataException();




    //create a connection with the database using the proper function defined in storage_service_util.php
    $myConn = getConnection();


    if( (isset($_POST['isDir'])) AND ($_POST['isDir'] == true) )
    {
        $scissor = divide_path_from_last($path);

        make_dir($myConn, $_POST['user'], $scissor[0], $scissor[1]);
    }
    else
        if( !(isset($_FILES["fileToUpload"]))
            OR (trim($_FILES["fileToUpload"]["name"]) == '')
            OR (!is_uploaded_file($_FILES["fileToUpload"]["tmp_name"]))
            OR ($_FILES["fileToUpload"]["error"]>0)
          )
            throw new IllegalDataException(); //maybe something else, like UploadErrorException?

        else
            file_upload($myConn, $_POST['user'], $path, $_FILES["FileToUpload"] );


    http_response_code(200);
}
finally{$myConn = null;}
}
