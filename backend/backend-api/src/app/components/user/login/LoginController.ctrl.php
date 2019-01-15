<?php

    namespace App\Components\User\Login;

    use \InvalidArgumentException;


    // Controller class
    class LoginController {

        // Action of the controller
        public function action(/*$request, $userService*/) {

            echo "login";

            // if ($request->input->has('user') && $request->input->has('password')) {
            //     // Get the parameters from the request
            //     $user = $request->input->get('user');
            //     $password = $request->input->get('password');

            //     try {
            //         // Try the request
            //         $status = $userService->login($user, $password);

            //     } catch (InvalidArgumentException $e) {
            //         // Bad Request
            //         $this->error(400, [
            //             'errors' => [
            //                 'badRequest' => 'The data in the request is wrong.'
            //             ]
            //         ]);

            //         return false;
            //     }

            //     // If the login is successful
            //     if ($status) {

            //         // Send the response
            //         $this->success($userService->user);

            //     } else {
            //         // Unauthorized
            //         $this->error(401, [
            //             'errors' => [
            //                 'badCredentials' => 'Email/Username and password are wrong.'
            //             ]
            //         ]);
            //     }

            // } else {
            //     // Bad Request
            //     $this->error(400, [
            //         'errors' => [
            //             'badRequest' => 'The data in the request is wrong.'
            //         ]
            //     ]);
            // }

            // return false;
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
