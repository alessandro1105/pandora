<?php

    namespace App\Components\User;

    use \PDO;

    // User class
    class User {

        // User uuid
        private $uuid;

        // User data
        private $data;


        public function __construct($data) {
            // Save user uuid
            $this->data = $data;
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
            return $this->data['uuid'];
        }

        // Return username of the user
        private function getUsername() {
            return $this->data['username'];
        }

        // Return email of the user
        private function getEmail() {
            return $this->data['email'];
        }

        // Return email of the user
        private function getEmailConfirmed() {
            return $this->data['email_confirmed'];
        }

        // Return email of the user
        private function getRegistrationDate() {
            return $this->data['registration_date'];
        }

        // Return email of the user
        private function getLastLoginDate() {
            return $this->data['last_login'];
        }
    }
