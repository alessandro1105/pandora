<?php

    namespace App\Components\Storage;

    use \InvalidArgumentException;
    use \PDO;

    use \App\Components\Storage\Exceptions\NoSuchFileOrDirectoryException;
    use \App\Components\Storage\Exceptions\FileOrDirectoryAlreadyExistsException;
    use \App\Components\Storage\Exceptions\NotADirectoryException;
    use \App\Components\Storage\Exceptions\NotAFileException;
    
    use \App\Components\Database\Exceptions\DatabaseQueryExecutionException;


    // User class
    class StorageService {

        // Service databaseService
        private $databaseService;


        // Service constructor
        public function __construct($databaseService) {
            
            // Save services
            $this->databaseService = $databaseService;
        }

        /*
        * $this->databaseService
        * $user
        * $path         path where the new dir will be inserted. If empty, it's the root directory
        * $dir_name     a valid file name. This makes impossible to use this function solely to create the root directory
        */
        public function makeDir($user, $path, $name) {
        
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
                throw new InvalidArgumentException('User must be a correct uuid!');
            }

            // Need to check path and $dir_name
            // if (...) {
            //     throw new InvalidArgumentException('Path must be a correct path name!');
            // }

            // if (...) {
            //     throw new InvalidArgumentException('Name must be a correct file name!');
            // }


            $parent = $this->getDirectory($user, $path);

            // Check if the directory alredy exists and it's not a file
            if (!is_null($this->getChildDirectory($name, $parent))) {
                // A file or a directory already exists
                throw new FileOrDirectoryAlreadyExistsException('File with name \'' . $name . '\' already exists in the path \'' . $path . '\'!');
            }

            // The directory can be created
            $directory = self::uuidV4();

$sql = <<<SQL
INSERT INTO file (uuid, file_name, user_uuid, is_dir)
    VALUES (:uuid, :file_name, :user_uuid, TRUE)
SQL;
            
            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid', $directory, PDO::PARAM_STR);
            $stmt->bindParam(':file_name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':user_uuid', $user, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // Create the realation parent => child
$sql = <<<SQL
INSERT INTO has_parent(uuid_child, uuid_parent)
    VALUES (:uuid_child, :uuid_parent)
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid_child', $directory, PDO::PARAM_STR);
            $stmt->bindParam(':uuid_parent', $parent, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // Return true to signal the correct execution
            return true;
        }

        
        // Get all versions of a file as an array
        // If the file doesn't exists it will be returned an empty array
        public function getAllFileVersions($user, $path, $name) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
                throw new InvalidArgumentException('User must be a correct uuid!');
            }

            // Need to check path and $dir_name
            // if (...) {
            //     throw new InvalidArgumentException('Path must be a correct path name!');
            // }

            // if (...) {
            //     throw new InvalidArgumentException('Name must be a correct file name!');
            // }


            $parent = $this->getDirectory($user, $path);

            // Get the file
            $file = $this->getChildFile($name, $parent);

            // If the file doesn't exists
            if (is_null($file)) {
                return []; // File not exists, so empty array

            // If the file is a directory
            } else if ($file === false) {
                throw new NotAFileException('Not a file \'' . $path . '/' . $name . '\'!');
            }

            // Select all versions
$sql = <<<SQL
SELECT *
    FROM version
    WHERE uuid_file = :uuid_file
SQL;
                // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid_file', $file, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // We consider that the query has been executed correctly
            $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return the versions
            return $versions;
        }

        // Get a specific version
        // If version is null get last one
        public function getFileVersion($user, $path, $name, $version = null) {

        }

        // Remove a specific file version
        public function removeFileVersion($version) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $version)) {
                throw new InvalidArgumentException('Version must be a correct uuid!');
            }

$sql = <<<SQL
DELETE
    FROM version
    WHERE uuid = :uuid
SQL;
            
            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid', $version, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // Return true to signal the correct execution
            return true;

        }

        // Add a new file version
        // If the file doesn't exist it will be created
        // The version is automatically inserted as last
        public function addFileVersion($user, $path, $name, $filesize) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
                throw new InvalidArgumentException('User must be a correct uuid!');
            }

            // Need to check path and $dir_name and filesize
            // if (...) {
            //     throw new InvalidArgumentException('Path must be a correct path name!');
            // }

            // if (...) {
            //     throw new InvalidArgumentException('Name must be a correct file name!');
            // }

            $parent = $this->getDirectory($user, $path);

            // Get the file
            $file = $this->getChildFile($name, $parent);

            // If the file doesn't exists
            if (is_null($file)) {
                $file = $this->addFile($name, $parent);

            // If the file is a directory
            } else if ($file === false) {
                throw new NotAFileException('Not a file \'' . $path . '/' . $name . '\'!');
            }

            // Insert the new version
            $version = self::uuidV4();

            // Select max version number
$sql = <<<SQL
SELECT MAX(version_number) AS version_number
    FROM version
    WHERE uuid_file = :uuid_file
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid_file', $file, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            $raw = $stmt->fetch(PDO::FETCH_ASSOC);

            $version_number = $raw['version_number'] ? (int) $raw['version_number'] + 1 : 1;

$sql = <<<SQL
INSERT INTO version (uuid, version_number, creation_time, file_size, uuid_file, uploaded)
    VALUES (:uuid, :version_number, NOW(), :file_size, :uuid_file, FALSE)
SQL;
            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid', $version, PDO::PARAM_STR);
            $stmt->bindParam(':version_number', $version_number, PDO::PARAM_INT);
            $stmt->bindParam(':file_size', $filesize, PDO::PARAM_INT);
            $stmt->bindParam(':uuid_file', $file, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // Return true to signal the correct execution
            return $version;
        }

        // Set that a file has been currenctly uploaded so it is available for download
        public function setFileVersionUploaded($version) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $version)) {
                throw new InvalidArgumentException('File version is not a valid uuid');
            }

$sql = <<<SQL
UPDATE version
    SET uploaded = TRUE
    WHERE uuid = :uuid
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid', $version, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // Return true to signal the correct execution
            return true;
        }




        // --- PRIVATE FUNCTIONS ---

        // Obtain directory from path
        private function getDirectory($user, $path) {
            if ($path == '/') {
                return $this->getRoot($user);
            }

            // The path is compsed by atleast a directory
            $pathExploded = explode('/', substr($path, 1)); // Remove the trailing /

            // Current directory is root
            $current = $this->getRoot($user);

            foreach ($pathExploded as $directory) {
                $current = $this->getChildDirectory($directory, $current);

                // Check if the directory has been found
                if (is_null($current)) {
                    throw new NoSuchFileOrDirectoryException('No such file or directory \'' . $path . '\'!');
                
                } else if ($current === false) {
                    throw new NotADirectoryException('Not a directory \'' . $path . '\'!');
                }
            }

            return $current;
        }

        // Obtain the root of a user
        private function getRoot($user) {        
            //usefully return the empty string if no root for the user was found
$sql = <<<SQL
SELECT uuid
    FROM file
    WHERE user_uuid = :user AND file_name = '/' AND is_dir = TRUE
SQL;
            
            // Prepare query
            $stmt = $this->databaseService->prepare($sql);
            
            // Bind query paraments
            $stmt->bindParam(':user', $user, PDO::PARAM_STR);

            // Execute query
            $result = $stmt->execute();

            // If the root exists
            if ($result) {
                $root = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($root === false) {
                    // Create and uuid for the root
                    $root = self::uuidV4();

$sql = <<<SQL
INSERT INTO file (uuid, file_name, user_uuid, is_dir)
    VALUES (:uuid, '/', :user_uuid, TRUE)
SQL;
                
                    // Prepare the query
                    $stmt = $this->databaseService->prepare($sql);
    
                    // Bind query parameters
                    $stmt->bindParam(':uuid', $root, PDO::PARAM_STR);
                    $stmt->bindParam(':user_uuid', $user, PDO::PARAM_STR);
    
                    // Execute the query
                    $stmt->execute();
    
                    return $root;

                } else {
                    return $root['uuid'];
                }

            // The root does not exists, we need to create it
            }

            // Something went wrong with the query
            throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
        }

        // Return null if nothing exists
        // Return false if it's a file
        private function getChildDirectory($name, $parent) {
        
            //get the child"s uuid with name equals to $fname
        
$sql = <<<SQL
SELECT F.uuid, F.is_dir
    FROM file AS F INNER JOIN has_parent AS H
        ON F.uuid = H.uuid_child
    WHERE H.uuid_parent = :parent_uuid AND F.file_name = :file_name
SQL;
            
            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);
            
            // Bind query parameters
            $stmt->bindParam(':parent_uuid', $parent, PDO::PARAM_STR);
            $stmt->bindParam(':file_name', $name, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if ($result) {
                $directory = $stmt->fetch(PDO::FETCH_ASSOC);

                // If there is nothing return null
                if ($directory === false) {
                    return null;
                }

                // If it's a file thow an exception
                if (!$directory['is_dir']) {
                    return false;
                }

                // return uuid
                return $directory['uuid'];
            }

            
        }

        // Return the uuid of the file
        private function getChildFile($name, $parent) {

$sql = <<<SQL
SELECT F.uuid, F.is_dir
    FROM file AS F INNER JOIN has_parent AS H
        ON F.uuid = H.uuid_child
    WHERE H.uuid_parent = :parent_uuid AND F.file_name = :file_name
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);
            
            // Bind query parameters
            $stmt->bindParam(':parent_uuid', $parent, PDO::PARAM_STR);
            $stmt->bindParam(':file_name', $name, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if ($result) {
                $file = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($file['is_dir']) {
                    return false;
                }

                return $file['uuid'];
            }

            return null;
        }

        private function addFile($name, $parent) {
            $file = self::uuidV4();
$sql = <<<SQL
INSERT INTO file (uuid, file_name, user_uuid, is_dir)
    VALUES (:uuid, :file_name, :user_uuid, FALSE)
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid', $file, PDO::PARAM_STR);
            $stmt->bindParam(':file_name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':user_uuid', $parent, PDO::PARAM_STR);

            // Execute
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

             // Create the realation parent => child
$sql = <<<SQL
INSERT INTO has_parent(uuid_child, uuid_parent)
    VALUES (:uuid_child, :uuid_parent)
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid_child', $file, PDO::PARAM_STR);
            $stmt->bindParam(':uuid_parent', $parent, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            return $file;
        }


        // --- HELPER FUNCTIONS ---

        // Create an uuid v4
        private static function uuidV4() {
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
    
    
















//         /*
//         * return the list of the uuid of the file in a certain directory
//         */
//         public function list($user, $path) {        
//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException('User is not a valid uuid');
//             }
        
//             $path = StorageServiceUtil::pathify($path);
        
//             $last_dir_uuid = $this->getLastElementUuid($user, $path);
        
//             if ($last_dir_uuid == '') {
//                 throw new DataNotFoundException();
//             }
        
// $sql = <<<SQL
// SELECT F.file_name, F.is_dir, F.creation_time
//     FROM file AS F INNER JOIN has_parent AS H
//         ON F.uuid = H.uuid_child
//         WHERE H.uuid_parent = :last_dir_uuid
// SQL;
        
        
        
//             $statement = $this->databaseService->prepare($sql);
        
//             $statement->bindParam(':last_dir_uuid', $last_dir_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetchAll($stmt);
        
        
//         }
    

//         public function getAllVersionsData($user, $path, $fname) {

//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $path = StorageServiceUtil::pathify($path);
    
//             if ((strpos($fname, '/') === true) OR ($fname == '') OR (strlen($fname) > 255)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $myfile_uuid = $this->getLastElementUuid($user, $path.'/'.$fname);
    
//             if ($myfile_uuid == '') {
//                 throw new DataNotFoundException();
//             }
    
//             if ($this->getIfIsDir($myfile_uuid)) {
//                 throw new DataNotFoundException();
//             }
    
    
// $sql = <<<SQL
// SELECT version_number, creation_time, file_size
//     FROM version
//     WHERE uuid_file = :myfile_uuid
// SQL;
    
//             $statement = $this->databaseService->prepare($sql);
    
//             $statement->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
    
//             return $this->doFetchAll($statement);
    
//         }
    
    
//         public function getIfIsDirByPath($user, $path) {
    
//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $path = StorageServiceUtil::pathify($path);
    
//             $myfile_uuid = $this->getLastElementUuid($user, $path);
    
//             if ($myfile_uuid == '') {
//                 throw new DataNotFoundException();
//             }    
    
//             return $this->getIfIsDir($myfile_uuid);
//         }
    
//         public function getIfExistsByPath($user, $path) {

//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $path = StorageServiceUtil::pathify($path);
    
//             $myfile_uuid = $this->getLastElementUuid($user, $path);
    
//             if ($myfile_uuid == '') {
//                 return false;
//             }
    
//             return true;
//         }
    
    
//         public function getVersionUuid($user, $path, $fileName, $version) {

//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $path = StorageServiceUtil::pathify($path);
    
//             if ((strpos($fileName, '/') === true) OR ($fileName == '') OR (strlen($fileName) > 255)) {
//                 throw new InvalidArgumentException();
//             }
            
//             //necessary, otherwise the cast from a non numeric string to int will return 0 (=max version)
//             if (!is_numeric($version)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $version = (int) $version;
    
    
//             if ($version < 0) { //version ==0 : take the highest version, legit value
//                 throw new InvalidArgumentException();
//             }
    
    
//             $completeName = $path.'/'.$fileName;
    
//             $myfile_uuid = $this->getLastElementUuid($user, $completeName);
    
//             if ($myfile_uuid == '') {
//                 throw new DataNotFoundException();
//             } 
    
    
//             //version==0 has the special meaning of: take the latest version
//             if ($version===0) {
//                 return $this->getHighestVersionUuid($myfile_uuid);

//             } else {
//                 //the name of the physical file that is present in the persistent
//                 $toPersistent = $this->getThisVersionUuid($version, $myfile_uuid);
    
//                 if ($toPersistent == '') { //illegal version number
//                     throw new DataNotFoundException();
//                 }
    
//                 return $toPersistent;
//             }
    
//         }
    
    
//         /*
//         * add a version.
//         * Returns the uuid of the version uuid equals to the filename to be removed from persistent service if number of versions for that file exceed the limit
//         *
//         */
//         public function addVersion($user, $path, $my_file_name, $my_file_version_uuid, $size){
        
//             //CHECK SECTION-------------------------------------------------------------
        
//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
        
//             $path = StorageServiceUtil::pathify($path);
        
//             if ((strpos($my_file_name, '/') === true) OR ($my_file_name == '') OR (strlen($my_file_name) > 255)) {
//                 throw new InvalidArgumentException();
//             }
        
//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $my_file_version_uuid)) {
//                 throw new InvalidArgumentException();
//             }
        
        
        
//             if(!is_numeric($size)) { //necessary, otherwise the cast from a non numeric string to int will return 0 (=max version)
//                 throw new InvalidArgumentException();
//             }
        
//             $size = (int) $size;
        
//             $parent_uuid = $this->getLastElementUuid($user, $path); //if path is empty, it returns the root_uuid (if not present the root, it creates it)
        
//             if ($parent_uuid == '' OR !$this->getIfIsDir($parent_uuid)) { //if the future parent does not exist OR is not a directory, ko
//                 throw new DataNotFoundException();
//             }
        
        
//             $uuid_toberemoved='';
        
//             $myfile_uuid = $this->getChildUuid($parent_uuid, $my_file_name);
        
//             if ($myfile_uuid != '') { //already present an element with the same name of the toBeAdded.... is it a file?....
        
//                 if($this->getIfIsDir($myfile_uuid)) { //there is a directory with the same name of the file I want to insert....
//                     throw new ConflictException();
//                 }
        
//                 //...it's a file!!
        
//                 //if the number of version in the databaseService is greater than MAX_VERSIONAMOUT_FOR_FILE, remove the version with lowest number
//                 //MAX_VERSIONAMOUT_FOR_FILE = 10
//                 if($this->getNumberOfVersionsPresent($myfile_uuid) > 9) {
//                     $lowest_version_uuid = $this->getLowestVersionUuid($myfile_uuid);
        
//                     $this->doDel('version', $lowest_version_uuid);
        
//                     $uuid_toberemoved = $lowest_version_uuid; //if it fails, no data is kept in the storage-db, so the user cannot reach it and have more than 10 versions
//                 }
        
//                 $this->doIns('version', [$my_file_version_uuid, $size, $myfile_uuid]);
        
//             } else {
//                 //file not present, I need to create a whole new file in file table
//                 $newfile_uuid = StorageServiceUtil::uuidV4();
        
//                 $this->doInsInFile($parent_uuid, [$newfile_uuid, $my_file_name, $user, false]);
//                 $this->doIns('version', [$my_file_version_uuid, $size, $newfile_uuid]);
//                 $this->doIns('has_parent', [$newfile_uuid, $parent_uuid]);
    
//             }
        
//             return $uuid_toberemoved; //empty string or uuid
        
//         }
        
        
//         public function moveElement($user, $path, $name, $newpath, $newname) {
        
        
//             if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $path = StorageServiceUtil::pathify($path);
    
//             if (($name==$newname) AND ($path==$newpath)) {
//                 return; //no business here to be done...
//             }
    
//             if ((strpos($name, '/') === true) OR ($name == '') OR (strlen($name) > 255)) {
//                 throw new InvalidArgumentException();
//             }
    
//             $newpath = StorageServiceUtil::pathify($newpath);
    
//             if ((strpos($newname, '/') === true) OR ($newname == '') OR (strlen($newname) > 255)) {
//                 throw new InvalidArgumentException();
//             }
    
//             //uuid of the file to be renamed and/or moved
//             $myfile_uuid = $this->getLastElementUuid($user, $path.'/'.$name);
    
//             if ($myfile_uuid == '') { //element not found...
//                 throw new DataNotFoundException();
//             }
    
    
//             //check whether an element with the same name already exists in the new path or the element to move doesn't exist
//             if ($this->getLastElementUuid($user, $newpath.'/'.$newname) != '') {
//                 throw new ConflictException();
//             }
        
        
//             try {
//                 $this->databaseService->beginTransaction();
        
//                 //renaming is required
//                 if ($name != $newname) {
        
//                     $stmt = $this->databaseService->prepare("UPDATE file SET file_name = :newname WHERE uuid = :myfile_uuid");
        
//                     $stmt->bindParam(':newname', $newname, PDO::PARAM_STR, 255);
//                     $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//                     $stmt->execute();
//                 }
        
//                 //moving is required
//                 if ($path != $newpath) {
//                     //getting the uuid of the future parent
//                     $future_parent_uuid = $this->getLastElementUuid($user, $newpath);
        
//                     if($future_parent_uuid == '') //the future parent directory doesn't exist
//                         throw new DataNotFoundException();
        
        
//                     $stmt = $this->databaseService->prepare("UPDATE has_parent SET uuid_parent = :future_parent_uuid WHERE uuid_child = :myfile_uuid");
        
//                     $stmt->bindParam(':future_parent_uuid', $future_parent_uuid, PDO::PARAM_STR, 36);
//                     $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//                     $stmt->execute();
//                 }
//                 $this->databaseService->commit();
        
//             } catch(PDOException $e) {
//                 $this->databaseService->rollBack();
//                 throw new DbException($e->getMessage());

//             } catch(DbException $d) {
//                 $this->databaseService->rollBack();
//                 throw new DbException($e->getMessage());
            
//             } catch(InvalidArgumentException $i) {
//                 $this->databaseService->rollBack();
//                 throw new InvalidArgumentException();
            
//             } catch(DataNotFoundException $f) {
//                 $this->databaseService->rollBack();
//                 throw new DataNotFoundException();

//             } catch(ConflictException $c) {
//                 $this->databaseService->rollBack();
//                 throw new ConflictException();
//             }
        
//         }
        
        
//         /*
//         * unix-like approach: renaming the element is just moving it in the same directory he's in with a different name
//         */
//         public function renameElement($user, $path, $name, $newname) {        
//             return $this->moveElement($user, $path, $name, $path, $newname);
//         }

        
//         /*
//         * The function to delete an element in databaseService that is accessible outside this class
//         * It returns the array with the uuid of the versions which correspond to a name of a physical file in the persistent, that will be removed outside
//         */
//         public function removeElement($user, $path, $name, $version) {
        
//             if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $user)) {
//                 throw new InvalidArgumentException();
//             }
        
//             $path = StorageServiceUtil::pathify($path);
        
//             if ($path != '' AND $name == '') { //after pathify, the '/' path has become ''
//                 throw new InvalidArgumentException();
//             }
        
//             if ((strpos($name, '/') === true) OR (strlen($name) > 255)) {
//                 throw new InvalidArgumentException();
//             }
        
//             if ($version != null) {
//                 if (!is_numeric($version)) { //necessary, otherwise the cast from a non numeric string to int will return 0 (=ma)
//                     throw new InvalidArgumentException();
//                 }
        
//                 $version = (int) $version;
        
//                 if ($version < 0) { //version ==0 : take the highest version, legit value
//                     throw new InvalidArgumentException();
//                 }
//             }
        
        
//             $elemUuid = $this->getLastElementUuid($user, $path . '/' . $name);
        
//             if ($elemUuid == '') {
//                 throw new NoContentException();
//             }
        
//             //just delete one version if it's a file
//             if (!$this->getIfIsDir($elemUuid)) {
//                 if ($version == null) {
//                     $version = 0;
//                 }
        
//                 if ($this->getThisVersionUuid($version,$elemUuid) == '') {
//                     throw new NoContentException();
//                 }
//             }

//             try {
        
//                 $this->databaseService->beginTransaction();
        
//                 $stack_of_uuid = array();
        
//                 $this->removeRecElement($user, $elemUuid, $version, $stack_of_uuid); //passing by reference the stack
        
//                 $this->databaseService->commit();
        
//                 return $stack_of_uuid;
            
//             } catch(PDOException $e) { //catching and throwing back again: if any, I need to rollback all the deleted data in the db
//                 $this->databaseService->rollBack();
//                 throw new DbException($e->getMessage());

//             } catch(DbException $d) { //catching and throwing back again: if any, I need to rollback all the deleted data in the db
//                 $this->databaseService->rollBack();
//                 throw new DbException($e->getMessage());
            
//             } catch(InvalidArgumentException $i) {
//                 $this->databaseService->rollBack();
//                 throw new InvalidArgumentException();
            
//             } catch(DataNotFoundException $f) {
//                 $this->databaseService->rollBack();
//                 throw new DataNotFoundException();
            
//             } catch(ConflictException $c) {
//                 $this->databaseService->rollBack();
//                 throw new ConflictException();
//             }
        
//         }
        
        
//         /*
//         * scan until last file or folder uuid is found
//         * return an uuid in a string
//         + base case: if an empty path is in input it will return the root uuid
//         */
//         private function getLastElementUuid($user, $path) {


        
//             //extract the token from the path
//             $parts = array_filter(explode('/', $path), 'strlen'); //tested: it DOESN"T include empty strings as array elements nor / ... surely legit filenames
    
//             //1: retrieve the root uuid for the given user
//             $parent_uuid = $this->getRootUuid($user);
    
//             foreach($parts as $t) {
//                 $parent_uuid = $this->getChildUuid($parent_uuid, $t);

//                 if ($parent_uuid == '') {
//                     return '';
//                 }
//             }
    
//             $last_element = $parent_uuid; //just for clarity..
    
//             return $last_element;
//         }
        
        
        
//         private function getRootUuid($user) {
        
//             //usefully return the empty string if no root for the user was found
// $sql = <<<SQL
// SELECT uuid
//     FROM file
//     WHERE user_uuid = :user AND file_name = :root_symbol
// SQL;
        
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $root_symbol = '/';
        
//             $stmt->bindParam(':user', $user, PDO::PARAM_STR, 36);
//             $stmt->bindParam(':root_symbol', $root_symbol, PDO::PARAM_STR);
        
//             $usr_root = $this->doFetch($stmt, "uuid");
        
//             //the user has no root folder, let's create it
//             if ($usr_root == "") {
//                 $usr_root=StorageServiceUtil::uuidV4();
        
//                 //maybe using a transaction will be better?
//                 $this->doInsInFile(null, [$usr_root, "/", $user, true]);
//                 $this->doIns('has_parent', [$usr_root, null]);
        
//             }
        
//             return $usr_root;
//         }
        

//         private function getChildUuid($parent_uuid, $fname) {
        
//             //get the child"s uuid with name equals to $fname
        
// $sql = <<<SQL
// SELECT F.uuid
//     FROM file AS F INNER JOIN has_parent AS H
//         ON F.uuid = H.uuid_child
//     WHERE H.uuid_parent = :parent_uuid AND F.file_name = :fname
// SQL;
        
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':parent_uuid', $parent_uuid, PDO::PARAM_STR, 36);
//             $stmt->bindParam(':fname', $fname, PDO::PARAM_STR, 255);
        
//             return $this->doFetch($stmt, "uuid");
//         }
        
        
//         private function getChildrenUuid($parent_uuid) {
//             if (!$this->getIfIsDir($parent_uuid)) {
//                 throw new DataNotFoundException();
//             }
        
// $sql = <<<SQL
//     SELECT F.uuid
//         FROM file AS F INNER JOIN has_parent AS H
//             ON F.uuid = H.uuid_child
//         WHERE H.uuid_parent = :parent_uuid
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':parent_uuid', $parent_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetchAll($stmt);
//         }
        

//         private function getAllVersionsUuid($myfile_uuid) {

// $sql = <<<SQL
// SELECT uuid
//     FROM version
//     WHERE uuid_file = :myfile_uuid
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetchAll($stmt);
//         }
        
        
        
//         private function getHighestVersionUuid($myfile_uuid) {

// $sql = <<<SQL
// SELECT uuid
//     FROM version
//     WHERE uuid_file = :myfile_uuid AND version_number = (
//         SELECT MAX(V2.version_number)
//             FROM version AS V2
//             WHERE V2.uuid_file = :myfile_uuid
//     )
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetch($stmt, "uuid");
//         }
        
//         private function getLowestVersionUuid($myfile_uuid) {

// $sql = <<<SQL
// SELECT uuid
//     FROM version
//     WHERE uuid_file = :myfile_uuid AND version_number = (
//         SELECT MIN(V2.version_number)
//             FROM version AS V2
//             WHERE V2.uuid_file = :myfile_uuid
//     )
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetch($stmt, "uuid");
//         }
        
//         private function getNumberOfVersionsPresent($myfile_uuid) {

// $sql = <<<SQL
// SELECT COUNT(*) FROM (
//     SELECT uuid
//         FROM version
//         WHERE uuid_file = :myfile_uuid
// ) tableAlias
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetch($stmt, 'count');
//         }
        
//         private function getThisVersionUuid($version, $myfile_uuid) {

// $sql = <<<SQL
// SELECT uuid
//     FROM version
//     WHERE uuid_file = :myfile_uuid AND version_number = :version
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
//             $stmt->bindParam(':version', $version, PDO::PARAM_INT);
        
//             return $this->doFetch($stmt, "uuid");
//         }
        

//         /*
//         * this function receives as input a filename
//         * returns if it is a directory (true) or a file (false)
//         */
//         private function getIfIsDir($myfile_uuid)
//         {
// $sql = <<<SQL
// SELECT is_dir
//     FROM file
//     WHERE uuid = :myfile_uuid
// SQL;
        
//             $stmt = $this->databaseService->prepare($sql);
        
//             $stmt->bindParam(':myfile_uuid', $myfile_uuid, PDO::PARAM_STR, 36);
        
//             return $this->doFetch($stmt, "is_dir");
//         }
        
        
//         /*
//         * $this->databaseService         is the connection (hopefully opened) to communicate with storage-db
//         * $parent_uuid  uuid of the parent (null if I want to insert root)
//         * $arr          the array contains the ordered value to be inserted (uuid, file_name, user_uuid, is_dir)
//         *
//         * Keep dinstinct this insertion from the others, due to additional existence check and so different input parameters set
//         */
//         private function doInsInFile($parent_uuid, $arr) {
//             if ($this->getChildUuid($parent_uuid, $arr[1]) != '') {
//                 throw new ConflictException(); //cannot insert a file if it's already present a file in the same directory with the same name
//             }

//             $in_file = "INSERT INTO file (uuid, file_name, user_uuid, is_dir) VALUES (:uuid, :file_name, :user_uuid, :is_dir)";
        
//             try {
//                 $stmt= $this->databaseService->prepare($in_file);
        
//                 $stmt->bindParam(':uuid', $arr[0], PDO::PARAM_STR, 36);
//                 $stmt->bindParam(':file_name',  $arr[1], PDO::PARAM_STR, 255);
//                 $stmt->bindParam(':user_uuid',  $arr[2], PDO::PARAM_STR, 36);
//                 $stmt->bindParam(':is_dir',  $arr[3], PDO::PARAM_BOOL);
        
//                 $stmt->execute();

//             } catch(PDOException $e) {
//                 throw new DbException($e->getMessage());
//             }
//         }
        
        
//         /*
//         * This private function do the insertion into a predefined table (different from file table) in the storage-db
//         * $table    specify the table where to do the insertion
//         * $arr      the array contains the ordered value to be inserted.
//         */
//         private function doIns($table, $arr) {
        
//             $in_has_parent = "INSERT INTO has_parent (uuid_child, uuid_parent) VALUES (:uuid_child, :uuid_parent)";
//             $in_version = "INSERT INTO version (uuid, file_size, uuid_file) VALUES (:uuid, :file_size, :uuid_file)";
    
//             $sql = ''; // no exception is thrown, it's just an internal error that should never happen
        
//             try {
        
//                 if($table == 'has_parent') {
//                     $stmt= $this->databaseService->prepare($in_has_parent);
        
//                     $stmt->bindParam(':uuid_child', $arr[0], PDO::PARAM_STR, 36);
//                     $stmt->bindParam(':uuid_parent',  $arr[1], PDO::PARAM_STR, 36);
        
//                     $sql=$table;
        
//                 } else if ($table == 'version') {
//                     $stmt= $this->databaseService->prepare($in_version);
        
//                     $stmt->bindParam(':uuid', $arr[0], PDO::PARAM_STR, 36);
//                     $stmt->bindParam(':file_size',  $arr[1], PDO::PARAM_INT);
//                     $stmt->bindParam(':uuid_file',  $arr[2], PDO::PARAM_STR, 36);
        
//                     $sql=$table;
//                 }
        
//                 if ($sql == '') {
//                     return;
//                 }
        
//                 $stmt->execute();
            
//             } catch(PDOException $e) {
//                 throw new DbException($e->getMessage());
//             }
//         }


//         private function doDel($table, $uuid) {
        
//             $del_file = "DELETE FROM file WHERE uuid = :uuid";
//             $del_version = "DELETE FROM version WHERE uuid = :uuid";
//             $del_has_parent = "DELETE FROM has_parent WHERE uuid_child = :uuid";
        
//             $sql = ''; // no exception is thrown if it remains empty, it's just an internal error that should never happen
        
//             if ($table == 'file') {
//                 $sql=$del_file;
//             }
        
//             if ($table == 'has_parent') {
//                 $sql = $del_has_parent;
//             }
        
//             if ($table == 'version') {
//                 $sql=$del_version;
//             }
        
//             if ($sql == '') {
//                 return;
//             }
        
        
//             try {
//                 $stmt = $this->databaseService->prepare($sql);
        
//                 $stmt->bindParam(':uuid', $uuid, PDO::PARAM_STR, 36);
        
//                 $stmt->execute();
            
//             } catch(PDOException $e) {
//                 throw new DbException($e->getMessage());
//             }
//         }
        

//         //it doesn't contain the beginTransaction and the commit, so it's more versatile
//         //0 is interpreted as maximum version number
//         private function removeVersion($file_uuid, int $version_number, &$stack) {
//             $v_uuid = null;
        
//             $v_uuid = ($version_number===0) ? $this->getHighestVersionUuid($file_uuid) : $this->getThisVersionUuid($version_number, $file_uuid);
        
//             $this->doDel('version',$v_uuid);
        
//             array_push($stack, $v_uuid);
        
//             if($this->getNumberOfVersionsPresent($file_uuid) == 0) {
//                 $this->doDel('has_parent',$file_uuid);
//                 $this->doDel('file',$file_uuid);
//             }
//         }
        
//         private function removeAllVersions($file_uuid) {
//             try {
//                 $stmt = $this->databaseService->prepare("DELETE FROM version WHERE uuid_file = :file_uuid");
        
//                 $stmt->bindParam(':file_uuid', $file_uuid, PDO::PARAM_STR, 36);
        
//                 $stmt->execute();

//             } catch(PDOException $e) {
//                 throw new DbException($e->getMessage());
//             }
        
//             $this->doDel('has_parent',$file_uuid);
//             $this->doDel('file',$file_uuid);
//         }
        
        
//         private function removeRecElement($user, $myelement_uuid, $version, &$stack) {
//             //base cases
//             if (is_int($version)) { //i'm dealing with one version... neat!
//                 return $this->removeVersion($myelement_uuid, $version, $stack);
//             }
        
//             if (!$this->getIfIsDir($myelement_uuid)) { //i'm dealing with a file with one or more versions... I need to remove them all!
//                 foreach($this->getAllVersionsUuid($myelement_uuid) as $v) {
//                     array_push($stack, $v['uuid']);
//                 }
        
//                 return $this->removeAllVersions($myelement_uuid);
//             }
        
        
//             //not a base case, I'm dealing with a directory
//             $children_uuid = $this->getChildrenUuid($myelement_uuid);
        
//             if (!($children_uuid == '')) {
//                 foreach($children_uuid as $child_uuid) {
//                     $this->removeRecElement($user, $child_uuid['uuid'], null, $stack);
//                 }
//             }
        
//             //first I remove all the children of the directory and then the parent directory... so no children without parent are ever present
//             $this->doDel('has_parent',$myelement_uuid);
//             $this->doDel('file',$myelement_uuid);
    
//             return;
//         }
    










//     /* DA ELIMINARE */
//         private function doFetch($stmt, $colName) {
        
//             try {
//                 $stmt->execute();
    
//                 $result = $stmt->fetch();
    
//             } catch(PDOException $e) {
//                 throw new DbException($e->getMessage());
//             }
    
//             return $result[$colName];
//         }
        
//         /*
//         * fetching a single row result from a query to storage-db
//         * return a json encoded string
//         */
//         private function doFetchAll($stmt)
//         {
        
//             try
//             {
//                 $stmt->execute();
        
//                 $result = $stmt->fetchAll();
        
//             }
//             catch(PDOException $e)
//             {
//                 throw new DbException($e->getMessage());
//             }
        
//             return $result;
//         }

    }