<?php

/*
    PersistentService
*/

    namespace App\Components\Persistent;

    use \InvalidArgumentException;


    class PersistentService {

        // Path where files are stored
        const PERSISTENT_DIR = '/persistent';

        // Void constructor
        public function __construct() {
            // Void
        }

        // Download file identified by uuid
        public function get($uuid) {
            // Return a file resource
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
                throw new InvalidArgumentException("'uuid' must be a valid uuid v4!");
            }

            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
                throw new InvalidArgumentException("'uuid' must be a valid uuid v4!");
            }

            if (file_exists($file) && is_file($file)) {
                // Create the file
                $resource = fopen($file, 'rb');
                // Return file resource
                return $resource;

            } else {
                return false;
            }
        }

        // Save file incoming as uuid
        // Return a file resource
        public function create($uuid) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
                throw new InvalidArgumentException("'uuid' must be a valid uuid v4!");
            }

            // Compose file name
            $file = self::PERSISTENT_DIR . '/' . $uuid;

            if (file_exists($file) && is_file($file)) {
                return false;

            } else {
                // Create the file
                $resource = fopen($file, 'wb+');
                // Return file resource
                return $resource;
            }
        }

        // Delete file identified by uuid
        // Delete the file
        public function delete($uuid) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
                throw new InvalidArgumentException("'uuid' must be a valid uuid v4!");
            }

            // Compose file name
            $file = self::PERSISTENT_DIR . '/' . $uuid;

            if (file_exists($file) && is_file($file)) {
                unlink($file);
                return true;

            } else {
                return false;
            }

        }

        // Check if file identified by uuid exists
        // Return a boolean
        public function exists($uuid) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
                throw new InvalidArgumentException("'uuid' must be a valid uuid v4!");
            }

           

            return file_exists($file) && is_file($file);
        }


    }