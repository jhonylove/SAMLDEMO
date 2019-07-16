<?php
/**
 * Created by: Ing.Juan Carlos Casadesús Rades
 * Date: 12/07/19
 * Time: 12:27 PM
 */

namespace Auth\Custom;

use \SimpleSAML\Configuration;
use \SimpleSAML\Error\AuthSource as AuthSourceError;
use \SimpleSAML\Module;
use \SimpleSAML\Session;
use \SimpleSAML\Utils\HTTP;
use \SimpleSAML\Auth\State;


class SimpleCustom extends \SimpleSAML\Auth\Simple

{
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

        $as = $this->getAuthSource();

        return $as->initLogin($returnTo, $errorURL, $params);
//        var_dump($RETURN);die;
//        assert(false);
    }

    /**
     * Retrieve the implementing authentication source.
     *
     * @return Source The authentication source.
     *
     * @throws AuthSourceError If the requested auth source is unknown.
     */
    public function getAuthSource()
    {
        $as = SAMLHandler::getById($this->authSource);
        if ($as === null) {
            throw new AuthSourceError($this->authSource, 'Unknown authentication source.');
        }
        return $as;
    }

    /**
     * Log the user out.
     *
     * This function logs the user out. It will never return. By default, it will cause a redirect to the current page
     * after logging the user out, but a different URL can be given with the $params parameter.
     *
     * Generic parameters are:
     *  - 'ReturnTo': The URL the user should be returned to after logout.
     *  - 'ReturnCallback': The function that should be called after logout.
     *  - 'ReturnStateParam': The parameter we should return the state in when redirecting.
     *  - 'ReturnStateStage': The stage the state array should be saved with.
     *
     * @param string|array|null $params Either the URL the user should be redirected to after logging out, or an array
     * with parameters for the logout. If this parameter is null, we will return to the current page.
     */
    public function logout($params = null)
    {
        assert(is_array($params) || is_string($params) || $params === null);

        if ($params === null) {
            $params = HTTP::getSelfURL();
        }

        if (is_string($params)) {
            $params = [
                'ReturnTo' => $params,
            ];
        }

        assert(is_array($params));
        assert(isset($params['ReturnTo']) || isset($params['ReturnCallback']));

        if (isset($params['ReturnStateParam']) || isset($params['ReturnStateStage'])) {
            assert(isset($params['ReturnStateParam'], $params['ReturnStateStage']));
        }

        if ($this->session->isValid($this->authSource)) {
            $state = $this->session->getAuthData($this->authSource, 'LogoutState');
            if ($state !== null) {
                $params = array_merge($state, $params);
            }

            $this->session->doLogout($this->authSource);

            $params['LogoutCompletedHandler'] = [get_class(), 'logoutCompleted'];

            $as = SAMLHandler::getById($this->authSource);
            if ($as !== null) {
                $as->logout($params);
            }
        }

        self::logoutCompleted($params);
    }


    /**
     * Called when logout operation completes.
     *
     * This function never returns.
     *
     * @param array $state The state after the logout.
     */
    public static function logoutCompleted($state)
    {
        assert(is_array($state));
        assert(isset($state['ReturnTo']) || isset($state['ReturnCallback']));

        if (isset($state['ReturnCallback'])) {
            call_user_func($state['ReturnCallback'], $state);
            assert(false);
        } else {
            $params = [];
            if (isset($state['ReturnStateParam']) || isset($state['ReturnStateStage'])) {
                assert(isset($state['ReturnStateParam'], $state['ReturnStateStage']));
                $stateID = State::saveState($state, $state['ReturnStateStage']);
                $params[$state['ReturnStateParam']] = $stateID;
            }
            HTTP::redirectTrustedURL($state['ReturnTo'], $params);
        }
    }
}