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

            // Check if the user is logged and load his information
            if ($this->request->session->has('user')) {
                $user = $this->request->session->get('user', null);
                // Create user object
                $this->user = new User($user);
                // Set that the user is logged
                $this->isLogged = true;

            // The user is not logged
            } else {
                $this->user = null;
                $this->isLogged = false;
            }
        }

        // Return boolean informing if the user is logged
        public function isLogged() {
            return $this->isLogged;
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

            // Try the login
            $response = $this->http->request(
                'POST',
                $this->endpoint . '/login',
                [
                    'user' => $user,
                    'password' => $password
                ]
            );

            if ($response->get('status') == 200) {

                // Obtain the data from the response
                $user = [
                    'uuid' => $response->get('data.uuid'),
                    'username' => $response->get('data.username'),
                    'email' => $response->get('data.email'),
                    'email_confirmed' => $response->get('data.emailConfirmed'),
                    'registration_date' => $response->get('data.registrationDate'),
                    'last_login' => $response->get('data.lastLoginDate')
                ];

                // Create the user object
                $this->user = new User($user);

                // Save the user in the session
                $this->request->session->put('user', $user);

                // Regenerate the session
                $this->request->session->regenerate();

                // Th user is logged in
                $this->isLogged = true;

                return true;
            }


            // Return erroneous login
            return false;
        }


        // Logout the user
        public function logout() {
            // Save the user in the session
            $this->request->session->forget('user');

            // Regenerate the session
            $this->request->session->regenerate();

            // Set that the user is not logged
            $this->isLogged = false;
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

            // Try the login
            $response = $this->http->request(
                'POST',
                $this->endpoint . '/signup',
                [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password
                ]
            );

            if ($response->get('status') == 200) {

                // Obtain the data from the response
                $user = [
                    'uuid' => $response->get('data.uuid'),
                    'username' => $response->get('data.username'),
                    'email' => $response->get('data.email'),
                    'email_confirmed' => $response->get('data.emailConfirmed'),
                    'registration_date' => $response->get('data.registrationDate'),
                    'last_login' => $response->get('data.lastLoginDate')
                ];

                // Create the user object
                $this->user = new User($user);

                // Save the user in the session
                $this->request->session->put('user', $user);

                // Regenerate the session
                $this->request->session->regenerate();

                // Th user is logged in
                $this->isLogged = true;

                return true;

            // Response is 409, so the email or the username was already in use
            } else if ($response->get('status') == 409) {

                // Username already used
                if ($response->has('data.errors.usernameRegistered')) {
                    throw new UsernameAlreadyRegisteredException('Username already registered!');
                } else {
                    throw new EmailAlreadyRegisteredException('Email already registered!');
                }

            }

            return false;

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
            if ($this->isLogged) {
                return $this->user;
            }

            return null;
        }


    }
