<?php

require_once __DIR__ . '/config/Constants.php';
require_once __DIR__ . '/files/Constants.php';
require_once __DIR__ . '/files/Helpers.php';

function commonLibAutoLoader($class_name)
{
    global $arrPublicClassName;
    if (array_key_exists($class_name, $arrPublicClassName) && is_file($arrPublicClassName[$class_name])) {
        require_once($arrPublicClassName[$class_name]);
    }
}


$GLOBALS['arrPublicClassName'] = [

];




spl_autoload_register('commonLibAutoLoader');