<?php


namespace App\Components\Storage\Model;
use App\Components\Storage\Util\storage_service_util as util;


/*
* This class contains every (and only) utilities to connect and perform queries to the storage-db
*/
class storage_service_model
{

    private static $conn; //a private connection object will be created in getConnection, if not already initialized


static function getConnection()
{

    $servername = "storage-db";
    $username = "postgres";
    $password = "pandora1";

    try
    {
        if(self::$conn == NULL)
        {
            self::$conn = new PDO("mysql:host=$servername;dbname=myDB", $username, $password);
            // set the PDO error mode to exception
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

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
private static function do_fetch($sql, $colName)
{

        try
        {

            $result = self::$conn->query($sql)->fetch();

            if(! $result )
            {
                throw new DataNotFoundException("Could not get data: " . mysql_error());
            }
        }
        catch(PDOException $e)
        {
            throw new DataNotFoundException("Error from pdo: ". $e->getMessage();)
        }

        //maybe some check on colName?? But in a utility internal static function maybe not necessary...
        return $result[$colName];

}

/*
* fetching a single row result from a query to storage-db
* return a json encoded string
*/
private static function do_fetchAll($sql)
{

    try
    {

        $result = self::$conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

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








//6--QUERY FUNCTIONS SECTION



/*
* return the list of the uuid of the file in a certain directory
*/
static function list($user, $path)
{

    $last_dir_uuid = self::get_last_element_uuid($user, $path);

    $sql="SELECT F.file_name, F.is_dir, F.creation_time "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."';";

    return self::do_fetchAll($sql);

}

/*
* scan until last file or folder uuid is found
* return an uuid in a string
+ base case: if an empty path is in input it will return the root uuid
*/
static function get_last_element_uuid($user, $path)
{

        //extract the token from the path
        $parts = array_filter(explode('/', $path), 'strlen'); //tested: it DOESN"T include empty strings as array elements nor / ... surely legit filenames

        //1: retrieve the root uuid for the given user
        $parent_uuid = self::get_root_uuid($user);

        foreach($parts as $t)
        {
            $parent_uuid = self::get_child_uuid($parent_uuid, $t);

            if( $parent_uuid == 'notfound')
                return 'notfound';
        }

        $last_element = $parent_uuid; //just for clarity..

        return $last_element;


}


static function get_root_uuid($user)
{

    //usefully return the empty string if no root for the user was found
    $sql = "SELECT IFNULL (( SELECT uuid FROM file WHERE user_uuid = '".$user."' AND file_name = '/' ), 'noroot');";

    //the user has no root folder, let's create it
    if ($usr_root == "noroot")
        return self::make_root_dir($user);

    return self::do_fetch($sql, "uuid");
}

static function get_child_uuid($parent_uuid, $fname)
{

    //get the child"s uuid with name equals to $fname

    $sql="SELECT IFNULL (( SELECT F.uuid "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."' AND F.file_name = '".$fname."' ), 'notfound');";

    return self::do_fetch($sql, "uuid");
}

static function get_children_uuid($parent_uuid)
{
    if(!self::get_if_is_dir($parent_uuid))
        throw new InvalidArgumentException();

    $sql="SELECT IFNULL (( SELECT F.uuid "
        ."FROM file AS F INNER JOIN has_parent AS H "
        ."ON F.uuid = H.uuid_child "
        ."WHERE H.uuid_parent = '".$parent_uuid."' ), 'nochildren');";

    return self::do_fetchAll($sql);
}






static function get_version_uuid($user, $path, $fileName, $version)
{

    $completeName = $path.'/'.$fileName;

    $myfile_uuid = self::get_last_element_uuid($user, $completeName);

    //version==0 has the special meaning of: take the latest version
    if($version===0)
    {

        return self::get_highest_version_uuid($myfile_uuid);

    }
    else
    {
        return self::get_this_version_uuid($version, $myfile_uuid);

    }

}

static function get_all_versions_uuid($myfile_uuid)
{
    $sql =  . "SELECT uuid "
            . "FROM version "
            . "WHERE uuid_file = '".$myfile_uuid."'  ;";

    return self::do_fetchAll($sql);

}

static function get_highest_version_uuid($myfile_uuid)
{
    $sql = "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' AND version_number = ( "
         . "SELECT MAX(V2.version_number) "
         . "FROM version AS V2 "
         . "WHERE V2.uuid_file = '".$myfile_uuid."' "
         . ");";

    return self::do_fetch($sql, "uuid");
}

static function get_lowest_version_uuid($myfile_uuid)
{
    $sql = "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' AND version_number = ( "
         . "SELECT MIN(V2.version_number) "
         . "FROM version AS V2 "
         . "WHERE V2.uuid_file = '".$myfile_uuid."' "
         . ");";

    return self::do_fetch($sql, "uuid");
}

static function get_number_of_versions_present($myfile_uuid)
{
    $sql = "SELECT COUNT(*) FROM ( "
         . "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' "
         . ") tableAlias ;";

    return self::do_fetch($sql, 'COUNT(*)');
}

static function get_this_version_uuid($version, $myfile_uuid)
{
    $sql = "SELECT uuid "
         . "FROM version "
         . "WHERE uuid_file = '".$myfile_uuid."' AND version_number = '".$version."';"

    return self::do_fetch($sql, "uuid");
}

/*
* this static function receives as input a filename
* returns if it is a directory (true) or a file (false)
*/
static function get_if_is_dir($myfile_uuid)
{
    $sql = "SELECT is_dir "
            . "FROM file "
            . "WHERE uuid = '".$myfile_uuid."' ;"

    return self::do_fetch($sql, "is_dir");
}










/*
* self::$conn         is the connection (hopefully opened) to communicate with storage-db
* $parent_uuid  uuid of the parent (NULL if I want to insert root)
* $arr          the array contains the ordered value to be inserted.
*
* Keep dinstinct this insertion from the others, due to additional existence check and so different input parameters set
*/
static function do_ins_in_file($parent_uuid, $arr)
{
    if(self::get_child_uuid($parent_uuid, $arr[1]) != 'notfound')
        throw new DataAlreadyPresentException(); //cannot insert a file if it's already present a file in the same directory with the same name

    $in_file = "INSERT INTO file (uuid, file_name, user_uuid, is_dir) VALUES (?,?,?,?)";

    $stmt= self::$conn->prepare($in_file);
    $stmt->execute($arr);

}


/*
* This static function do the insertion into a predefined table (different from file table) in the storage-db
* $table    specify the table where to do the insertion
* $arr      the array contains the ordered value to be inserted.
*/
static function do_ins($table, $arr)
{
    $in_has_parent = "INSERT INTO has_parent (uuid_child, uuid_parent) VALUES (?,?)";
    $in_version = "INSERT INTO version (uuid, file_size, uuid_file) VALUES (?,?,?)";


    $sql='no table selected, so this is an invalid sql query'; // no exception is thrown, it's just an internal error that should never happen


    if($table == 'has_parent')
        $sql=$in_has_parent;
    else
        if($table == 'version')
            $sql=$in_version;


    $stmt= self::$conn->prepare($sql);
    $stmt->execute($arr);
}



static function do_del($table, $uuid)
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

    $stmt= self::$conn->prepare($sql);
    $stmt->execute($arr);


}


//It will return the uuid of the root if correctly created
static function make_root_dir($user)
{
    $usr_root=util::uuid_v4();

    //maybe using a transaction will be better?
    self::do_ins_in_file(NULL, [$usr_root, "/", $user, TRUE]);
    self::do_ins('has_parent', [$usr_root, NULL]);

    return $usr_root;
}


/*
* self::$conn
* $user
* $path         path where the new dir will be inserted. If empty, it's the root directory
* $dir_name     a valid file name. This makes impossible to use this static function solely to create the root directory
*/
static function make_dir($user, $path, $dir_name)
{
    if(!util::is_file_name($dir_name))
        throw new InvalidArgumentException();


    $parent_uuid = self::get_last_element_uuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    $my_dir_uuid = util::uuid_v4();


    self::do_ins_in_file($parent_uuid, [$my_dir_uuid, $dir_name, $user, TRUE]); //an exception can be thrown if a file with same name is already present in parent
    self::do_ins('has_parent', [$my_dir_uuid, $parent_uuid]);

}



/*
* add a version.
* Returns the uuid of the version uuid equals to the filename to be removed from persistent service if number of versions for that file exceed the limit
*
*/
static function add_version($user, $path, $my_file_name, $my_file_version_uuid, $size)
{
    $uuid_toberemoved='';

    if(!util::is_file_name($my_file_name))
        throw new InvalidArgumentException();


    $parent_uuid = self::get_last_element_uuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    $myfile_uuid = self::get_child_uuid($parent_uuid, $my_file_name);

    if( $myfile_uuid != 'notfound' )
    {

        //if the number of version in the database is greater than MAX_VERSIONAMOUT_FOR_FILE, remove the version with lowest number
        //MAX_VERSIONAMOUT_FOR_FILE = 10
        if(self::get_number_of_versions_present($myfile_uuid) > 10)
        {
            $lowest_version_uuid = self::get_lowest_version_uuid($myfile_uuid);

            self::do_del('version', $lowest_version_uuid);

            $uuid_toberemoved = $lowest_version_uuid; //if it fails, no data is kept in the storage-db, so the user cannot reach it and have more than 10 versions
        }

        self::do_ins('version', [$my_file_version_uuid, $size, $myfile_uuid]);

    }
    else
    {
        //file not present, I need to create a whole new file in file table
        $newfile_uuid = util::uuid_v4();

        self::do_ins_in_file($parent_uuid, [$newfile_uuid, $my_file_name, $user, FALSE]);
        self::do_ins('version', [$my_file_version_uuid, $size, $newfile_uuid]);
    }

    return $uuid_toberemoved; //empty string or uuid

}


static function move_element($user, $path, $name, $newpath, $newname)
{
    if(($name==$newname) AND ($path==$newpath))
        return; //no business here to be done...



    //maybe all these controls are too much....
    if(!util::is_file_name($name) OR !util::is_file_name($newname))
        throw new InvalidArgumentException();

    if(!util::is_uuid($user))
        throw new InvalidArgumentException();

    $path = util::pathify($path);
    $newpath = util::pathify($newpath);
    //-------




    //uuid of the file to be renamed and/or moved
    $myfile_uuid = self::get_last_element_uuid($user, $path.'/'.$name);

    //first of all, check whether an element with the same name already exists in the new path or the element to move doesn't exist
    if( ( self::get_last_element_uuid($user, $newpath.'/'.$newname) != 'notfound' ) OR ($myfile_uuid == 'notfound') )
        throw new InvalidArgumentException();

    try
    {
        self::$conn->beginTransaction();

        //renaming is required
        if($name!=$newname)
        {
            self::$conn->exec("UPDATE file SET file_name = '".$newname."' WHERE uuid = '".$myfile_uuid."';");
        }

        //moving is required
        if($path!=$newpath)
        {
            //getting the uuid of the future parent
            $future_parent_uuid = self::get_last_element_uuid($user, $newpath);

            self::$conn->exec("UPDATE has_parent SET uuid_parent = '".$future_parent_uuid."' WHERE uuid_child = '".$myfile_uuid."';");
        }
        self::$conn->commit();

    }
    catch(PDOException $e)
    {
        self::$conn->rollBack();
        throw new DbException($e->getMessage());
    }

}


/*
* unix-like approach: renaming the element is just moving it in the same directory he's in with a different name
*/
static function rename_element($user, $path, $name, $newname)
{
    return self::move_element($user, $path, $name, $path, $newname);
}




//it doesn't contain the beginTransaction and the commit, so it's more versatile
//0 is interpreted as maximum version number
static function remove_version($file_uuid, int $version_number, &$stack)
{
    $v_uuid;

    if($version_number===0)
    {
        $v_uuid = self::get_highest_version_uuid($file_uuid);
        self::$conn->exec("DELETE FROM version WHERE uuid = '".$v_uuid."';");

    }
    else
    {
        $v_uuid = self::get_this_version_uuid($version_number, $file_uuid);
        self::$conn->exec("DELETE FROM version WHERE uuid = '".$v_uuid."';");
    }

    array_push($stack, $v_uuid);
}

static function remove_all_version_($file_uuid)
{
    self::$conn->exec("DELETE FROM version WHERE uuid_file = '".$file_uuid."';");
}


static function remove_rec_element($user, $myelement_uuid, $version, &$stack)
{

    //base cases
    if(is_int($version)) //i'm dealing with one version... neat!
    {
        return self::remove_version($myelement_uuid, $version, &$stack);
    }

    if(!self::get_if_is_dir($myelement_uuid)) //i'm dealing with a file with one or more versions... I need to remove them all!
    {
        foreach(self::get_all_versions_uuid($myelement_uuid) as $v)
        {
            array_push($stack, $v);
        }
        return self::remove_all_versions($myelement_uuid);
    }
    //----


    //not a base case, I'm dealing with a directory
    $children_uuid = self::get_children_uuid($myelement_uuid);

    if(!($children_uuid == 'nochildren'))
        foreach($children_uuid as $child_uuid)
            self::remove_rec_element($user, $child_uuid, null, &$stack);

    //first I remove all the children of the directory and then the parent directory... so no children without parent are ever present
    self::$conn->exec("DELETE FROM file WHERE uuid = '".$myelement_uuid"';"); //finally removing this directory


}


/*
* The function to delete an element in database that is accessible outside this class
* It returns the array with the uuid of the versions which correspond to a name of a physical file in the persistent, that will be removed outside
*/
static function remove_element($user, $path, $name, $version)
{
    try
    {
        self::$conn->beginTransaction();

        $stack_of_uuid = array();
        self::remove_rec_element($user,  self::get_last_element_uuid($user, $path.'/'.$name), $version, &$stack_of_uuid); //passing by reference the stack

        self::$conn->commit();

        return $stack_of_uuid;
    }
    catch(DbException $e) //catching and throwing back again: if any, I need to rollback all the deleted data in the db
    {
        self::$conn->rollBack();
        throw new DbException($e->getMessage());
    }

}


}



class DbException extends Exception {  }
class InvalidArgumentException extends Exception {  }


//THERE ARE SOME INTERNALLY SPECIALIZED EXCEPTION. THEY ARE CONSIDERED DbException FROM THE OUTSIDE
class ConnectiondbException extends DbException {  }
class DataNotFoundException extends DbException {  }
class DataAlreadyPresentException extends DbException {  }
