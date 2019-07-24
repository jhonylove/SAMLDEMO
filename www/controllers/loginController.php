<?php
/**
 * Created by PhpStorm.
 * User: jhony
 * Date: 16/07/19
 * Time: 02:57 PM
 */

namespace Controllers;
use Auth\Custom\SimpleCustom;
use Auth\SAMLHandler;
use SimpleSAML\Configuration;
use SimpleSAML\Utils\HTTP;


class loginController
{

    private $config;

    private $configpath=ROOT.DS.'config';

    private $authsource;
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
//            $as = new SimpleCustom('myauthinstance',$this->config);
        $this->authsource=$_POST['idp'];

        $as = new SAMLHandler($this->authsource,$this->config);
        $user=$_POST['username'];
        $pass=$_POST['password'];
        $idp=$_POST['idp'];
//        $sp=$_POST['sp'];
        $params=['ErrorURL'=>'http://localhost/SAMLDEMO/www/pages/login.php?&msg=badcredentials',
                     'ReturnTo'=>'http://localhost/SAMLDEMO/www/pages/index.php?username='.$user.'&password='.$pass.'&idp='.$idp,
//            'ReturnTo'=>'http://localhost/SAMLDEMO/smplphpdemo/controllers/loginHandler.php',
                 'KeepPost' => true];
//            $as->requireAuth($params);
//        var_dump($as);die;
        $state=$as->login($params);
        $authdata=$as->getAuthDataArray();
        $_POST['state']=$state;
        $_POST['authdata']=$authdata;
        HTTP::redirectTrustedURL('http://localhost/SAMLDEMO/www/pages/index.php',$_POST);

    }

    function checklogin($idsource){
        $auth = new SAMLHandler($idsource,$this->config);
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
        $auth = new SAMLHandler($request['idp'],$this->config);
        $auth->logout('http://localhost/SAMLDEMO/www/pages/login.php');
        return $this->config;
    }

}