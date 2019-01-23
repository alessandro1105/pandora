<?php

    namespace App\Components\Storage\Upload;

    use \InvalidArgumentException;


    // Controller class
    class UploadController {

        // Action of the controller
        public function action(/*$request, $userService*/) {
            echo "upload";
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
