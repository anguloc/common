<?php

if (is_file('/config/Constants.php')) {
    require_once __DIR__ . '/config/Constants.php';
}
require_once __DIR__ . '/files/Constants.php';
require_once __DIR__ . '/files/Helpers.php';
require_once __DIR__ . '/files/Collect.php';

//function commonLibAutoLoader($class_name)
//{
//    global $arrPublicClassName;
//    print_r($class_name);
//    die;
//    if (array_key_exists($class_name, $arrPublicClassName) && is_file($arrPublicClassName[$class_name])) {
//        require_once($arrPublicClassName[$class_name]);
//    }
//}
//
//
//$GLOBALS['arrPublicClassName'] = [
//    'RabbitMq' => '',
//];
//
//
//
//
//spl_autoload_register('commonLibAutoLoader');