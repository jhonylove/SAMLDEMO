<?php
require_once ('../../autoload.php');
use \Controllers\loginController;
use SimpleSAML\Utils\HTTP;

$controller= new loginController();
if($controller->checklogin($_POST['idp'])){
echo'<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>SAMLPhp demo</title>

    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/collapse_sideBar.css">
    <link rel="stylesheet" href="../resources/css/navBar.css">
    <link rel="stylesheet" href="../resources/css/colors.css">
    <link rel="stylesheet" href="../resources/css/fontawesome/css/all.min.css">


    <link rel="shortcut icon" href="../resources/img/favicon.ico">
</head>

<body>
<div class="wrapper">
    <!-- Sidebar  -->
    <nav id="sidebar">
        <div class="sidebar-header">

            <h3><a href="index.php"><img id="img-log" class="img-fluid" src="../resources/img/inswitch.png"></a></h3>
            <strong><img id="stronimg" src="../resources/img/inswitch.png"></strong>
        </div>

        <ul class="list-unstyled components">
            <span class="menu-span">Menu</span>
            <li>
                <a href="#"><i class="fas fa-home"></i>Home</a>
            </li>
        </ul>

    </nav>

    <div class="content">

        <nav class="navbar navbar-expand-lg navbar-light py-1 py-md-1" id="navbarSupportedContent">
            <a href="index.php" class="navbar-brand">
                <div class="brand-text d-none d-md-inline-block">
                    <span>SAMLPhp </span>
                    <strong class="text-secondary">demo</strong>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div id="navbarNavDropdown" class="navbar-collapse collapse">
                <ul class="navbar-nav mr-auto">

<!--                <li class="nav-item active">
                        <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
                    </li>-->

                </ul>
                <ul class="navbar-nav">

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user dropdown-icon"></i>'.$_GET['username'].'</a>
                        <ul class="dropdown-menu dropdown-menu-right dropdown-navbar" aria-labelledby="navbarDropdownMenuLink">
                            <a class="dropdown-item" href="#">Action</a>
                            <a class="dropdown-item" href="#">Another action</a>
                            <a class="dropdown-item" href="'.\SimpleSAML\Utils\HTTP::addURLParameters('http://localhost/SAMLDEMO/www/pages/logout.php',$_GET).'">Logout</a>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Some page</li>
            </ol>
        </nav>

        <!-- Page Content  -->
        <div id="content">
            <div class="card">

                <div class="card-header pb-0">
                    <h3 class="page-title">
Detalles <small>de la autenticacion</small>
                        <a class="help-button fas fa-question-circle" style="float: right"></a>
                    </h3>
                </div>
                <div class="card-body">
                    <h2>User Data</h2>
                    <p><strong class="text-secondary">User: </strong>'.(isset($_GET['username'])?$_GET['username']:'Sin user').'</p>
                    <p><strong class="text-secondary">Pass: </strong>'.(isset($_GET['password'])?$_GET['password']:'Sin pass').'</p>
                    <p><strong class="text-secondary">IDP: </strong>'.(isset($_GET['idp'])?$_GET['idp']:'Sin idp').'</p>
                    <p><strong class="text-secondary">SP: </strong>'.(isset($_GET['sp'])?$_GET['sp']:'Sin sp').'</p>


                    <div class="line"></div>

                    <h2>Atribute</h2>';
//                    foreach (base64_decode($_GET['a']) as $a){
//    echo '<p>'.var_dump($_GET['state']['Attributes']).'</p>';
    echo '<p>'.var_dump($_GET['authdata']).'</p>';
//                    }
    echo '


                    <div class="line"></div>

                    <h2>'.(isset($_GET['idp'])?$_GET['idp']:'Sin idp').'</h2>
                    <p>El Identity Provider de la autenticacion.</p>

                    <h2>'.(isset($_GET['sp'])?$_GET['sp']:'Sin sp').'</h2>
                    <p>El Service Provider de la autenticacion.</p>


                    <div class="line"></div>
                </div>
            </div>

        </div>
    <!-- Footer  -->
<footer id="footer" class="bg-tiffanyblue">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>INSwitch © SAMLPhp demo 2019</span>
        </div>
    </div>
</footer>
    </div>
</div>
<script src="../resources/js/jquery-3.4.1.min.js"></script>
<script src="../resources/js/bootstrap.min.js"></script>
</body>
</html>';
}else{
    HTTP::redirectTrustedURL('http://localhost/SAMLDEMO/www/pages/login.php');
}