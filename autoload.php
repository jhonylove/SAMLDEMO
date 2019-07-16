<?php
require_once ('vendor/simplesamlphp/lib/_autoload.php');

define('ROOT', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);
//spl_autoload_register('autoload_demo');

spl_autoload_register(function ($class) {
    static $map = array (
        'Auth\\Custom\\MyAuth' => 'auth/custom/MyAuth.php',
        'Auth\\Custom\\SAMLHandler' => 'auth/custom/SAMLHandler.php',
        'Auth\\Custom\\SimpleCustom' => 'auth/custom/SimpleCustom.php',
        'Controllers\\loginController' => 'www/controllers/loginController.php',
        'Project\\Class2' => 'lots of classes.php',
        'Project\\Class3' => 'lots of classes.php',
    );

    if (isset($map[$class])) {
        require_once __DIR__ . "/{$map[$class]}";
    }
}, true, false);

//require_once __DIR__ . '/constants.php';
//require_once __DIR__ . '/functions.php';
//function autoload_demo($class)
//{
//    $class = ROOT . DS . str_replace("\\", DS, $class) . '.php';
//    if (!file_exists($class)) {
//        throw new Exception("Error al cargar la clase" . $class);
//    }
//}

?>
