<?php

    namespace App\Components\User\Logged;

    use \InvalidArgumentException;


    // Controller class
    class LoggedController {

        // Action of the controller
        public function action($userService) {

            // Check if the user is already logged in
            if ($userService->isLogged()) {
                $this->success($userService->user);

            } else {
                // Unauthorized
                $this->error(401, [
                    'errors' => [
                        'notAuthorized' => 'User is not currently logged in.'
                    ]
                ]);
            }

            return false;
        }


        /* =============== Private =============== */

        // Generate the response
        private function success($user) {
            // Setting status code
            http_response_code(200); // OK
            // Setting the content type of the request
            header('Content-Type: application/json');

            // Prepare the response
            $response = [
                'uuid' => htmlspecialchars($user->uuid),
                'username' => htmlspecialchars($user->username),
                'email' => htmlspecialchars($user->email),
                'emailConfirmed' => $user->emailConfirmed,
                'registrationDate' => $user->registrationDate,
                'lastLoginDate' => $user->lastLoginDate
            ];

            // echo the response
            echo json_encode($response, JSON_PRETTY_PRINT);
        }

        // Generate the error response
        private function error($errorCode, $errors = array()) {
            // Setting status code
            http_response_code($errorCode);

            if ($errors != array()) {
                // Setting the content type of the request
                header('Content-Type: application/json');

                // echo the response
                echo json_encode($errors, JSON_PRETTY_PRINT);
            }
        }
    }
