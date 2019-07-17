<?php
require_once ('../../autoload.php');
use \Controllers\loginController;
use SimpleSAML\Utils\HTTP;

$controller= new loginController();
if (isset($_POST['sub'])) {
    $controller->login($_POST);
}
if($controller->checklogin('myauthinstance')) {
    HTTP::redirectTrustedURL('http://localhost/SAMLDEMO/www/pages/index.php',$_POST);
}
{
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../resources/css/login.css">
    <link rel="shortcut icon" href="../resources/img/favicon.ico">
    <script src="../resources/js/jquery-3.4.1.min.js"></script>
    <!-- Popper.JS -->
    <!-- Bootstrap JS -->
    <script src="../resources/js/bootstrap.min.js"></script>

</head>
<body>
<div class="login-form">

    <form action=""  method="post">
        <h3><a><img id="img-log" class="logo" src="../resources/img/inswitch.png"></a></h3>

        <h2 class="text-center" style="color: white">SAMLPhp demo</h2>';
    if (isset($_GET['msg'])) {
        echo '<div class="alert alert-danger" role="alert">
            ' . $_GET['msg'] . '
    </div>';
    }
    echo '     
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text" id="user"><i class="fas fa-user"></i></span>
            </div>
            <input type="text" class="form-control" name="username" placeholder="Username" aria-label="Username" aria-describedby="user" required>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text" id="pass"><i class="fas fa-key"></i></span>
            </div>
            <input type="password" class="form-control" name="password" placeholder="Password" aria-label="Password" aria-describedby="pass" required>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text" id="pass"><i class="fas fa-folder"></i></span>
            </div>
            <select name="idp" class="form-control" id="exampleFormControlSelect1" required>
              <option>myauthinstance</option>
              <option>default-sp</option>
              <option>example-oci8</option>
              <option>example-pgsql</option>
              <option>example</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-success" name="sub" value="loginAction">Log in</button>
            <button type="reset" class="btn btn-danger">Reset</button>
        </div>
    </form>
</div>
</body>
</html>';
}