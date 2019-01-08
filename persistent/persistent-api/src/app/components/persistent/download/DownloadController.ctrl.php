<?php

/*
    DownloadController
*/

    namespace App\Components\Persistent\Download;

    use \InvalidArgumentException;


    class DownloadController {

        public function action($router, $persistentService) {

            // Get uuid
            $uuid = $router->getParam('uuid');

            try {
                // Check if file exists
                if (!$persistentService->exists($uuid)) {
                    // Error file not exists
                    $this->error(404);
                    return;
                }

                // Get file handler
                $file = $persistentService->get($uuid);

                $this->success(200, $file, $uuid);

                // Close file resource
                fclose($file);

            } catch (InvalidArgumentException $e) {
                // Success response
                $this->error(400);
                return;
            }
            
        }

        // Success response
        private function success($statusCode, $file, $uuid) {
            http_response_code($statusCode);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header("Content-Type: application/force-download");
            header("Content-disposition: attachment; filename=\"" . $uuid . "\"");
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . fstat($file)['size']);
            header('Content-Transfer-Encoding: binary');

            while(!feof($file)) {
                echo fread($file, 8192);
            }
        }

        private function error($statusCode) {
            http_response_code($statusCode);
        }
    }