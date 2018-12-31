<?php

include 'storageExceptions.php';
include 'is_uuid.php';

//cartella dove rimuovere il file
$persistent_storage_folder = __DIR__ . "/persistent";

if(isset($_POST['delsubmit']))
{
    if(trim($_POST["fileToDelete"]) == '')
    {
        throw new FileNameNotSetException();
    }

    else if(!is_uuid($_POST["fileToDelete"]))
    {
            throw new IllegalNameException();
    }

    else
    {
        // complete absolute file name
        $file = $persistent_storage_folder."/".$_POST["fileToDelete"];


        //test of being an uuid already passed, maybe this check is redundant
        if(!is_file($file))
        {
            throw new NotAFileException();
        }

        else if(!file_exists($file))
        {
            http_response_code(204);
        }

        else
        {
            unlink($file);
            http_response_code(200);
        }

    }

}
