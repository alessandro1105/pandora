<?php

    namespace App\Components\User\Signup;

    use \App\Components\User\Signup\Exceptions\EmailAlreadyRegisteredException;
    use \App\Components\User\Signup\Exceptions\UsernameAlreadyRegisteredException;
    use \InvalidArgumentException;


    // Controller class
    class SignupController {

        // Action of the controller
        public function action($request, $userService) {

            // Check if the user is already logged
            if ($userService->isLogged()) {
                // If the user is already logged in
                $this->error(403, [
                    'errors' => [
                        'userLogged' => 'User is logged in.'
                    ]
                ]); // Forbidden
                // Stop executing
                return false;
            }

            if ($request->input->has('username') && 
                $request->input->has('email') && 
                $request->input->has('password')) {

                // Get the parameter from the request
                $username = $request->input->get('username');
                $email = $request->input->get('email');
                $password = $request->input->get('password');

                // Try to register the user
                try {
                    $userService->signup($username, $email, $password);

                // Bad request (username, email or password are malformed)
                } catch (InvalidArgumentException $e) {
                    // Bad Request
                    $this->error(400, [
                        'errors' => [
                            'badRequest' => 'The data in the request is wrong.'
                        ]
                    ]); // Bad Request
                    return false;

                // Username already registered
                } catch (UsernameAlreadyRegisteredException $e) {
                    // Error response (Conflict)
                    $this->error(409, [
                        'errors' => [
                            'usernameRegistered' => 'Username already registered.'
                        ]
                    ]);
                    return false;

                } catch (EmailAlreadyRegisteredException $e) {
                    // Error response (Conflict)
                    $this->error(409, [
                        'errors' => [
                            'emailRegistered' => 'Email already registered.'
                        ]
                    ]);
                    return false;
                }

                // Succesfull response
                $this->success($userService->user);

            } else {
                // Bad Request
                $this->error(400, [
                    'errors' => [
                        'badRequest' => 'The data in the request is wrong.'
                    ]
                ]);
            }

            return false;
        }


        /* =============== Private =============== */

        // Generate the success response
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
