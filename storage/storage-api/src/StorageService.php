<?php


/*
* This class contains every (and only) utilities to connect and perform queries to the storage-db
*/
class StorageService
{

    private $conn;

    public function __construct()
    {
        $servername = "localhost";
        $username = "postgres";
        $password = "postgres";

        try
        {
            if($this->conn == NULL)
            {
                $this->conn = new PDO("pgsql:host=".$servername.";port=5432;dbname=postgres;user=".$username.";password=".$password);
                // set the PDO error mode to exception
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
private function doFetch($stmt, $colName)
{

        try
        {
            $stmt->execute();

            $result = $stmt->fetch();

        }
        catch(PDOException $e)
        {
            throw new DataNotFoundException("Data Not Found");
        }

        return $result[$colName];

}

/*
* fetching a single row result from a query to storage-db
* return a json encoded string
*/
private function doFetchAll($stmt)
{

    try
    {
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
    catch(PDOException $e)
    {
        throw new DataNotFoundException("Error from pdo: ". $e->getMessage());
    }

    return $result;
}








//PUBLIC FUNCTION SECTION-------------------------------------------------------




/*
* return the list of the uuid of the file in a certain directory
*/
public function list($user, $path)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    //---------------------------------------------------------END CHECK SECTION



    $last_dir_uuid = $this->getLastElementUuid($user, $path);

    if($last_dir_uuid == '')
        throw new DataNotFoundException();

$sql = <<<SQL
SELECT F.file_name, F.is_dir, F.creation_time
    FROM file AS F INNER JOIN has_parent AS H
        ON F.uuid = H.uuid_child
        WHERE H.uuid_parent = :last_dir_uuid
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':last_dir_uuid', $last_dir_uuid, PDO::PARAM_STR, 36);

    return $this->doFetchAll($stmt);

}

public function getAllVersionsData($user, $path, $fname)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    if( (strpos($fname, '/') === true) OR ($fname == '') OR (strlen($fname) > 255))
        throw new InvalidArgumentException();

    //---------------------------------------------------------END CHECK SECTION



    $myfile_uuid = getLastElementUuid($user, $path.'/'.$fname);

    if($myfile_uuid == '')
        throw new DataNotFoundException();


$sql = <<<SQL
SELECT version_number, creation_time, file_size
    FROM version
    WHERE uuid_file = :myfile_uuid
SQL;

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

        return $this->doFetchAll($stmt);

}


public function getIfIsDirByPath($user, $path)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    //---------------------------------------------------------END CHECK SECTION

    $myfile_uuid = $this->getLastElementUuid($user, $path);

    return $this->getIfIsDir($myfile_uuid);

}



public function getVersionUuid($user, $path, $fileName, $version)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    if( (strpos($fileName, '/') === true) OR ($fileName == '') OR (strlen($fileName) > 255))
        throw new InvalidArgumentException();


    try
    {
        $version = (int) $version;
    }
    catch(Exception $e)
    {
        throw new InvalidArgumentException();
    }


    if($version < 0) //version ==0 : take the highest version, legit value
        throw new InvalidArgumentException();



    //---------------------------------------------------------END CHECK SECTION



    $completeName = $path.'/'.$fileName;

    $myfile_uuid = $this->getLastElementUuid($user, $completeName);

    if($myfile_uuid == '')
        throw new DataNotFoundException();


    //version==0 has the special meaning of: take the latest version
    if($version===0)
    {

        return $this->getHighestVersionUuid($myfile_uuid);

    }
    else
    {
        return $this->getThisVersionUuid($version, $myfile_uuid);

    }

}







/*
* $this->conn
* $user
* $path         path where the new dir will be inserted. If empty, it's the root directory
* $dir_name     a valid file name. This makes impossible to use this private function solely to create the root directory
*/
public function makeDir($user, $path, $dir_name)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);




    if( (strpos($dir_name, '/') === true) OR ($dir_name == '') OR (strlen($dir_name) > 255))
        throw new InvalidArgumentException();


    //---------------------------------------------------------END CHECK SECTION




    $parent_uuid = $this->getLastElementUuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)



    if($parent_uuid == '')
        throw new DataNotFoundException();

    $my_dir_uuid = StorageServiceUtil::uuidV4();


    $this->doInsInFile($parent_uuid, [$my_dir_uuid, $dir_name, $user, TRUE]); //an exception can be thrown if a file with same name is already present in parent
    $this->doIns('has_parent', [$my_dir_uuid, $parent_uuid]);

}


/*
* add a version.
* Returns the uuid of the version uuid equals to the filename to be removed from persistent service if number of versions for that file exceed the limit
*
*/
public function addVersion($user, $path, $my_file_name, $my_file_version_uuid, $size)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    if( (strpos($my_file_name, '/') === true) OR ($my_file_name == '') OR (strlen($my_file_name) > 255))
        throw new InvalidArgumentException();

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $my_file_version_uuid))
        throw new InvalidArgumentException();

    if(!is_numeric($size)) //better not overconstraint it, maybe we will allow bigger size in future
            throw new InvalidArgumentException();

    //---------------------------------------------------------END CHECK SECTION




    $uuid_toberemoved='';



    $parent_uuid = $this->getLastElementUuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)

    if($parent_uuid == '')
        throw new DataNotFoundException();


    $myfile_uuid = $this->getChildUuid($parent_uuid, $my_file_name);

    if( $myfile_uuid != '' )
    {

        //if the number of version in the database is greater than MAX_VERSIONAMOUT_FOR_FILE, remove the version with lowest number
        //MAX_VERSIONAMOUT_FOR_FILE = 10
        if($this->getNumberOfVersionsPresent($myfile_uuid) > 9)
        {
            $lowest_version_uuid = $this->getLowestVersionUuid($myfile_uuid);

            $this->doDel('version', $lowest_version_uuid);

            $uuid_toberemoved = $lowest_version_uuid; //if it fails, no data is kept in the storage-db, so the user cannot reach it and have more than 10 versions
        }

        $this->doIns('version', [$my_file_version_uuid, $size, $myfile_uuid]);

    }
    else
    {
        //file not present, I need to create a whole new file in file table
        $newfile_uuid = StorageServiceUtil::uuidV4();

        $this->doInsInFile($parent_uuid, [$newfile_uuid, $my_file_name, $user, FALSE]);
        $this->doIns('version', [$my_file_version_uuid, $size, $newfile_uuid]);
        $this->doIns('has_parent', [$newfile_uuid, $parent_uuid]);
    }

    return $uuid_toberemoved; //empty string or uuid

}


public function moveElement($user, $path, $name, $newpath, $newname)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    if( (strpos($name, '/') === true) OR ($name == '') OR (strlen($name) > 255))
        throw new InvalidArgumentException();

    $newpath = StorageServiceUtil::pathify($newpath);

    if( (strpos($newname, '/') === true) OR ($newname == '') OR (strlen($newname) > 255))
        throw new InvalidArgumentException();

    //---------------------------------------------------------END CHECK SECTION



    if(($name==$newname) AND ($path==$newpath))
        return; //no business here to be done...




    //uuid of the file to be renamed and/or moved
    $myfile_uuid = $this->getLastElementUuid($user, $path.'/'.$name);

    if($myfile_uuid == '')
        throw new DataNotFoundException();

    //first of all, check whether an element with the same name already exists in the new path or the element to move doesn't exist
    if( ( $this->getLastElementUuid($user, $newpath.'/'.$newname) != '' ) )
        throw new InvalidArgumentException();

    try
    {
        $this->conn->beginTransaction();

        //renaming is required
        if($name!=$newname)
        {

            $stmt = $this->conn->prepare("UPDATE file SET file_name = :newname WHERE uuid = :myfile_uuid");

            $stmt->bindParam(':newname', $newname, PDO::PARAM_STR, 255);
            $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

            $stmt->execute();
        }

        //moving is required
        if($path!=$newpath)
        {
            //getting the uuid of the future parent
            $future_parent_uuid = $this->getLastElementUuid($user, $newpath);

            if($future_parent_uuid == '') //the future parent directory doesn't exist
                throw new InvalidArgumentException();


            $stmt = $this->conn->prepare("UPDATE has_parent SET uuid_parent = :future_parent_uuid WHERE uuid_child = :myfile_uuid");

            $stmt->bindParam(':future_parent_uuid', $future_parent_uuid, PDO::PARAM_STR, 36);
            $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

            $stmt->execute();
        }
        $this->conn->commit();

    }
    catch(PDOException $e)
    {
        $this->conn->rollBack();
        throw new DbException($e->getMessage());
    }

}


/*
* unix-like approach: renaming the element is just moving it in the same directory he's in with a different name
*/
public function renameElement($user, $path, $name, $newname)
{
    //no checks, moveElement is a public function too

    return $this->moveElement($user, $path, $name, $path, $newname);

}



/*
* The function to delete an element in database that is accessible outside this class
* It returns the array with the uuid of the versions which correspond to a name of a physical file in the persistent, that will be removed outside
*/
public function removeElement($user, $path, $name, $version)
{

    //CHECK SECTION-------------------------------------------------------------

    if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user))
        throw new InvalidArgumentException();

    $path = StorageServiceUtil::pathify($path);

    if( (strpos($name, '/') === true) OR ($name == '') OR (strlen($name) > 255))
        throw new InvalidArgumentException();



    try
    {
        $version = (int) $version;
    }
    catch(Exception $e)
    {
        throw new InvalidArgumentException();
    }


    if($version < 0) //version ==0 : take the highest version, legit value
        throw new InvalidArgumentException();

    //---------------------------------------------------------END CHECK SECTION



    try
    {
        $this->conn->beginTransaction();

        $stack_of_uuid = array();

        $elemUuid = $this->getLastElementUuid($user, $path.'/'.$name);

        if($elemUuid == '')
            return; //No Exception, deleting a file that does not exist is ok


        $this->removeRecElement($user, $elemUuid, $version, $stack_of_uuid); //passing by reference the stack

        $this->conn->commit();

        return $stack_of_uuid;
    }
    catch(DbException $e) //catching and throwing back again: if any, I need to rollback all the deleted data in the db
    {
        $this->conn->rollBack();
        throw new DbException($e->getMessage());
    }

}






//-------------------------------------------------------END OF PUBLIC FUNCTIONS











/*
* scan until last file or folder uuid is found
* return an uuid in a string
+ base case: if an empty path is in input it will return the root uuid
*/
private function getLastElementUuid($user, $path)
{

        //extract the token from the path
        $parts = array_filter(explode('/', $path), 'strlen'); //tested: it DOESN"T include empty strings as array elements nor / ... surely legit filenames


        //1: retrieve the root uuid for the given user
        $parent_uuid = $this->getRootUuid($user);

        foreach($parts as $t)
        {

            $parent_uuid = $this->getChildUuid($parent_uuid, $t);



            if( $parent_uuid == '')
                return '';



        }

        $last_element = $parent_uuid; //just for clarity..

        return $last_element;


}



private function getRootUuid($user)
{

    //usefully return the empty string if no root for the user was found
$sql = <<<SQL
    SELECT uuid
        FROM file
        WHERE user_uuid = :user AND file_name = :root_symbol
SQL;



    $stmt = $this->conn->prepare($sql);

    $root_symbol = '/';

    $stmt->bindParam(':user', $user, PDO::PARAM_STR, 36);
    $stmt->bindParam(':root_symbol', $root_symbol, PDO::PARAM_STR);

    $usr_root = $this->doFetch($stmt, "uuid");



    //the user has no root folder, let's create it
    if ($usr_root == "")
    {
        $usr_root=StorageServiceUtil::uuidV4();

        //maybe using a transaction will be better?
        $this->doInsInFile(NULL, [$usr_root, "/", $user, TRUE]);
        $this->doIns('has_parent', [$usr_root, NULL]);

    }

    return $usr_root;

}





private function getChildUuid($parent_uuid, $fname)
{

    //get the child"s uuid with name equals to $fname

$sql = <<<SQL
    SELECT F.uuid
        FROM file AS F INNER JOIN has_parent AS H
            ON F.uuid = H.uuid_child
            WHERE H.uuid_parent = :parent_uuid AND F.file_name = :fname
SQL;


    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':parent_uuid', $parent_uuid, PDO::PARAM_STR, 36);
    $stmt->bindParam(':fname', $fname, PDO::PARAM_STR, 255);

    return $this->doFetch($stmt, "uuid");
}



private function getChildrenUuid($parent_uuid)
{
    if(!$this->getIfIsDir($parent_uuid))
        throw new InvalidArgumentException();

$sql = <<<SQL
    SELECT F.uuid
        FROM file AS F INNER JOIN has_parent AS H
            ON F.uuid = H.uuid_child
        WHERE H.uuid_parent = :parent_uuid
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':parent_uuid', $parent_uuid, PDO::PARAM_STR, 36);

    return $this->doFetchAll($stmt);
}





private function getAllVersionsUuid($myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
    WHERE uuid_file = :myfile_uuid
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return $this->doFetchAll($stmt);

}



private function getHighestVersionUuid($myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
    WHERE uuid_file = :myfile_uuid AND version_number = (
        SELECT MAX(V2.version_number)
            FROM version AS V2
            WHERE V2.uuid_file = :myfile_uuid )
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return $this->doFetch($stmt, "uuid");
}

private function getLowestVersionUuid($myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
    WHERE uuid_file = :myfile_uuid AND version_number = (
        SELECT MIN(V2.version_number)
            FROM version AS V2
            WHERE V2.uuid_file = :myfile_uuid )
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return $this->doFetch($stmt, "uuid");
}

private function getNumberOfVersionsPresent($myfile_uuid)
{
$sql = <<<SQL
SELECT COUNT(*) FROM (
    SELECT uuid
        FROM version
        WHERE uuid_file = :myfile_uuid
) tableAlias
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return $this->doFetch($stmt, 'count');
}

private function getThisVersionUuid($version, $myfile_uuid)
{
$sql = <<<SQL
SELECT uuid
    FROM version
    WHERE uuid_file = :myfile_uuid AND version_number = :version
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
    $stmt->bindParam(':version_number', $myfile_uuid, PDO::PARAM_INT);

    return $this->doFetch($stmt, "uuid");
}

/*
* this function receives as input a filename
* returns if it is a directory (true) or a file (false)
*/
private function getIfIsDir($myfile_uuid)
{
$sql = <<<SQL
SELECT is_dir
    FROM file
    WHERE uuid = :myfile_uuid
SQL;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);

    return $this->doFetch($stmt, "is_dir");
}










/*
* $this->conn         is the connection (hopefully opened) to communicate with storage-db
* $parent_uuid  uuid of the parent (NULL if I want to insert root)
* $arr          the array contains the ordered value to be inserted (uuid, file_name, user_uuid, is_dir)
*
* Keep dinstinct this insertion from the others, due to additional existence check and so different input parameters set
*/
private function doInsInFile($parent_uuid, $arr)
{
    if($this->getChildUuid($parent_uuid, $arr[1]) != '')
        throw new IllegalArgumentException(); //cannot insert a file if it's already present a file in the same directory with the same name

    $in_file = "INSERT INTO file (uuid, file_name, user_uuid, is_dir) VALUES (:uuid, :file_name, :user_uuid, :is_dir)";

    $stmt= $this->conn->prepare($in_file);

    $stmt->bindParam(':uuid', $arr[0], PDO::PARAM_STR, 36);
    $stmt->bindParam(':file_name',  $arr[1], PDO::PARAM_STR, 255);
    $stmt->bindParam(':user_uuid',  $arr[2], PDO::PARAM_STR, 36);
    $stmt->bindParam(':is_dir',  $arr[3], PDO::PARAM_BOOL);

    $stmt->execute();
}


/*
* This private function do the insertion into a predefined table (different from file table) in the storage-db
* $table    specify the table where to do the insertion
* $arr      the array contains the ordered value to be inserted.
*/
private function doIns($table, $arr)
{

    $in_has_parent = "INSERT INTO has_parent (uuid_child, uuid_parent) VALUES (:uuid_child, :uuid_parent)";
    $in_version = "INSERT INTO version (uuid, file_size, uuid_file) VALUES (:uuid, :file_size, :uuid_file)";


    $sql=''; // no exception is thrown, it's just an internal error that should never happen


    if($table == 'has_parent')
    {
        $stmt= $this->conn->prepare($in_has_parent);

        $stmt->bindParam(':uuid_child', $arr[0], PDO::PARAM_STR, 36);
        $stmt->bindParam(':uuid_parent',  $arr[1], PDO::PARAM_STR, 36);

        $sql=$table;

    }
    else if($table == 'version')
    {
        $stmt= $this->conn->prepare($in_version);

        $stmt->bindParam(':uuid', $arr[0], PDO::PARAM_STR, 36);
        $stmt->bindParam(':file_size',  $arr[1], PDO::PARAM_INT);
        $stmt->bindParam(':uuid_file',  $arr[2], PDO::PARAM_STR, 36);

        $sql=$table;
    }

    if($sql == '')
    {
        return;
    }

    $stmt->execute();
}



private function doDel($table, $uuid)
{
    $del_file = "DELETE FROM file WHERE uuid = :uuid";
    $del_version = "DELETE FROM version WHERE uuid = :uuid";
    $del_has_parent = "DELETE FROM has_parent WHERE uuid_child = :uuid";

    $sql=''; // no exception is thrown if it remains empty, it's just an internal error that should never happen

    if($table == 'file')
        $sql=$del_file;

    if($table == 'has_parent')
        $sql=$del_has_parent;

    if($table == 'version')
        $sql=$del_version;

    if($sql == '')
        return;

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR, 36);

    $stmt->execute();


}









//it doesn't contain the beginTransaction and the commit, so it's more versatile
//0 is interpreted as maximum version number
private function removeVersion($file_uuid, int $version_number, & $stack)
{
    $v_uuid=NULL;


    if($this->getNumberOfVersionsPresent($file_uuid) == 1) //only one version and soon it will be removed
    {
        $this->doDel('file',$file_uuid);
        $this->doDel('has_parent',$file_uuid);
    }


    if($version_number===0)
    {
        $v_uuid = $this->getHighestVersionUuid($file_uuid);

        $this->doDel('version',$v_uuid);

    }
    else
    {
        $v_uuid = $this->getThisVersionUuid($version_number, $file_uuid);


        $stmt = $this->conn->prepare("DELETE FROM version WHERE uuid = :v_uuid");

        $stmt->bindParam(':v_uuid', $v_uuid, PDO::PARAM_STR, 36);

        $stmt->execute();
    }


    array_push($stack, $v_uuid);
}

private function removeAllVersions($file_uuid)
{
    $stmt = $this->conn->prepare("DELETE FROM version WHERE uuid_file = :file_uuid");

    $stmt->bindParam(':file_uuid', $file_uuid, PDO::PARAM_STR, 36);

    $stmt->execute();

    $this->doDel('has_parent',$file_uuid);
    $this->doDel('file',$file_uuid);
}


private function removeRecElement($user, $myelement_uuid, $version, & $stack)
{

    //base cases
    if(is_int($version)) //i'm dealing with one version... neat!
    {
        return $this->removeVersion($myelement_uuid, $version, $stack);
    }

    if(!$this->getIfIsDir($myelement_uuid)) //i'm dealing with a file with one or more versions... I need to remove them all!
    {
        foreach($this->getAllVersionsUuid($myelement_uuid) as $v)
        {
            array_push($stack, $v);
        }

        return $this->removeAllVersions($myelement_uuid);
    }
    //----


    //not a base case, I'm dealing with a directory
    $children_uuid = $this->getChildrenUuid($myelement_uuid);

    if(!($children_uuid == ''))
        foreach($children_uuid as $child_uuid)
            $this->removeRecElement($user, $child_uuid, null, $stack);

    //first I remove all the children of the directory and then the parent directory... so no children without parent are ever present

    $this->doDel('file',$myelement_uuid);
    $this->doDel('has_parent',$myelement_uuid);


    return;
}



}//-----------------------------------------------------------------END OF CLASS



class DbException extends Exception {  }
//class InvalidArgumentException extends Exception {  }
class DataNotFoundException extends DbException {  }

//THERE ARE SOME INTERNALLY SPECIALIZED EXCEPTION. THEY ARE CONSIDERED DbException FROM THE OUTSIDE
class ConnectiondbException extends DbException {  }

class DataAlreadyPresentException extends DbException {  }
