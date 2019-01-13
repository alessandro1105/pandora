<?php

    namespace App\Components\User;

    use \PDO;

    // User class
    class User {

        // User uuid
        private $uuid;

        // User data
        private $data;

        // variable indicating that the data has been fetched
        private $isDataFetched;

        // Services
        private $databaseService;


        public function __construct($uuid, $databaseService) {
            // Save user uuid
            $this->uuid = $uuid;

            // Save services
            $this->databaseService = $databaseService;

            // Set that the data has not been fetched
            $this->isDataFetched = false;

        }

        public function __get($property) {
            switch ($property) {
                case 'uuid':
                    return $this->getUuid();
                    break;
                
                case 'username':
                    return $this->getUsername();
                    break;

                case 'email':
                    return $this->getEmail();
                    break;

                case 'emailConfirmed':
                    return $this->getEmailConfirmed();
                    break;

                case 'registrationDate':
                    return $this->getRegistrationDate();
                    break;

                case 'lastLoginDate':
                    return $this->getLastLoginDate();
                    break;

                default:
                    return null;
            }
        }

        // Return user id
        private function getUuid() {
            return $this->uuid;
        }

        // Return username of the user
        private function getUsername() {
            if (!$this->isDataFetched) {
                $this->fetchUser();
            }

            return $this->data['username'];
        }

        // Return email of the user
        private function getEmail() {
            if (!$this->isDataFetched) {
                $this->fetchUser();
            }

            return $this->data['email'];
        }

        // Return email of the user
        private function getEmailConfirmed() {
            if (!$this->isDataFetched) {
                $this->fetchUser();
            }

            return $this->data['email_confirmed'];
        }

        // Return email of the user
        private function getRegistrationDate() {
            if (!$this->isDataFetched) {
                $this->fetchUser();
            }

            return $this->data['registration_date'];
        }

        // Return email of the user
        private function getLastLoginDate() {
            if (!$this->isDataFetched) {
                $this->fetchUser();
            }

            return $this->data['last_login'];
        }

        private function fetchUser() {
            // Prepare sql statement
$sql = <<<SQL
    SELECT username, email, email_confirmed, activated, registration_date, last_login, password
        FROM users
        WHERE uuid = :uuid
SQL;
            $stmt = $this->databaseService->prepare($sql);
            // Bind parameters
            $stmt->bindParam(':uuid', $this->uuid, PDO::PARAM_STR); // Uuid
            // Execute the statement
            $result = $stmt->execute();

            // Get the data
            $this->data = $stmt->fetch(PDO::FETCH_ASSOC);

            // Setting that the data has been fetched
            $this->isDataFetched = true;
        }
    }
