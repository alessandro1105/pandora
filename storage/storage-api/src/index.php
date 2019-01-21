<?php


require "UploadController.php";
require "StorageService.php";
require "StorageServiceUtil.php";
require "RetrieveController.php";
require "EditController.php";
require "DeleteController.php";
/*

//.... NOTE, other are needed!!


*/


$controller = NULL;

if(!isset($_GET['path']))
{
    die("remember to insert the path!");
}


if(isset($_GET['retrieve']))
    $controller = new RetrieveController();

else if(isset($_GET['upload']))
    $controller = new UploadController();

else if(isset($_GET['edit']))
    $controller = new EditController();

else if(isset($_GET['delete']))
    $controller = new DeleteController();

$controller->action();
