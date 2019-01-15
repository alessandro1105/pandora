<?php

    namespace App\Components\User;

    use \App\Components\User\Signup\Exceptions\EmailAlreadyRegisteredException;
    use \App\Components\User\Signup\Exceptions\UsernameAlreadyRegisteredException;
    use \InvalidArgumentException;
    use \App\Components\User\User;
    use \PDO;


    // User class
    class UserService {

        // User uuid
        private $uuid;

        // User object
        private $user;

        // Endpoint
        private $endpoint;

        // Services
        private $http; // Http service
        private $request; // Request service



        // Costructor
        public function __construct($USER_SERVICE_API_ENDPOINT, $http, $request) {

            // Save endpoint service
            $this->endpoint = $USER_SERVICE_API_ENDPOINT;

            // Save http service
            $this->http = $http; // Http service
            $this->request = $request; // Request service

            // Intialize user
            $this->user = null;
        }

        // Login the user
        public function login($user, $password) {
            // Let's validate input
            if (!preg_match('/^[a-zA-Z][a-zA-z0-9]{4,20}$/', $user) and
                !preg_match('/^[a-zA-Z0-9\.\!\#\$\%\&\'\*\+\/\=\?\^\_\`\{\|\}\~\-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $user)) {

                throw new InvalidArgumentException('User is not a valid username or email!');
            }
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d$@$!%*#?&]{8,}$/', $password)) {
                throw new InvalidArgumentException('Password is not valid!');
            }

            // Prepare sql statement
$sql = <<<SQL
    SELECT uuid, password
        FROM users
        WHERE (username = :username OR email = :email) AND activated = TRUE AND deleted = FALSE
SQL;
            $stmt = $this->databaseService->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':username', $user, PDO::PARAM_STR); // Username
            $stmt->bindParam(':email', $user, PDO::PARAM_STR); // Email

            // Execute the statement
            $result = $stmt->execute();

            if ($result) {

                // Get user data from the database
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if there is a user with that username or email and that the password match
                if ($user and password_verify($password, $user['password'])) {
                    // The user exists and the password matches
                    // Save the user uuid session
                    $this->uuid = $user['uuid'];
                    // Create user object
                    $this->user = new User($this->uuid, $this->databaseService);

                    // Set last login of the user
$sql = <<<SQL
UPDATE users
    SET last_login = NOW()
    WHERE uuid = :uuid
SQL;
                    $stmt = $this->databaseService->prepare($sql);

                    // Bind parameters
                    $stmt->bindParam(':uuid', $this->uuid, PDO::PARAM_STR); // Username

                    // Execute the statement
                    $result = $stmt->execute();

                    // Successfully login
                    return true;
                }
            }

            // Return erroneous login
            return false;
        }

        // Login the user
        public function signup($username, $email, $password) {
            // Let's validate input
            if (!preg_match('/^[a-zA-Z][a-zA-z0-9]{4,20}$/', $username)) {
                throw new InvalidArgumentException('Username is not valid!');
            }
            if (!preg_match('/^[a-zA-Z0-9\.\!\#\$\%\&\'\*\+\/\=\?\^\_\`\{\|\}\~\-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $email)) {
                throw new InvalidArgumentException('Email is not valid!');
            }
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d$@$!%*#?&]{8,}$/', $password)) {
                throw new InvalidArgumentException('Password is not valid!');
            }

            // Create the sql statement
$sql = <<<SQL
    INSERT INTO users (uuid, username, email, password)
        VALUES (uuid_generate_v4(), :username, :email, :password)
SQL;

            $stmt = $this->databaseService->prepare($sql);

            // Bind data to the statement
            $stmt->bindParam(':username', $username, PDO::PARAM_STR); //username
            $stmt->bindParam(':email', $email, PDO::PARAM_STR); //email
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);

            // Execute the statement
            $result = $stmt->execute();

            if ($result) {
                // Log the user
                return $this->login($email, $password);

            } else {
                // Check if the username is already registered (if not the email is already registered)
$sql = <<<SQL
    SELECT COUNT(*) AS username_exists
        FROM users
        WHERE username = :username;
SQL;
                $stmt = $this->databaseService->prepare($sql);

                // Bind data to the statement
                $stmt->bindParam(':username', $username, PDO::PARAM_STR); //username

                // Execute the statement
                $result = $stmt->execute();

                // If the query has been succesfully executed
                if ($result) {
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['username_exists']) {
                        throw new UsernameAlreadyRegisteredException('Username already registered!');
                    } else {
                        throw new EmailAlreadyRegisteredException('Email already registered!');
                    }
                }
            }

        }


        public function __get($property) {
            switch ($property) {
                case 'user':
                    return $this->getUser();
                    break;

                default:
                    return null;
            }
        }

        // Get user object
        private function getUser() {
            return $this->user;
        }


    }
