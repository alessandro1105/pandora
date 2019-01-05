<?php

//the controller that dispatch to the right function in storage_service_util.php

include 'storage_service_util.php';

//possible parameter in $_POST: user, path, isDir
//if isDir is not set or false then there must be a parameter in $_FILE: fileToUpload

if( (isset($_POST['user'])) AND (isset($_POST['path'])) )
{
    //create a connection with the database using the proper function defined in storage_service_util.php
    $myConn = getConnection();

    $path = pathify($_POST['path']); // check ( not empty & not containing // ) removing the possible initial / (that surely is present) and final /

    //check on useruuid and on path
    if( (!is_uuid($_POST['user'])) )
        throw new IllegalDataException();

    else
    {

        if( (isset($_POST['isDir'])) AND ($_POST['isDir'] == true) )
        {
            //The last directory in the file is the one I need to create

            $arrpath = explode('/',$path); //it has at least one element thanks to previous pathify checks


            //let's put in strpath the path excluding the directory I want to make
            $strpath='/'; //so that is not empty and I can use pathify even if no other directories are added
            for($i=0; $i<=count($arrpath)-2; $i++) //not executed if the array has only one element
                $strpath=$strpath.$arrpath[$i].'/';
            $strpath = pathify($strpath); //let's trash the final and initial /


            $my_dir = $arrpath[count($arrpath)-1]; //the last element, a.k.a. the directory I want to create


            if(!is_file_name($my_dir))
                throw new IllegalDataException();


            make_dir($myConn, $_POST['user'], $strpath, $my_dir);
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

    }

    http_response_code(200);
    $myConn = null;
}
