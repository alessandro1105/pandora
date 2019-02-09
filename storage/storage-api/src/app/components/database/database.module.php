<?php

    use \App\Components\Database\Exceptions\DatabaseConnectionException;

    // Declare database module
    $azzurro->module('database', [])

    ->factory('databaseService', function () {

        // NOTE: PHP will automatically close the connection at the end of the script
        // NOTE: This connection information are just to debug purpose

        // The object containing the connection to the database
        $pdo = null;

        try {
            // Create connection string
            $dsn = 'pgsql:host=storage-db port=5432 dbname=storage user=postgres password=pandora1';
            // Instantiate the connection
            $pdo = new PDO($dsn);

        } catch (PDOException $e) {
            throw new DatabaseConnectionException('Could not connect to the database!');
        }

        return $pdo;

    });
