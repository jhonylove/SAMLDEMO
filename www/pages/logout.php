<?php
require_once ('../../autoload.php');
use \Controllers\loginController;
use SimpleSAML\Utils\HTTP;

$controller= new loginController();
    $controller->logout($_GET);
