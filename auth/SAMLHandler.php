<?php
/**
 * Created by: Ing.Juan Carlos Casadesús Rades
 * Date: 12/07/19
 * Time: 12:27 PM
 */

namespace Auth;

use Auth\Custom\SimpleCustom;
use Auth\Facebook\Facebook;
use Auth\Google\Google;
use Auth\Ldap\LDAP;
use Auth\Oci8\oci8;
use Auth\Pgsql\PGSQL;
use SimpleSAML\Auth\Source;
use \SimpleSAML\Configuration;
use \SimpleSAML\Module;
use \SimpleSAML\Session;
use \SimpleSAML\Utils\HTTP;


class SAMLHandler extends \SimpleSAML\Auth\Simple

{

    protected $config;

    /**
     * SimpleCustom constructor.
     * @param $config
     */
    public function __construct($authSource, Configuration $config = null, Session $session = null)
    {
        parent::__construct($authSource, $config, $session);

        $this->config = $config;
    }

    /**
     * Start an authentication process.
     *
     * This function accepts an array $params, which controls some parts of the authentication. The accepted parameters
     * depends on the authentication source being used. Some parameters are generic:
     *  - 'ErrorURL': A URL that should receive errors from the authentication.
     *  - 'KeepPost': If the current request is a POST request, keep the POST data until after the authentication.
     *  - 'ReturnTo': The URL the user should be returned to after authentication.
     *  - 'ReturnCallback': The function we should call after the user has finished authentication.
     *
     * Please note: this function never returns.
     *
     * @param array $params Various options to the authentication request.
     */
    public function login(array $params = [])
    {

        if (array_key_exists('KeepPost', $params)) {
            $keepPost = (bool) $params['KeepPost'];
        } else {
            $keepPost = true;
        }

        if (array_key_exists('ReturnTo', $params)) {
            $returnTo = (string) $params['ReturnTo'];
        } else {
            if (array_key_exists('ReturnCallback', $params)) {
                $returnTo = (array) $params['ReturnCallback'];
            } else {
                $returnTo = HTTP::getSelfURL();
            }
        }

        if (is_string($returnTo) && $keepPost && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnTo = HTTP::getPOSTRedirectURL($returnTo, $_POST);
        }

        if (array_key_exists('ErrorURL', $params)) {
            $errorURL = (string) $params['ErrorURL'];
        } else {
            $errorURL = null;
        }
        $source_config=$this->config->getConfig('authsources.php')->getValue($this->authSource);

        if(isset($source_config['handler'])){
            $handler_auth=$this->getCustomHandler($source_config['handler'],$source_config);
            return $handler_auth->initLogin($returnTo, $errorURL, $params);
        }else{
            $auth = $this->getAuthSource();
            return $auth->initLogin($returnTo, $errorURL, $params);
        }
//        assert(false);
    }

    /**
     * @return Source
     */
    public function getCustomHandler($handlername,$config)
    {
        $handler=null;
        switch ($handlername){
            case 'saml':
                $handler= new SimpleCustom(array('AuthId'=>$this->authSource),$config);
                break;
            case 'facebook':
                $handler= new Facebook(array('AuthId'=>$this->authSource),$config);
                break;
            case 'ldap':
                $handler= new LDAP(array('AuthId'=>$this->authSource),$config);
                break;
            case 'google':
                $handler= new Google(array('AuthId'=>$this->authSource),$config);
                break;
            case 'oci8':
                $handler= new oci8(array('AuthId'=>$this->authSource),$config);
                break;
            case 'pqsql':
                $handler= new PGSQL(array('AuthId'=>$this->authSource),$config);
                break;
            default:
                $handler = $this->getAuthSource();
                break;
        }
        return $handler;

    }
}