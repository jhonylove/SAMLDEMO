<?php
/**
 * Created by PhpStorm.
 * User: jhony
 * Date: 19/07/19
 * Time: 12:44 PM
 */

namespace Auth\Facebook;
use SimpleSAML\Auth\State;
use SimpleSAML\Module;
use \SimpleSAML\Module\authfacebook\Auth\Source\Facebook as FacebookHandler;
use SimpleSAML\Module\authfacebook\Facebook as Face;


class Facebook extends FacebookHandler
{
    /**
     * The string used to identify our states.
     */
    const STAGE_INIT = 'facebook:init';


    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = 'facebook:AuthId';


    /**
     * Facebook App ID or API Key
     */
    private $api_key;


    /**
     * Facebook App Secret
     */
    private $secret;


    /**
     * Which additional data permissions to request from user
     */
    private $req_perms;


    /**
     * A comma-separated list of user profile fields to request.
     *
     * Note that some user fields require appropriate permissions. For
     * example, to retrieve the user's primary email address, "email" must
     * be specified in both the req_perms and the user_fields parameter.
     *
     * When empty, only the app-specific user id and name will be returned.
     *
     * See the Graph API specification for all available user fields:
     * https://developers.facebook.com/docs/graph-api/reference/v2.6/user
     */
    private $user_fields;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct($info, $config)
    {
        assert(is_array($info));
        assert(is_array($config));

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $cfgParse = \SimpleSAML\Configuration::loadFromArray(
            $config,
            'authsources['.var_export($this->authId, true).']'
        );

        $this->api_key = $cfgParse->getString('api_key');
        $this->secret = $cfgParse->getString('secret');
        $this->req_perms = $cfgParse->getString('req_perms', null);
        $this->user_fields = $cfgParse->getString('user_fields', null);
    }


    /**
     * Log-in using Facebook platform
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(&$state)
    {
        assert(is_array($state));

        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;
        \SimpleSAML\Auth\State::saveState($state, self::STAGE_INIT);

        $facebook = new Face(
            ['appId' => $this->api_key, 'secret' => $this->secret],
            $state
        );
        $facebook->destroySession();

//        $linkback = Module::getModuleURL('authfacebook/linkback.php');
        $linkback = 'http://localhost/SAMLDEMO/www/pages/linkback.php';
        $url = $facebook->getLoginUrl(['redirect_uri' => $linkback, 'scope' => $this->req_perms]);
        \SimpleSAML\Auth\State::saveState($state, self::STAGE_INIT);

        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
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
        $state=array_merge($state,$_POST);



        if (is_string($return)) {
            $state['\SimpleSAML\Auth\Source.ReturnURL'] = $return;
        }

        if ($errorURL !== null) {
            $state[State::EXCEPTION_HANDLER_URL] = $errorURL;
        }

        try {
            $this->authenticate($state);
        } catch (\SimpleSAML\Error\Exception $e) {
            State::throwException($state, $e);
        } catch (\Exception $e) {
            $e = new \SimpleSAML\Error\UnserializableException($e);
            State::throwException($state, $e);
        }
        self::loginCompleted($state);
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

        if (is_string($return)) {
            // redirect...
            \SimpleSAML\Utils\HTTP::redirectTrustedURL($return);
        } else {
            call_user_func($return, $state);
        }
        assert(false);
    }

    public function finalStep(&$state)
    {
        assert(is_array($state));

        $facebook = new Face(
            ['appId' => $this->api_key, 'secret' => $this->secret],
            $state
        );
        $uid = $facebook->getUser();

        if (isset($uid) && $uid) {
            try {
                $info = $facebook->api("/".$uid.($this->user_fields ? "?fields=".$this->user_fields : ""));
            } catch (\FacebookApiException $e) {
                throw new \SimpleSAML\Error\AuthSource($this->authId, 'Error getting user profile.', $e);
            }
        }

        if (!isset($info)) {
            throw new \SimpleSAML\Error\AuthSource($this->authId, 'Error getting user profile.');
        }

        $attributes = [];
        foreach ($info as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $attributes['facebook.'.$key] = [(string) $value];
            }
        }

        if (array_key_exists('third_party_id', $info)) {
            $attributes['facebook_user'] = [$info['third_party_id'].'@facebook.com'];
        } else {
            $attributes['facebook_user'] = [$uid.'@facebook.com'];
        }

        $attributes['facebook_targetedID'] = ['http://facebook.com!'.$uid];
        $attributes['facebook_cn'] = [$info['name']];

        \SimpleSAML\Logger::debug('Facebook Returned Attributes: '.implode(", ", array_keys($attributes)));

        $state['Attributes'] = $attributes;

        $facebook->destroySession();
    }

}