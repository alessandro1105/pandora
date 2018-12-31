<?php

include 'storageExceptions.php';
include 'is_uuid.php';

//the folder where all files are kept
$persistent_storage_folder = __DIR__ . "/persistent";


// delform was sent?
if(isset($_POST['upsubmit']) and isset($_FILES["fileToUpload"]))
{

    if(trim($_FILES["fileToUpload"]["name"]) == '')
    {
        throw new NoFileToUploadException();
    }

    else if(!is_uuid($_FILES["fileToUpload"]["name"]))
    {
        throw new IllegalNameException();
    }

    else if(!is_uploaded_file($_FILES["fileToUpload"]["tmp_name"]) or $_FILES["fileToUpload"]["error"]>0)
    {
        throw new UploadErrorException();
    }

    // let's hope storage folder still exists!
    else if(!is_dir($persistent_storage_folder))
    {
        throw new FolderNotFoundException();
    }

    else if(!is_writable($persistent_storage_folder))
    {
        throw new FolderNotWritableException();
    }

    else
    {
        //this is the absolute file name to upload...
        $file = $persistent_storage_folder."/".$_FILES["fileToUpload"]["name"];



        //...but maybe it already exists? If it does, it won't be overwritten
        if(file_exists($file))
        {
            http_response_code(409);
        }

        else
        {
            // allright, let's do it. Move from temp to storage folder the file to be uploaded. And then if some false value is returned, let's tell the User
            if(!move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $file))
            {
                throw new UploadErrorException();
            }

            //success!!
            else
            {
                http_response_code(200);
            }
        }
    }
}
