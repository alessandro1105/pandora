<?php

    namespace App\Components\Storage;

    use \InvalidArgumentException;
    use \PDO;

    use \App\Components\Storage\Exceptions\NoSuchFileOrDirectoryException;
    use \App\Components\Storage\Exceptions\FileOrDirectoryAlreadyExistsException;
    use \App\Components\Storage\Exceptions\NotADirectoryException;
    use \App\Components\Storage\Exceptions\NotAFileException;
    use \App\Components\Storage\Exceptions\NoSuchFileVersionException;
    
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
    WHERE uuid_file = :uuid_file AND uploaded = TRUE
    ORDER BY version_number
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
                throw new NoSuchFileOrDirectoryException('No such file or directory \'' . $path .  '/' . $name . '\'!');

            // If the file is a directory
            } else if ($file === false) {
                throw new NotAFileException('Not a file \'' . $path . '/' . $name . '\'!');
            }

            // Prepare sql satement
            $stmt = null;

            if (!is_null($version)) {
                // Select all versions
$sql = <<<SQL
SELECT *
    FROM version
    WHERE uuid_file = :uuid_file AND uploaded = TRUE AND version_number = :version_number
    ORDER BY version_number
SQL;
                // Prepare the query
                $stmt = $this->databaseService->prepare($sql);

                // Bind query parameters
                $stmt->bindParam(':uuid_file', $file, PDO::PARAM_STR);
                $stmt->bindParam(':version_number', $version, PDO::PARAM_INT);
            
            } else {
$sql = <<<SQL
SELECT *
    FROM version
    WHERE uuid_file = :uuid_file 
        AND uploaded = TRUE 
        AND version_number = (SELECT MAX(version_number)
                                    FROM version
                                    WHERE uuid_file = :uuid_file)
    ORDER BY version_number
SQL;
                // Prepare the query
                $stmt = $this->databaseService->prepare($sql);

                // Bind query parameters
                $stmt->bindParam(':uuid_file', $file, PDO::PARAM_STR);
            }

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // We consider that the query has been executed correctly
            $version = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($version)) {
                throw new NoSuchFileVersionException('File version not found!');
            }

            // Return the versions
            return $version[0];
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

        // Check if it is a directory
        public function isADirectory($user, $path, $name) {
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

            // It's the root
            if ($path == '/' && $name == '/') {
                return true;
            }

            $parent = $this->getDirectory($user, $path);

            $child = $this->getChildDirectory($name, $parent);

            // Check if the element exists
            if (is_null($child)) {
                throw new NoSuchFileOrDirectoryException('No such file or directory \'' . $path .  '/' . $name . '\'!');

            // It's a file
            } else if ($child === false) {
                return false;
            }

            // It's a directory
            return true;
        }

        // List the directory content
        public function listDirectory($user, $path, $name) {
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
            $child = null;

            if ($name == '/') {
                $child = $parent;
            } else {
                $child = $this->getChildDirectory($name, $parent);
            }

            // Check if the element exists
            if (is_null($child)) {
                throw new NoSuchFileOrDirectoryException('No such file or directory \'' . $path .  '/' . $name . '\'!');

            // It's a file
            } else if ($child === false) {
                throw new NotADirectoryException('Not a directory \'' . $path . '\'!');
            }

            // Prepare the query
$sql = <<<SQL
SELECT *
	FROM file as F LEFT JOIN has_parent as H
		ON F.uuid = H.uuid_child
	WHERE H.uuid_parent = :uuid_directory
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);

            // Bind query parameters
            $stmt->bindParam(':uuid_directory', $child, PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            $listing = [];

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($data as $item) {

                $object = [
                    'directory' => $item['is_dir'],
                    'name' => $item['file_name'],
                    'path' => ($path == '/' ? '/' : $path . '/') . ($name == '/' ? '' : $name . '/') . $item['file_name'],
                    'creation_time' => strtotime($item['creation_time'])
                ];

                $listing[] = $object;
            }

            return $listing;
        }

        // List the directory content
        public function delete($user, $path, $name) {
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

            $child = $this->getChildElement($name, $parent);

            if (is_null($child)) {
                throw new NoSuchFileOrDirectoryException('No such file or directory \'' . $path . '/' . $name . '\'!');
            }

            // var_dump($child);
            // exit;

            return $this->deleteRecursively($child);

        }



        // --- PRIVATE FUNCTIONS ---

        // Delete recursively and return array of uuid of file version to remove
        private function deleteRecursively($element) {
            $uuids = [];

            // It's a directory
            if ($element['is_dir']) {

$sql = <<<SQL
SELECT * 
	FROM file AS F JOIN has_parent AS H
		ON H.uuid_child = F.uuid
	WHERE H.uuid_parent = :uuid_parent
SQL;
                // Prepare the query
                $stmt = $this->databaseService->prepare($sql);
                
                // Bind query parameters
                $stmt->bindParam(':uuid_parent', $element['uuid'], PDO::PARAM_STR);

                // Execute the query
                $result = $stmt->execute();

                if (!$result) {
                    // Something went wrong with the query
                    throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
                }

                $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($children as $child) {
                    $versions = $this->deleteRecursively($child);

                    foreach($versions as $version) {
                        $uuids[] = $version;
                    }
                }
            
            // It's a file
            } else {
                // Select all versions
$sql = <<<SQL
SELECT *
    FROM version
    WHERE uuid_file = :uuid_file
SQL;

                // Prepare the query
                $stmt = $this->databaseService->prepare($sql);
                
                // Bind query parameters
                $stmt->bindParam(':uuid_file', $element['uuid'], PDO::PARAM_STR);

                // Execute the query
                $result = $stmt->execute();

                if (!$result) {
                    // Something went wrong with the query
                    throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
                }

                $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($versions as $version) {
                    $uuids[] = $version['uuid'];
                }

                // Delete all the version
$sql = <<<SQL
DELETE FROM version
    WHERE uuid_file = :uuid_file
SQL;
            
                // Prepare the query
                $stmt = $this->databaseService->prepare($sql);
                
                // Bind query parameters
                $stmt->bindParam(':uuid_file', $element['uuid'], PDO::PARAM_STR);

                // Execute the query
                $result = $stmt->execute();

                if (!$result) {
                    // Something went wrong with the query
                    throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
                }
            
            }

            // Delete from child
$sql = <<<SQL
DELETE FROM has_parent
    WHERE uuid_child = :uuid_child
SQL;

            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);
            
            // Bind query parameters
            $stmt->bindParam(':uuid_child', $element['uuid'], PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            // Remove the element
$sql = <<<SQL
DELETE FROM file
    WHERE uuid = :uuid
SQL;
            
            // Prepare the query
            $stmt = $this->databaseService->prepare($sql);
            
            // Bind query parameters
            $stmt->bindParam(':uuid', $element['uuid'], PDO::PARAM_STR);

            // Execute the query
            $result = $stmt->execute();

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            return $uuids;
        }

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
            $directory = $this->getChildElement($name, $parent);
            
            if ($directory == null) {
                return null;
            }

            if (!$directory['is_dir']) {
                return false;
                
            } else {
                return $directory['uuid'];
            }            
        }

        // Return the uuid of the file
        private function getChildFile($name, $parent) {
            $file = $this->getChildElement($name, $parent);
            
            if ($file == null) {
                return null;
            }

            if ($file['is_dir']) {
                return false;

            } else {
                return $file['uuid'];
            }

        }

        private function getChildElement($name, $parent) {
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

            if (!$result) {
                // Something went wrong with the query
                throw new DatabaseQueryExecutionException('Something went wrong with \'' . $sql . '\'!');
            }

            return $stmt->fetch(PDO::FETCH_ASSOC);
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

    }