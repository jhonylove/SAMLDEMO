<?php
/**
 * Created by PhpStorm.
 * User: jhony
 * Date: 16/07/19
 * Time: 02:57 PM
 */

namespace Controllers;
use Auth\Custom\SimpleCustom;
use SimpleSAML\Configuration;
use SimpleSAML\Utils\HTTP;


class loginController
{

    private $config;

    private $configpath=ROOT.DS.'config';
    /**
     * loginController constructor.
     */
    public function __construct()
    {
        $c=new Configuration([],'');
        $c->setConfigDir($this->configpath);
        $this->config=$c;
    }

    function login($request){
            $as = new SimpleCustom('myauthinstance',$this->config);

//        $as = new SimpleCustom($_POST['idp'],$c);
        $user=$_POST['username'];
        $pass=$_POST['password'];
        $idp=$_POST['idp'];
        $sp=$_POST['sp'];
        $params=['ErrorURL'=>'http://localhost/SAMLDEMO/www/pages/login.php?&msg=badcredentials',
                     'ReturnTo'=>'http://localhost/SAML/smplphpdemo/index.php?user='.$user.'&pass='.$pass.'&idp='.$idp.'&sp='.$sp,
//            'ReturnTo'=>'http://localhost/SAMLDEMO/smplphpdemo/controllers/loginHandler.php',
                 'KeepPost' => true];
//            $as->requireAuth($params);
        $state=$as->login($params);
        $_POST['state']=$state;
        HTTP::redirectTrustedURL('http://localhost/SAMLDEMO/www/pages/index.php',$_POST);

    }

    function checklogin($idsource){
        $auth = new SimpleCustom($idsource,$this->config);
        if (!$auth->isAuthenticated()) {
            return false;
        }
        return true;


    }

    /**
     * @return Configuration
     */
    public function logout($request)
    {
        return $this->config;
    }

}