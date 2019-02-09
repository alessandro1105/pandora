<?php
/*
	User application autolaoder file

	This file is the file which will load your application.

    DO NOT DELETE THIS FILE otherwise your application will not be loaded.
*/

    /* =============== LOAD MODULES DECLARATIONS =============== */

    require_once(__DIR__ . '/app.module.php');
    require_once(__DIR__ . '/DeleteController.php');
    require_once(__DIR__ . '/EditController.php');
    require_once(__DIR__ . '/RetrieveController.php');
    require_once(__DIR__ . '/StorageService.php');
    require_once(__DIR__ . '/StorageServiceUtil.php');
    require_once(__DIR__ . '/UploadController.php');