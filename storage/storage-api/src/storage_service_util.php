<?php

namespace App\Components\Storage\Util;

//This class contains:
//-uuid_v4 generator
//-check functions
//as static methods

class storage_service_util
{

//uuid v4 generator
public static function uuid_v4()
{
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}





/*
* Utility to separate the path into two pieces: (the path -1 element) and (the element)
* It also do additional checks like pathify and is_file_name
* Example: /bla/bla/blaargh will be splitted into /bla/bla and blaargh
*/
public static function divide_path_from_last($path)
{

    $path = self::pathify($path);

    if(strlen($path) == 0 )
        throw new InvalidArgumentException(); //divide an empty path into more pieces is a mischievious action that will be punished


    //The last directory in the file is the one I need

    $arrpath = explode('/', pathify($path) );

    if(count($arrpath) == 1) //only one element that for previous check is not the empty string
        return ['', $arrpath[0]]; //this element is in the root directory

    //let's put in strpath the path excluding the last element
    $strpath='/'; //so that is not empty and I can use pathify even if no other directories are added
    for($i=0; $i<=count($arrpath)-2; $i++) //not executed if the array has only one element
        $strpath=$strpath.$arrpath[$i].'/';


    $strpath = self::pathify($strpath); //let's trash the final and initial / ... it may return the empty string, it's fine (the last_el is in root)


    $last_el = $arrpath[count($arrpath)-1]; //the last element, a.k.a. a dirname or a filename


    if(!is_file_name($last_el))
        throw new InvalidArgumentException();

    return [$strpath, $last_el];

}


//throw exceptions if path is empty or contain // character (which means a directory name is empty string, that is illegal)
//usefully transform /etc/div/ into etc/div
//it won't throw an exception if an empty string is passed, it will be consider as a root. So pathify is idempotent
public static pathify($pathToBe)
{
    if( preg_match('/\/\//',$pathToBe) OR strlen($pathToBe) > 4096 ) //assuming ASCII code we have 1 character = 1 byte, and for sure max path = 4096 bytes
        throw new InvalidArgumentException();

    if( (substr($path, 0, 1) == '/') ) //let"s trash the initial / symbol if it"s present
        $path = substr($path, 1);

    if( (substr($path, -1, 1) == '/') ) //let"s trash the final / if it"s present
        $path = substr($path,0,-1);

    return $path;
}

//check for UUID version 4
public static function is_uuid($toCheck)
{
    $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    if(preg_match($UUIDv4, $toCheck))
        return true;
    return false;
}

public static function is_file_name($toCheck)
{
    //having a / or being empty is illegal for a file name (without path)
    if( (strpos($toCheck, '/') === true) OR ($toCheck == '') OR (strlen($toCheck) > 255))
        return false;
    return true;
}


}
