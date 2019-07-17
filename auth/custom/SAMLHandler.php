<?php

/**
 * Created by PhpStorm.
 * User: jhony
 * Date: 03/07/19
 * Time: 03:16 PM
 */
namespace Auth\Custom;
use SimpleSAML\Auth\State;
use SimpleSAML\Utils\HTTP;

class SAMLHandler extends \SimpleSAML\Auth\Source

{

    /* The database DSN.
 * See the documentation for the various database drivers for information about the syntax:
 *     http://www.php.net/manual/en/pdo.drivers.php
 */
    private $dsn;

    /* The database username, password & options. */
    private $username;
    private $password;
    private $options;

    /**
     * The string used to identify our states.
     */
    const STAGEID = '\SimpleSAML\Module\mymodule\Auth\Source\MyAuth.state';

    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = '\SimpleSAML\Module\mymodule\Auth\Source\MyAuth.AuthId';

    /**
     * Links to pages from login page.
     * From configuration
     */
    protected $loginLinks;

    /**
     * Storage for authsource config option remember.username.enabled
     * loginuserpass.php and loginuserpassorg.php pages/templates use this option to
     * present users with a checkbox to save their username for the next login request.
     * @var bool
     */
    protected $rememberUsernameEnabled = false;

    /**
     * Storage for authsource config option remember.username.checked
     * loginuserpass.php and loginuserpassorg.php pages/templates use this option
     * to default the remember username checkbox to checked or not.
     * @var bool
     */
    protected $rememberUsernameChecked = false;

    /**
     * Storage for general config option session.rememberme.enable.
     * loginuserpass.php page/template uses this option to present
     * users with a checkbox to keep their session alive across
     * different browser sessions (that is, closing and opening the
     * browser again).
     * @var bool
     */
    protected $rememberMeEnabled = false;

    /**
     * Storage for general config option session.rememberme.checked.
     * loginuserpass.php page/template uses this option to default
     * the "remember me" checkbox to checked or not.
     * @var bool
     */
    protected $rememberMeChecked = false;

    public function __construct($info, $config) {
        parent::__construct($info, $config);

        if (!is_string($config['dsn'])) {
            throw new Exception('Missing or invalid dsn option in config.');
        }
        $this->dsn = $config['dsn'];
        if (!is_string($config['username'])) {
            throw new Exception('Missing or invalid username option in config.');
        }
        $this->username = $config['username'];
        if (!is_string($config['password'])) {
            throw new Exception('Missing or invalid password option in config.');
        }
        $this->password = $config['password'];
        if (isset($config['options'])) {
            if (!is_array($config['options'])) {
                throw new Exception('Missing or invalid options option in config.');
            }
            $this->options = $config['options'];
        }
    }

    /**
     * A helper function for validating a password hash.
     *
     * In this example we check a SSHA-password, where the database
     * contains a base64 encoded byte string, where the first 20 bytes
     * from the byte string is the SHA1 sum, and the remaining bytes is
     * the salt.
     */
//    private function checkPassword($passwordHash, $password) {
//        $passwordHash = base64_decode($passwordHash);
//        $digest = substr($passwordHash, 0, 20);
//        $salt = substr($passwordHash, 20);
//
//        $checkDigest = sha1($password . $salt, TRUE);
//        return $digest === $checkDigest;
//    }
    private function checkPassword($passwordHash, $password) {
        return $passwordHash === $password;
    }

    protected function login($username, $password) {
        /* Connect to the database. */

        $db = new \PDO($this->dsn, $this->username, $this->password, $this->options);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        /* Ensure that we are operating with UTF-8 encoding.
         * This command is for MySQL. Other databases may need different commands.
         */
        $db->exec("SET NAMES 'utf8'");

        /* With PDO we use prepared statements. This saves us from having to escape
         * the username in the database query.
         */
//        $st = $db->prepare('SELECT username, password_hash, full_name FROM userdb WHERE username=:username');
        $st = $db->prepare('SELECT username,password, name, email FROM users WHERE username = :username');


        if (!$st->execute(['username' => $username])) {
            throw new Exception('Failed to query database for user.');
        }

        /* Retrieve the row from the database. */
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            /* User not found. */
            \SimpleSAML\Logger::warning('MyAuth: Could not find user ' . var_export($username, TRUE) . '.');
            HTTP::redirectTrustedURL('http://localhost/SAMLDEMO/www/pages/login.php?&msg=badcredentials',$_POST);

//            throw new \SimpleSAML\Error\Error('WRONGUSERPASS');
        }

        /* Check the password. */
        if (!$this->checkPassword($row['password'], $password)) {
            /* Invalid password. */
            \SimpleSAML\Logger::warning('MyAuth: Wrong password for user ' . var_export($username, TRUE) . '.');
            HTTP::redirectTrustedURL('http://localhost/SAMLDEMO/www/pages/login.php?&msg=badcredentials',$_POST);

//            throw new \SimpleSAML\Error\Error('WRONGUSERPASS');
        }

        /* Create the attribute array of the user. */
        $attributes = [
            'uid' => [$username],
            'displayName' => [$row['name']],
            'eduPersonAffiliation' => ['member', 'employee'],
        ];

        /* Return the attributes. */
        return $attributes;
    }
    
    public function authenticate(&$state)
    {
        assert(is_array($state));

        $username = $_POST['username'];
        $password = $_POST['password'];
        $attributes = $this->login($username, $password);
        if ($attributes !== null) {
            $state['myauthinstance:AuthID'] = self::AUTHID;
            $state['returnTo'] = 'http://localhost/SAML/smplphpdemo/index.php';

            /*
             * The user is already authenticated.
             *
             * Add the users attributes to the $state-array, and return control
             * to the authentication process.
             */
            $stateId = \SimpleSAML\Auth\State::saveState($state, 'myauthinstance:MyAuth');
            $parameters=$_REQUEST;
            $returnTo = 'http://localhost/SAML/smplphpdemo/controllers/loginHandler.php';
            $parameters['stateId']=$stateId;
            $returnTo = \SimpleSAML\Utils\HTTP::addURLParameters($returnTo, $parameters);

            $state['Attributes'] = $attributes;
            $state['\SimpleSAML\Auth\Source.Return'] = $returnTo;
            \SimpleSAML\Auth\State::saveState($state, 'myauthinstance:MyAuth');


            return;
        }

    }
    /**
     * Start authentication.
     *
     * This method never returns.
     *
     * @param string|array $return The URL or function we should direct the user to after authentication. If using a
     * URL obtained from user input, please make sure to check it by calling \SimpleSAML\Utils\HTTP::checkURLAllowed().
     * @param string|null $errorURL The URL we should direct the user to after failed authentication. Can be null, in
     * which case a standard error page will be shown. If using a URL obtained from user input, please make sure to
     * check it by calling \SimpleSAML\Utils\HTTP::checkURLAllowed().
     * @param array $params Extra information about the login. Different authentication requestors may provide different
     * information. Optional, will default to an empty array.
     */
    public function initLogin($return, $errorURL = null, array $params = [])
    {
        assert(is_string($return) || is_array($return));
        assert(is_string($errorURL) || $errorURL === null);

        $state = array_merge($params, [
            '\SimpleSAML\Auth\Source.id' => $this->authId,
            '\SimpleSAML\Auth\Source.Return' => $return,
            '\SimpleSAML\Auth\Source.ErrorURL' => $errorURL,
            'LoginCompletedHandler' => [get_class(), 'loginCompleted'],
            'LogoutCallback' => [get_class(), 'logoutCallback'],
            'LogoutCallbackState' => [
                '\SimpleSAML\Auth\Source.logoutSource' => $this->authId,
            ],
        ]);

        try {
            $this->authenticate($state);
        } catch (\SimpleSAML\Error\Exception $e) {
            State::throwException($state, $e);
        } catch (\Exception $e) {
            $e = new \SimpleSAML\Error\UnserializableException($e);
            State::throwException($state, $e);
        }
        self::loginCompleted($state);
        return $state;
    }

    /**
     * Called when a login operation has finished.
     *
     * This method never returns.
     *
     * @param array $state The state after the login has completed.
     */
    public static function loginCompleted($state)
    {
        assert(is_array($state));
        assert(array_key_exists('\SimpleSAML\Auth\Source.Return', $state));
        assert(array_key_exists('\SimpleSAML\Auth\Source.id', $state));
        assert(array_key_exists('Attributes', $state));
        assert(!array_key_exists('LogoutState', $state) || is_array($state['LogoutState']));

        $return = $state['\SimpleSAML\Auth\Source.Return'];


        // save session state
        $session = \SimpleSAML\Session::getSessionFromRequest();
        $authId = $state['\SimpleSAML\Auth\Source.id'];
        $session->doLogin($authId, State::getPersistentAuthData($state));

//        if (is_string($return)) {
//            // redirect...
//            \SimpleSAML\Utils\HTTP::redirectTrustedURL($return);
//        } else {
//            call_user_func($return, $state);
//        }
//        assert(false);
    }

    /**
     * This function is called when the user start a logout operation, for example
     * by logging out of a SP that supports single logout.
     *
     * @param array &$state  The logout state array.
     */
    public function logout(&$state)
    {
        assert(is_array($state));

        if (!session_id()) {
            // session_start not called before. Do it here
            session_start();
        }

        /*
         * In this example we simply remove the 'uid' from the session.
         */
        unset($_SESSION['uid']);

        /*
         * If we need to do a redirect to a different page, we could do this
         * here, but in this example we don't need to do this.
         */
    }

    /**
     * @return string
     */
    public function get_dsn()
    {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function get_password()
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function get_options()
    {
        return $this->options;
    }

}