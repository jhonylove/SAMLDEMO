<?php
require_once ('vendor/simplesamlphp/lib/_autoload.php');

define('ROOT', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);
//spl_autoload_register('autoload_demo');

spl_autoload_register(function ($class) {
    static $map = array (
        'Auth\\Custom\\MyAuth' => 'auth/custom/MyAuth.php',
        'Auth\\Custom\\SimpleCustom' => 'auth/custom/SimpleCustom.php',
        'Auth\\Facebook\\Facebook' => 'auth/facebook/Facebook.php',
        'Auth\\Ldap\\LDAP' => 'auth/ldap/LDAP.php',
        'Auth\\Pgsql\\PGSQL' => 'auth/pgsql/PGSQL.php',
        'Auth\\Oci8\\oci8' => 'auth/oci8/oci8.php',
        'Auth\\SAMLHandler' => 'auth/SAMLHandler.php',
        'Controllers\\loginController' => 'www/controllers/loginController.php',
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
