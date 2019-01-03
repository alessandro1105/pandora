<?php

/*
The module that contains:
- check functions
- pdo utilities functions
- functions to connect with persistent service
- functions to perform queries in the storage-db
*/




//usefully transform /etc/div/ into etc/div
function pathify($pathToBe)
{

    if( (substr($path, 0, 1) == '/') ) //let"s trash the initial / symbol if it"s present
        $path = substr($path, 1);

    if( (substr($path, -1, 1) == '/') ) //let"s trash the final / if it"s present
        $path = substr($path,0,-1);

    return $path;
}

//check for UUID version 4
function is_uuid($toCheck)
{
    $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    if(preg_match($UUIDv4, $toCheck))
        return true;
    return false;
}

function is_file_name($toCheck)
{
    //having a / or being empty is illegal for a file name (without path)
    if( (strpos($toCheck, '/') === true) OR ($toCheck == '') )
        return false;
    return true;
}

function getConnection()
{

    $servername = "storage-db";
    $username = "postgres";
    $password = "pandora1";

    try
    {
        $conn = new PDO("mysql:host=$servername;dbname=myDB", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }

    catch(PDOException $e)
    {
        throw new ConnectiondbException();
    }

}

/*
* return a string with the result
*/
function do_fetch($conn, $sql, $colName)
{

        $stmt = $conn->prepare($sql);
        try
        {
            $stmt->execute();

            $result = $stmt->fetch();

            if(! $result )
            {
                throw new DataNotFoundException("Could not get data: " . mysql_error());
            }
        }
        catch(PDOException $e)
        {
            throw new DataNotFoundException("Error from pdo: ". $e->getMessage();)
        }

        //maybe some check on colName?? But in a utility internal function maybe not necessary...
        return $result[$colName];

}

/*
*
* return a json encoded string
*/
function do_fetchAll($conn, $sql)
{
    $stmt = $conn->prepare($sql);

    try
    {
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(! $result )
        {
            throw new DataNotFoundException("Could not get data: " . mysql_error());
        }
    }
    catch(PDOException $e)
    {
        throw new DataNotFoundException("Error from pdo: ". $e->getMessage();)
    }

    return json_encode($result); //i"m expecting only one uuid
}


//asking the persistent storage service to...

//...download a file...
function download_from_persistent($file_uuid)
{
    $url = 'http://persistent-api/downloader.php?fileToDownload='.$file_uuid;

    return http_get($url);
}

//...upload a file...
function upload_to_persistent($file_uuid)
{


}

//...remove a file.
function remove_from_persistent($file_uuid)
{




}





//-----db queries functions follows




/*
* return a json_encoded string
*/
function list($conn, $user, $path)
{

    $last_dir_uuid = get_last_element_uuid($conn, $user, $path);

    $sql="SELECT F.file_name, F.is_dir, F.creation_time "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."';";

    return do_fetchAll($conn, $sql);

}

/*
* scan until last file or folder uuid is found
* return an uuid in a string
*/
function get_last_element_uuid($conn, $user, $path)
{

        //extract the token from the path
        $parts = array_filter(explode('/', $path), 'strlen'); //tested: it DOESN"T include empty strings as array elements nor / ... surely legit filenames

        //1: retrieve the root uuid for the given user
        $parent_uuid = get_root_uuid($conn, $user);

        foreach($parts as $t)
        {
            $parent_uuid = get_child_uuid($conn, $parent_uuid, $t);
        }

        $last_element = $parent_uuid; //just for clarity..

        return $last_element;


}


function get_root_uuid($conn, $user)
{

    $sql = "SELECT IFNULL (( SELECT uuid FROM file WHERE user_uuid = '".$user."' AND file_name = '/' ), '');";

    return do_fetch($conn, $sql, "uuid");
}

function get_child_uuid($conn, $parent_uuid, $fname)
{

    //get the child"s uuid with name equals to $fname

    $sql="SELECT F.uuid "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."' AND F.file_name = '".$fname."';";

    return do_fetch($conn, $sql, "uuid");
}





function file_download($conn, $user, $path, $fileName, $version)
{

    $completeName = $path.'/'.$fileName;

    $myfile_uuid = get_last_element_uuid($conn, $user, $completeName);

    //version==0 has the special meaning of: take the latest version
    if($version===0)
    {
        $sql = "SELECT V.uuid "
             . "FROM version AS V INNER JOIN file AS F "
             . "ON F.uuid = V.uuid_file "
             . "WHERE F.uuid = '".$myfile_uuid."' AND V.version_number = ( "
             . "SELECT MAX(V2.version_number) "
             . "FROM version AS V2 "
             . "WHERE V2.uuid_file = '".$myfile_uuid."' "
             . ");";

        $uuid_for_persistent = do_fetch($conn, $sql, "uuid");

        return download_from_persistent($uuid_for_persistent);

    }
    else
    {
        $sql = "SELECT V.uuid "
             . "FROM version AS V INNER JOIN file AS F "
             . "ON F.uuid = V.uuid_file "
             . "WHERE F.uuid = '".$myfile_uuid."' AND V.version_number = '".$version."';"

        $uuid_for_persistent = do_fetch($conn, $sql, "uuid");

        return download_from_persistent($uuid_for_persistent);

    }

}


class DbException extends Exception {  }
class IllegalDataException extends Exception {  }

class ConnectiondbException extends DbException {  }
class DataNotFoundException extends DbException {  }
