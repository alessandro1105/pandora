<?php

/*
The controller that dispatch to the right function in storage_service_util.php
- list the content of a directory
- download a file version
*/
include 'storage_service_util.php';

if( (isset($_GET['user'])) AND (isset($_GET['path'])) )
{
    //create a connection with the database using the proper function defined in storage_service_util.php
    $myConn = getConnection();

    $path = pathify($_GET['path']); // removing the possible initial / (that surely is present) and final /

    //check on useruuid and on path
    if( (!is_uuid($_GET['user'])) )
        throw new IllegalDataException();

    else
    {
        //call the proper function accorting to what it is set
        if(!isset($_GET['fileName']))
            list($myConn, $_GET['user'], $path);
        else
            if(!isset($_GET['version']))
                file_download($myConn, $_GET['user'], $path, $_GET['fileName'], 0);
            else
                if(!is_int($_GET['version')) OR ($_GET['version']<=0) )
                    throw new IllegalDataException();
                else
                    file_download($myConn, $_GET['user'], $path, $_GET['fileName'], $_GET['version']);
    }

    $myConn = null;
}
