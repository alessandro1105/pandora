<?php

    namespace App\Commons\Http;

    use \Adbar\Dot; // Dot notation from composer package

    // Service HTTP

    Class HTTPService {

        public function __construct() {
            // Empty constructor
        }

        // Make a request and expect the result to be JSON
        public function request(string $method, string $url, array $fields = null) {
            // Create curl
            $ch = curl_init(); 

            // Setup curl
            curl_setopt($ch, CURLOPT_URL, $url); // Url
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method)); // method
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the body
            curl_setopt($ch, CURLOPT_HEADER, false); // Strip headers

            // Set body if it's not null
            // We assume that the body is an array and that it must be sent in json
            if (!is_null($fields)) {
                $body = json_encode($fields);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $body); 

                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json', // Content Type
                    'Content-Length: ' . strlen($body) // Content Length
                ]);
            }
             
            
            // Execute curl
            $response = curl_exec($ch);

            // Get the status code
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

            // Close curl
            curl_close($ch);

            $data = [
                'status' => (int) $status
            ];

            // if there is a body
            // Here we assume that the response is in JSON, if it's not it will trigger an error
            if (!empty($response)) {
                $data['data'] = json_decode($response, true);
            }

            return new Dot($data);

        }

        // Make an upload of a file. The method is PUT
        public function upload($url, $file, $filesize) {
            // Create curl
            $ch = curl_init(); 

            // Setup curl
            curl_setopt($ch, CURLOPT_URL, $url); // Url
            curl_setopt($ch, CURLOPT_PUT, true); // Set PUT method
            curl_setopt($ch, CURLOPT_INFILESIZE, $filesize); // Filesize
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the body
            curl_setopt($ch, CURLOPT_HEADER, false); // Strip headers

            curl_setopt($ch, CURLOPT_READFUNCTION, function () use ($file) { // Function to read the file
                return fread($file, 8192);
            });
            
            
            // Execute curl
            $response = curl_exec($ch);

            // Get the status code
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

            // Close curl
            curl_close($ch);

            $data = [
                'status' => (int) $status
            ];

            // if there is a body
            // Here we assume that the response is in JSON, if it's not it will trigger an error
            if (!empty($response)) {
                $data['data'] = json_decode($response, true);
            }

            return new Dot($data);
        }

        // Download a file. The method is GET
        public function download($url) {

        }
        
    }