<?php

/*
The module that contains:
- check functions
- pdo utility functions
- functions to connect with persistent service
- functions to perform queries in the storage-db
*/

//uuid v4 generator
function uuid_v4()
{
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

/*
* Utility to separate the path into two pieces: (the path -1 element) and (the element)
* It also do additional checks like pathify and is_file_name
*/
function divide_path_from_last($path)
{

    $path = pathify($path);

    if(strlen($path) == 0 )
        throw new IllegalDataException(); //divide an empty path into more pieces is a mischievious action that will be punished


    //The last directory in the file is the one I need

    $arrpath = explode('/', pathify($path) );

    if(count($arrpath) == 1) //only one element that for previous check is not the empty string
        return ['', $arrpath[0]]; //this element is in the root directory

    //let's put in strpath the path excluding the last element
    $strpath='/'; //so that is not empty and I can use pathify even if no other directories are added
    for($i=0; $i<=count($arrpath)-2; $i++) //not executed if the array has only one element
        $strpath=$strpath.$arrpath[$i].'/';


    $strpath = pathify($strpath); //let's trash the final and initial / ... it may return the empty string, it's fine (the last_el is in root)


    $last_el = $arrpath[count($arrpath)-1]; //the last element, a.k.a. a dirname or a filename


    if(!is_file_name($last_el))
        throw new IllegalDataException();

    return [$strpath, $last_el];

}

//throw exceptions if path is empty or contain // character (which means a directory name is empty string, that is illegal)
//usefully transform /etc/div/ into etc/div
//if the input is / it will return an empty string, but it cannot receive as input an empty string

//---ALL THE COMMENTS BELOW OVERRIDE SOME ASPECTS FROM ABOVE:
//NEW FEATURE: it won't throw anymore an exception if an empty string is passed, it will be consider as a root. So now pathify is idempotent
function pathify($pathToBe)
{
    if( preg_match('/\/\//',$pathToBe) )
        throw new IllegalDataException();

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
    if( (strpos($toCheck, '/') === true) OR ($toCheck == '') OR (strlen($toCheck) > 255))
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
* fetching a single row result from a query to storage-db
* return a string with the result
*/
function do_fetch($conn, $sql, $colName)
{

        try
        {

            $result = $conn->query($sql)->fetch();

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
* fetching a single row result from a query to storage-db
* return a json encoded string
*/
function do_fetchAll($conn, $sql)
{

    try
    {

        $result = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if(! $result )
        {
            throw new DataNotFoundException("Could not get data: " . mysql_error());
        }

        return $result;
    }
    catch(PDOException $e)
    {
        throw new DataNotFoundException("Error from pdo: ". $e->getMessage();)
    }


}





//asking the persistent storage service to...

//...download a file...
function download_from_persistent($file_uuid)
{
    $url = 'http://persistent-api/downloader.php?fileToDownload='.$file_uuid;

    return http_get($url);
}

//...upload a file...
/*
* $file_uuid    file to be uploaded uuid
* $file         $_FILE["fileToUpload"] variable, which contains the file and all its information
*/
function upload_to_persistent($file_uuid, $file)
{


}

//...remove a file.
function remove_from_persistent($file_uuid)
{




}


// le funzioni per rispondere a backend
function send_response_to_backend(....)
{


}

function send_data_to_backend(....)
{



}








//-----db queries functions follows




/*
* return the json put in a string
*/
function list($conn, $user, $path)
{

    $last_dir_uuid = get_last_element_uuid($conn, $user, $path);

    $sql="SELECT F.file_name, F.is_dir, F.creation_time "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."';";

    return json_encode(do_fetchAll($conn, $sql));

}

/*
* scan until last file or folder uuid is found
* return an uuid in a string
+ base case: if an empty path is in input it will return the root uuid
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

            if( $parent_uuid == 'notfound')
                return 'notfound';
        }

        $last_element = $parent_uuid; //just for clarity..

        return $last_element;


}


function get_root_uuid($conn, $user)
{

    //usefully return the empty string if no root for the user was found
    $sql = "SELECT IFNULL (( SELECT uuid FROM file WHERE user_uuid = '".$user."' AND file_name = '/' ), 'noroot');";

    //the user has no root folder, let's create it
    if ($usr_root == "noroot")
        return make_root_dir($conn, $user);

    return do_fetch($conn, $sql, "uuid");
}

function get_child_uuid($conn, $parent_uuid, $fname)
{

    //get the child"s uuid with name equals to $fname

    $sql="SELECT IFNULL (( SELECT F.uuid "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."' AND F.file_name = '".$fname."' ), 'notfound');";

    return do_fetch($conn, $sql, "uuid");
}

function get_children_uuid($conn, $parent_uuid)
{
    if(!get_if_is_dir($conn, $parent_uuid))
        throw new IllegalDataException();

    $sql="SELECT IFNULL (( SELECT F.uuid "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."' ), 'nochildren');";

    return do_fetchAll($conn, $sql);
}


function get_all_versions_uuid($conn, $myfile_uuid)
{
    $sql =  . "SELECT uuid "
            . "FROM version "
            . "WHERE uuid_file = '".$myfile_uuid."'  ;";

    return do_fetchAll($conn, $sql);

}

function get_highest_version_uuid($conn, $myfile_uuid)
{
    $sql = "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' AND version_number = ( "
         . "SELECT MAX(V2.version_number) "
         . "FROM version AS V2 "
         . "WHERE V2.uuid_file = '".$myfile_uuid."' "
         . ");";

    return do_fetch($conn, $sql, "uuid");
}

function get_lowest_version_uuid($conn, $myfile_uuid)
{
    $sql = "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' AND version_number = ( "
         . "SELECT MIN(V2.version_number) "
         . "FROM version AS V2 "
         . "WHERE V2.uuid_file = '".$myfile_uuid."' "
         . ");";

    return do_fetch($conn, $sql, "uuid");
}

function get_number_of_versions_present($conn, $myfile_uuid)
{
    $sql = "SELECT COUNT(*) FROM ( "
         . "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' ) "
         . ") tableAlias ;";

    return do_fetch($conn, $sql, 'COUNT(*)');
}

function get_this_version_uuid($conn, $version, $myfile_uuid)
{
    $sql = "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' AND version_number = '".$version."';"

    return do_fetch($conn, $sql, "uuid");
}

/*
* this function receives as input a filename
* returns if it is a directory (true) or a file (false)
*/
function get_if_is_dir($conn, $myfile_uuid)
{
    $sql = "SELECT is_dir "
            . "FROM file "
            . "WHERE uuid = '".$myfile_uuid."' ;"

    return do_fetch($conn, $sql, "is_dir");
}


function file_download($conn, $user, $path, $fileName, $version)
{

    $completeName = $path.'/'.$fileName;

    $myfile_uuid = get_last_element_uuid($conn, $user, $completeName);

    //version==0 has the special meaning of: take the latest version
    if($version===0)
    {

        return download_from_persistent(get_highest_version_uuid($conn, $myfile_uuid));

    }
    else
    {
        $uuid_for_persistent = get_this_version_uuid($conn, $version, $myfile_uuid);

        return download_from_persistent($uuid_for_persistent);

    }

}








/*
* $conn         is the connection (hopefully opened) to communicate with storage-db
* $parent_uuid  uuid of the parent (NULL if I want to insert root)
* $arr          the array contains the ordered value to be inserted.
*
* Keep dinstinct this insertion from the others, due to additional existence check and so different input parameters set
*/
function do_ins_in_file($conn, $parent_uuid, $arr)
{
    if(get_child_uuid($conn, $parent_uuid, $arr[1]) != 'notfound')
        throw new DataAlreadyPresentException(); //cannot insert a file if it's already present a file in the same directory with the same name

    $in_file = "INSERT INTO file (uuid, file_name, user_uuid, is_dir) VALUES (?,?,?,?)";

    $stmt= $pdo->prepare($in_file);
    $stmt->execute($arr);

}


/*
* This function do the insertion into a predefined table (different from file table) in the storage-db
* $conn     is the connection (hopefully opened) to communicate with storage-db
* $table    specify the table where to do the insertion
* $arr      the array contains the ordered value to be inserted.
*/
function do_ins($conn, $table, $arr)
{
    $in_has_parent = "INSERT INTO has_parent (uuid_child, uuid_parent) VALUES (?,?)";
    $in_version = "INSERT INTO version (uuid, file_size, uuid_file) VALUES (?,?,?)";


    $sql='no table selected, so this is an invalid sql query'; // no exception is thrown, it's just an internal error that should never happen


    if($table == 'has_parent')
        $sql=$in_has_parent;
    else
        if($table == 'version')
            $sql=$in_version;


    $stmt= $pdo->prepare($sql);
    $stmt->execute($arr);
}



function do_del($conn, $table, $uuid)
{
    $del_file = "DELETE FROM file WHERE uuid = '".$uuid."' ;";
    $del_version = "DELETE FROM version WHERE uuid = '".$uuid."' ;";
    $del_has_parent = "DELETE FROM has_parent WHERE uuid_child = '".$uuid."' ;";

    $sql='no table selected, so this is an invalid sql query'; // no exception is thrown, it's just an internal error that should never happen

    if($table == 'file')
        $sql=$del_file;

    if($table == 'has_parent')
        $sql=$del_has_parent;
    else
        if($table == 'version')
            $sql=$del_version;

    $stmt= $pdo->prepare($sql);
    $stmt->execute($arr);


}


//It will return the uuid of the root if correctly created
function make_root_dir($conn, $user)
{
    $usr_root=uuid_v4();

    //maybe using a transaction will be better?
    do_ins_in_file($conn, NULL, [$usr_root, "/", $user, TRUE]);
    do_ins($conn, 'has_parent', [$usr_root, NULL]);

    return $usr_root;
}


/*
* $conn
* $user
* $path         path where the new dir will be inserted. If empty, it's the root directory
+ $dir_name     a valid file name. This makes impossible to use this function solely to create the root directory
*/
function make_dir($conn, $user, $path, $dir_name)
{
    if(!is_file_name($dir_name))
        throw new IllegalDataException();


    $parent_uuid = get_last_element_uuid($conn, $user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    $my_dir_uuid = uuid_v4();


    do_ins_in_file($conn, $parent_uuid, [$my_dir_uuid, $dir_name, $user, TRUE]); //an exception can be thrown if a file with same name is already present in parent
    do_ins($conn, 'has_parent', [$my_dir_uuid, $parent_uuid]);

}



/*
*
*
* $myfile   è la variabile $_FILE["fileToUpload"] che contiene il file e tutte le sue informazioni
*/
function file_upload($conn, $user, $path, $myfile)
{
    if(!is_file_name($myfile["name"]))
        throw new IllegalDataException();

    $my_file_version_uuid = uuid_v4();

    //FIRST of all try to update the version as a physical file in the persistent storage
    upload_to_persistent($my_file_version_uuid, $myfile);



    //IF the update of the file version was successful, then save the data in the storage-db

    $parent_uuid = get_last_element_uuid($conn, $user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    $myfile_uuid = get_child_uuid($conn, $parent_uuid, $myfile["name"]);

    if( $myfile_uuid != 'notfound' )
    {

        //if the number of version in the database is greater than MAX_VERSIONAMOUT_FOR_FILE, remove the version with lowest number
        //MAX_VERSIONAMOUT_FOR_FILE = 10
        if(get_number_of_versions_present($conn, $myfile_uuid) > 10)
        {
            $lowest_version_uuid = get_lowest_version_uuid($conn, $myfile_uuid);

            do_del($conn, 'version', $lowest_version_uuid);

            remove_from_persistent($lowest_version_uuid); //if it fails, no data is kept in the storage-db, so the user cannot reach it and have more than 10 versions
        }

        do_ins($conn, 'version', [$my_file_version_uuid, $myfile["size"], $myfile_uuid]);

    }
    else
    {
        //file not present, I need to create a whole new file in file table
        $newfile_uuid = uuid_v4();

        do_ins_in_file($conn, $parent_uuid, [$newfile_uuid, $myfile["name"], $user, FALSE]);
        do_ins($conn, 'version', [$my_file_version_uuid, $myfile["size"], $newfile_uuid]);
    }



}


function move_element($conn, $user, $path, $name, $newpath, $newname)
{
    if(($name==$newname) AND ($path==$newpath))
        return; //no business here to be done...



    //maybe all these controls are too much....
    if(!is_file_name($name) OR !is_file_name($newname))
        throw new IllegalDataException();

    if(!is_uuid($user))
        throw new IllegalDataException();

    $path = pathify($path);
    $newpath = pathify($newpath);
    //-------




    //uuid of the file to be renamed and/or moved
    $myfile_uuid = get_last_element_uuid($conn, $user, $path.'/'.$name);

    //first of all, check whether an element with the same name already exists in the new path or the element to move doesn't exist
    if( ( get_last_element_uuid($conn, $user, $newpath.'/'.$newname) != 'notfound' ) OR ($myfile_uuid == 'notfound') )
        throw new IllegalDataException();

    try
    {
        $conn->beginTransaction();

        //renaming is required
        if($name!=$newname)
        {
            $conn->exec("UPDATE file SET file_name = '".$newname."' WHERE uuid = '".$myfile_uuid."';");
        }

        //moving is required
        if($path!=$newpath)
        {
            //getting the uuid of the future parent
            $future_parent_uuid = get_last_element_uuid($conn, $user, $newpath);

            $conn->exec("UPDATE has_parent SET uuid_parent = '".$future_parent_uuid."' WHERE uuid_child = '".$myfile_uuid."';");
        }
        $conn->commit();

    }
    catch(PDOException $e)
    {
        $conn->rollBack();
        throw new DbException($e->getMessage());
    }

}


/*
* unix-like approach: renaming the element is just moving it in the same directory he's in with a different name
*/
function rename_element($conn, $user, $path, $name, $newname)
{
    return move_element($conn, $user, $path, $name, $path, $newname);
}




//it doesn't contain the beginTransaction and the commit, so it's more versatile
//0 is interpreted as maximum version number
function remove_version($conn, $file_uuid, int $version_number, &$stack)
{
    $v_uuid;

    if($version_number===0)
    {
        $v_uuid = get_highest_version_uuid($conn, $file_uuid);
        $conn->exec("DELETE FROM version WHERE uuid = '".$v_uuid."';");

    }
    else
    {
        $v_uuid = get_this_version_uuid($conn, $version_number, $file_uuid);
        $conn->exec("DELETE FROM version WHERE uuid = '".$v_uuid."';");
    }

    array_push($stack, $v_uuid);
}

function remove_all_version_($conn, $file_uuid)
{
    $conn->exec("DELETE FROM version WHERE uuid_file = '".$file_uuid."';");
}


function remove_rec_element($conn, $user, $myelement_uuid, $version, &$stack)
{

    //base cases
    if(is_int($version)) //i'm dealing with one version... neat!
    {
        return remove_version($conn, $myelement_uuid, $version, &$stack);
    }

    if(!get_if_is_dir($conn, $myelement_uuid)) //i'm dealing with a file with one or more versions
    {
        foreach(get_all_versions_uuid($conn, $myelement_uuid) as $v)
        {
            array_push($stack, $v);
        }
        return remove_all_versions($conn, $myelement_uuid);
    }

    $children_uuid = get_children_uuid($conn, $myelement_uuid);

    if(!($children_uuid == 'nochildren'))
        foreach($children_uuid as $child_uuid)
            remove_rec_element($conn, $user, $child_uuid, null, &$stack);

    $conn->exec("DELETE FROM file WHERE uuid = '".$myelement_uuid"';"); //finally removing this directory


}

//
function remove_element($conn, $user, $path, $name, $version)
{
    try
    {
        $conn->beginTransaction();

        $stack_of_uuid = array();
        remove_rec_element($conn, $user,  get_last_element_uuid($conn, $user, $path.'/'.$name), $version, &$stack_of_uuid);

        $conn->commit();

        foreach($stack_of_uuid as v_uuid)
            remove_from_persistent(v_uuid);
    }
    catch(PDOException $e)
    {
        $conn->rollBack();
        throw new DbException($e->getMessage());
    }

}






class DbException extends Exception {  }
class IllegalDataException extends Exception {  }

class ConnectiondbException extends DbException {  }
class DataNotFoundException extends DbException {  }
class DataAlreadyPresentException extends DbException {  }
