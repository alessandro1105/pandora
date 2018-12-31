<?php

include 'storageExceptions.php';
include 'is_uuid.php';

//no final slash!! This is a simulation of the folder where the persistent service store its files
$persistent_storage_folder = __DIR__ . "/persistent";



// if the form was submitted
if(isset($_GET['downsubmit']))
{
    // let's verify the file it's not empty
    if(trim($_GET["fileToDownload"]) == '')
    {
        throw new FileNameNotSetException();
    }

    else if(!is_uuid($_GET["downsubmit"]))
    {
        throw new IllegalNameException();
    }

    else
    {
        // complete absolute file name
        $file = $persistent_storage_folder."/".$_GET["fileToDownload"];

        // error: the file doesn't exist...
        if(!file_exists($file))
            http_response_code(404);

        //allright, the file exists, let's give it to the User
        else
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header("Content-Type: application/force-download");
            header("Content-disposition: attachment; filename=\"".basename($_GET["fileToDownload"])."\"");
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            header('Content-Transfer-Encoding: binary');


            //new solution: chunked file!! Tested!
            $chunkSize = 1024 * 1024;
            $handle = fopen($file, 'rb');
            while (!feof($handle))
            {
                $buffer = fread($handle, $chunkSize);
                echo $buffer;
                ob_flush();
                flush();
            }
            fclose($handle);


        }

    }

}
