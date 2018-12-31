<?php

//check for UUID version 4
function is_uuid($toCheck)
{
    $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    if(preg_match($UUIDv4, $toCheck))
        return true;
    return false;
}
