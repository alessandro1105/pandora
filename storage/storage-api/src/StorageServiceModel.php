<?php


namespace App\Components\Storage\Model;

use \InvalidArgumentException;

use App\Components\Storage\Util\StorageServiceUtil as util;


/*
* This class contains every (and only) utilities to connect and perform queries to the storage-db
*/
class StorageServiceModel
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
            self::$conn = new PDO("mysql:host=$servername;dbname=storageDb", $username, $password);
            // set the PDO error mode to exception
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

    }

    catch(PDOException $e)
    {
        throw new ConnectiondbException();
    }

    return self::$conn;

}

/*
* fetching a single row result from a query to storage-db
* return a string with the result
*/
private static function doFetch($stmt, $colName)
{

        try
        {

            $result = $stmt->fetch();

            if(! $result )
            {
                throw new InvalidArgumentException("Data Not Found");
            }
        }
        catch(PDOException $e)
        {
            throw new InvalidArgumentException("Data Not Found");
        }

        return $result[$colName];

}

/*
* fetching a single row result from a query to storage-db
* return a json encoded string
*/
private static function doFetchAll($stmt)
{

    try
    {

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(! $result ) //if null, empty or false
        {
            throw new InvalidArgumentException("Could not get data: " . mysql_error());
        }

        return $result;
    }
    catch(PDOException $e)
    {
        throw new InvalidArgumentException("Error from pdo: ". $e->getMessage());
    }


}








//6--QUERY FUNCTIONS SECTION



/*
* return the list of the uuid of the file in a certain directory
*/
static function list($user, $path)
{

    $last_dir_uuid = self::getLastElementUuid($user, $path);

$sql = <<<SQL
SELECT F.file_name, F.is_dir, F.creation_time
    FROM file AS F INNER JOIN has_parent AS H
        ON F.uuid = H.uuid_child
        WHERE H.uuid_parent = :last_dir_uuid
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':last_dir_uuid', $last_dir_uuid, PDO::PARAM_STR, 36);

    return self::doFetchAll($stmt);

}

/*
* scan until last file or folder uuid is found
* return an uuid in a string
+ base case: if an empty path is in input it will return the root uuid
*/
static function getLastElementUuid($user, $path)
{

        //extract the token from the path
        $parts = array_filter(explode('/', $path), 'strlen'); //tested: it DOESN"T include empty strings as array elements nor / ... surely legit filenames

        //1: retrieve the root uuid for the given user
        $parent_uuid = self::getRootUuid($user);

        foreach($parts as $t)
        {
            $parent_uuid = self::getChildUuid($parent_uuid, $t);

            if( $parent_uuid == 'notfound')
                return 'notfound';
        }

        $last_element = $parent_uuid; //just for clarity..

        return $last_element;


}


static function getRootUuid($user)
{

    //usefully return the empty string if no root for the user was found
$sql = <<<SQL
SELECT IFNULL ((
    SELECT uuid
        FROM file
            WHERE user_uuid = :user AND file_name = '/'
), 'noroot');
SQL;


    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':user', $user, PDO::PARAM_STR, 36);

    $usr_root = self::doFetch($stmt, "uuid");

    //the user has no root folder, let's create it
    if ($usr_root == "noroot")
        return self::makeRootDir($user);

}

static function getChildUuid($parent_uuid, $fname)
{

    //get the child"s uuid with name equals to $fname

$sql = <<<SQL
SELECT IFNULL ((
    SELECT F.uuid
        FROM file AS F INNER JOIN has_parent AS H
            ON F.uuid = H.uuid_child
            WHERE H.uuid_parent = :parent_uuid AND F.file_name = :fname ), 'notfound')
SQL;


    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':parent_uuid', $parent_uuid, PDO::PARAM_STR, 36);
    $stmt->bindParam(':fname', $fname, PDO::PARAM_STR, 255);

    return self::doFetch($stmt, "uuid");
}

static function getChildrenUuid($parent_uuid)
{
    if(!self::getIfIsDir($parent_uuid))
        throw new InvalidArgumentException();

$sql = <<<SQL
SELECT IFNULL ((
    SELECT F.uuid
        FROM file AS F INNER JOIN has_parent AS H
            ON F.uuid = H.uuid_child
            WHERE H.uuid_parent = :parent_uuid ), 'nochildren')
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':parent_uuid', $parent_uuid, PDO::PARAM_STR, 36);

    return self::doFetchAll($stmt);
}






static function getVersionUuid($user, $path, $fileName, $version)
{

    $completeName = $path.'/'.$fileName;

    $myfile_uuid = self::getLastElementUuid($user, $completeName);

    //version==0 has the special meaning of: take the latest version
    if($version===0)
    {

        return self::getHighestVersionUuid($myfile_uuid);

    }
    else
    {
        return self::getThisVersionUuid($version, $myfile_uuid);

    }

}

static function getAllVersionsUuid($myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
        WHERE uuid_file = :myfile_uuid
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return self::doFetchAll($stmt);

}

static function getHighestVersionUuid($myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
        WHERE uuid_file = :myfile_uuid AND version_number = (
                SELECT MAX(V2.version_number)
                    FROM version AS V2
                        WHERE V2.uuid_file = :myfile_uuid )
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return self::doFetch($stmt, "uuid");
}

static function getLowestVersionUuid($myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
        WHERE uuid_file = :myfile_uuid AND version_number = (
            SELECT MIN(V2.version_number)
                FROM version AS V2
                    WHERE V2.uuid_file = :myfile_uuid )
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return self::doFetch($stmt, "uuid");
}

static function getNumberOfVersionsPresent($myfile_uuid)
{
$sql = <<<SQL
SELECT COUNT(*) FROM (
    SELECT uuid
        FROM version
            WHERE uuid_file = :myfile_uuid
) tableAlias
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return self::doFetch($stmt, 'COUNT(*)');
}

static function getThisVersionUuid($version, $myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
        WHERE uuid_file = :myfile_uuid AND version_number = :version
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
    $stmt->bindParam(':version_number', $myfile_uuid, PDO::PARAM_INT);

    return self::doFetch($stmt, "uuid");
}

/*
* this static function receives as input a filename
* returns if it is a directory (true) or a file (false)
*/
static function getIfIsDir($myfile_uuid)
{
$sql = <<<SQL
SELECT is_dir
    FROM file
        WHERE uuid = :myfile_uuid
SQL;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return self::doFetch($stmt, "is_dir");
}










/*
* self::$conn         is the connection (hopefully opened) to communicate with storage-db
* $parent_uuid  uuid of the parent (NULL if I want to insert root)
* $arr          the array contains the ordered value to be inserted (uuid, file_name, user_uuid, is_dir)
*
* Keep dinstinct this insertion from the others, due to additional existence check and so different input parameters set
*/
static function doInsInFile($parent_uuid, $arr)
{
    if(self::getChildUuid($parent_uuid, $arr[1]) != 'notfound')
        throw new IllegalArgumentException(); //cannot insert a file if it's already present a file in the same directory with the same name

    $in_file = "INSERT INTO file (uuid, file_name, user_uuid, is_dir) VALUES (:uuid, :file_name, :user_uuid, :is_dir)";

    $stmt= self::getConnection()->prepare($in_file);

    $stmt->bindParam(':uuid', $arr[0], PDO::PARAM_STR, 36);
    $stmt->bindParam(':file_name',  $arr[1], PDO::PARAM_STR, 255);
    $stmt->bindParam(':user_uuid',  $arr[2], PDO::PARAM_STR, 36);
    $stmt->bindParam(':is_dir',  $arr[3], PDO::PARAM_BOOL);

    $stmt->execute();

}


/*
* This static function do the insertion into a predefined table (different from file table) in the storage-db
* $table    specify the table where to do the insertion
* $arr      the array contains the ordered value to be inserted.
*/
static function doIns($table, $arr)
{
    $in_has_parent = "INSERT INTO has_parent (uuid_child, uuid_parent) VALUES (:uuid_child, :uuid_parent)";
    $in_version = "INSERT INTO version (uuid, file_size, uuid_file) VALUES (:uuid, :file_size, :uuid_file)";


    $sql=''; // no exception is thrown, it's just an internal error that should never happen


    if($table == 'has_parent')
    {
        $stmt= self::getConnection()->prepare($in_has_parent);

        $stmt->bindParam(':uuid_child', $arr[0], PDO::PARAM_STR, 36);
        $stmt->bindParam(':uuid_parent',  $arr[1], PDO::PARAM_STR, 36);

    }
    else
        if($table == 'version')
        {
            $stmt= self::getConnection()->prepare($in_version);

            $stmt->bindParam(':uuid', $arr[0], PDO::PARAM_STR, 36);
            $stmt->bindParam(':file_size',  $arr[1], PDO::PARAM_INT);
            $stmt->bindParam(':uuid_file',  $arr[2], PDO::PARAM_STR, 36);

        }

    if($sql == '')
        return;

    $stmt->execute();
}



static function doDel($table, $uuid)
{
    $del_file = "DELETE FROM file WHERE uuid = :uuid";
    $del_version = "DELETE FROM version WHERE uuid = :uuid";
    $del_has_parent = "DELETE FROM has_parent WHERE uuid_child = :uuid";

    $sql=''; // no exception is thrown if it remains empty, it's just an internal error that should never happen

    if($table == 'file')
        $sql=$del_file;

    if($table == 'has_parent')
        $sql=$del_has_parent;
    else
        if($table == 'version')
            $sql=$del_version;

    if($sql == '')
        return;

    $stmt = self::getConnection()->prepare($sql);

    $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR, 36);

    $stmt->execute();


}


//It will return the uuid of the root if correctly created
static function makeRootDir($user)
{
    $usr_root=util::uuidV4();

    //maybe using a transaction will be better?
    self::doInsInFile(NULL, [$usr_root, "/", $user, TRUE]);
    self::doIns('has_parent', [$usr_root, NULL]);

    return $usr_root;
}


/*
* self::$conn
* $user
* $path         path where the new dir will be inserted. If empty, it's the root directory
* $dir_name     a valid file name. This makes impossible to use this static function solely to create the root directory
*/
static function makeDir($user, $path, $dir_name)
{
    if(!util::isFileName($dir_name))
        throw new InvalidArgumentException();


    $parent_uuid = self::getLastElementUuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    $my_dir_uuid = util::uuidV4();


    self::doInsInFile($parent_uuid, [$my_dir_uuid, $dir_name, $user, TRUE]); //an exception can be thrown if a file with same name is already present in parent
    self::doIns('has_parent', [$my_dir_uuid, $parent_uuid]);

}



/*
* add a version.
* Returns the uuid of the version uuid equals to the filename to be removed from persistent service if number of versions for that file exceed the limit
*
*/
static function addVersion($user, $path, $my_file_name, $my_file_version_uuid, $size)
{
    $uuid_toberemoved='';

    if(!util::isFileName($my_file_name))
        throw new InvalidArgumentException();


    $parent_uuid = self::getLastElementUuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    $myfile_uuid = self::getChildUuid($parent_uuid, $my_file_name);

    if( $myfile_uuid != 'notfound' )
    {

        //if the number of version in the database is greater than MAX_VERSIONAMOUT_FOR_FILE, remove the version with lowest number
        //MAX_VERSIONAMOUT_FOR_FILE = 10
        if(self::getNumberOfVersionsPresent($myfile_uuid) > 10)
        {
            $lowest_version_uuid = self::getLowestVersionUuid($myfile_uuid);

            self::doDel('version', $lowest_version_uuid);

            $uuid_toberemoved = $lowest_version_uuid; //if it fails, no data is kept in the storage-db, so the user cannot reach it and have more than 10 versions
        }

        self::doIns('version', [$my_file_version_uuid, $size, $myfile_uuid]);

    }
    else
    {
        //file not present, I need to create a whole new file in file table
        $newfile_uuid = util::uuidV4();

        self::doInsInFile($parent_uuid, [$newfile_uuid, $my_file_name, $user, FALSE]);
        self::doIns('version', [$my_file_version_uuid, $size, $newfile_uuid]);
    }

    return $uuid_toberemoved; //empty string or uuid

}


static function moveElement($user, $path, $name, $newpath, $newname)
{
    if(($name==$newname) AND ($path==$newpath))
        return; //no business here to be done...



    //maybe all these controls are too much....
    if(!util::isFileName($name) OR !util::isFileName($newname))
        throw new InvalidArgumentException();

    if(!util::isUuid($user))
        throw new InvalidArgumentException();

    $path = util::pathify($path);
    $newpath = util::pathify($newpath);
    //-------




    //uuid of the file to be renamed and/or moved
    $myfile_uuid = self::getLastElementUuid($user, $path.'/'.$name);

    //first of all, check whether an element with the same name already exists in the new path or the element to move doesn't exist
    if( ( self::getLastElementUuid($user, $newpath.'/'.$newname) != 'notfound' ) OR ($myfile_uuid == 'notfound') )
        throw new InvalidArgumentException();

    try
    {
        self::getConnection()->beginTransaction();

        //renaming is required
        if($name!=$newname)
        {

            $stmt = self::getConnection()->prepare("UPDATE file SET file_name = :newname WHERE uuid = :myfile_uuid");

            $stmt->bindParam(':newname', $newname, PDO::PARAM_STR, 255);
            $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

            $stmt->execute();
        }

        //moving is required
        if($path!=$newpath)
        {
            //getting the uuid of the future parent
            $future_parent_uuid = self::getLastElementUuid($user, $newpath);



            $stmt = self::getConnection()->prepare("UPDATE has_parent SET uuid_parent = :future_parent_uuid WHERE uuid_child = :myfile_uuid");

            $stmt->bindParam(':future_parent_uuid', $future_parent_uuid, PDO::PARAM_STR, 36);
            $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

            $stmt->execute();
        }
        self::getConnection()->commit();

    }
    catch(PDOException $e)
    {
        self::getConnection()->rollBack();
        throw new DbException($e->getMessage());
    }

}


/*
* unix-like approach: renaming the element is just moving it in the same directory he's in with a different name
*/
static function renameElement($user, $path, $name, $newname)
{
    return self::moveElement($user, $path, $name, $path, $newname);
}




//it doesn't contain the beginTransaction and the commit, so it's more versatile
//0 is interpreted as maximum version number
static function removeVersion($file_uuid, int $version_number, &$stack)
{
    $v_uuid;

    if($version_number===0)
    {
        $v_uuid = self::getHighestVersionUuid($file_uuid);


        $stmt = self::getConnection()->prepare("DELETE FROM version WHERE uuid = :v_uuid");

        $stmt->bindParam(':v_uuid', $v_uuid, PDO::PARAM_STR, 36);

        $stmt->execute();

    }
    else
    {
        $v_uuid = self::getThisVersionUuid($version_number, $file_uuid);


        $stmt = self::getConnection()->prepare("DELETE FROM version WHERE uuid = :v_uuid");

        $stmt->bindParam(':v_uuid', $v_uuid, PDO::PARAM_STR, 36);

        $stmt->execute();
    }

    array_push($stack, $v_uuid);
}

static function removeAllVersions($file_uuid)
{
    $stmt = self::getConnection()->prepare("DELETE FROM version WHERE uuid_file = :file_uuid");

    $stmt->bindParam(':file_uuid', $file_uuid, PDO::PARAM_STR, 36);

    $stmt->execute();
}


static function removeRecElement($user, $myelement_uuid, $version, &$stack)
{

    //base cases
    if(is_int($version)) //i'm dealing with one version... neat!
    {
        return self::removeVersion($myelement_uuid, $version, &$stack);
    }

    if(!self::getIfIsDir($myelement_uuid)) //i'm dealing with a file with one or more versions... I need to remove them all!
    {
        foreach(self::getAllVersionsUuid($myelement_uuid) as $v)
        {
            array_push($stack, $v);
        }
        return self::removeAllVersions($myelement_uuid);
    }
    //----


    //not a base case, I'm dealing with a directory
    $children_uuid = self::getChildrenUuid($myelement_uuid);

    if(!($children_uuid == 'nochildren'))
        foreach($children_uuid as $child_uuid)
            self::removeRecElement($user, $child_uuid, null, &$stack);

    //first I remove all the children of the directory and then the parent directory... so no children without parent are ever present

    $stmt = self::getConnection()->prepare("DELETE FROM file WHERE uuid = :myelement_uuid"); //now remove this directory

    $stmt->bindParam(':myelement_uuid', $myelement_uuid, PDO::PARAM_STR, 36);

    $stmt->execute();


}


/*
* The function to delete an element in database that is accessible outside this class
* It returns the array with the uuid of the versions which correspond to a name of a physical file in the persistent, that will be removed outside
*/
static function removeElement($user, $path, $name, $version)
{
    try
    {
        self::getConnection()->beginTransaction();

        $stack_of_uuid = array();
        self::removeRecElement($user,  self::getLastElementUuid($user, $path.'/'.$name), $version, &$stack_of_uuid); //passing by reference the stack

        self::getConnection()->commit();

        return $stack_of_uuid;
    }
    catch(DbException $e) //catching and throwing back again: if any, I need to rollback all the deleted data in the db
    {
        self::getConnection()->rollBack();
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
