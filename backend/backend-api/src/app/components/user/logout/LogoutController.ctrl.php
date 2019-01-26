<?php

    namespace App\Components\User\Logout;

    use \InvalidArgumentException;


    // Controller class
    class LogoutController {

        public function action($userService) {

            // Check if the user is already logged
            if ($userService->isLogged()) {
                // Logout the user
                $userService->logout();
                // Send status code
                $this->success();

            // The user is not logged
            } else {
                $this->error(401, [
                    'errors' =>[
                        'userNotLogged' => "User is not logged in."
                    ]
                ]); // Forbidden
            }

            return false;
        }


        /* =============== Private =============== */

        // Generate the response
        private function success() {
            // Setting status code
            http_response_code(204); // No Content
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
