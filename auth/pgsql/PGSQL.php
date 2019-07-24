<?php
/**
 * Created by PhpStorm.
 * User: jhony
 * Date: 17/07/19
 * Time: 05:38 PM
 */

namespace Auth\Pgsql;


use SimpleSAML\Auth\State;
use SimpleSAML\Module\sqlauth\Auth\Source\SQL;

class PGSQL extends SQL
{

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
            '\SimpleSAML\Auth\DefaultAuth.id' => $this->authId, // TODO: remove in 2.0
            '\SimpleSAML\Auth\Source.id' => $this->authId,
            '\SimpleSAML\Auth\DefaultAuth.Return' => $return, // TODO: remove in 2.0
            '\SimpleSAML\Auth\Source.Return' => $return,
            '\SimpleSAML\Auth\DefaultAuth.ErrorURL' => $errorURL, // TODO: remove in 2.0
            '\SimpleSAML\Auth\Source.ErrorURL' => $errorURL,
            'LoginCompletedHandler' => [get_class(), 'loginCompleted'],
            'LogoutCallback' => [get_class(), 'logoutCallback'],
            'LogoutCallbackState' => [
                '\SimpleSAML\Auth\DefaultAuth.logoutSource' => $this->authId, // TODO: remove in 2.0
                '\SimpleSAML\Auth\Source.logoutSource' => $this->authId,
            ],
        ]);

        if (is_string($return)) {
            $state['\SimpleSAML\Auth\DefaultAuth.ReturnURL'] = $return; // TODO: remove in 2.0
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

    public function authenticate(&$state)
    {
        assert(is_array($state));

        $username = $_POST['username'];
        $password = $_POST['password'];
        $attributes = $this->login($username, $password);
        if ($attributes !== null) {
            $state['myauthinstance:AuthID'] = self::AUTHID;
            $state['returnTo'] = 'http://localhost/SAMLDEMO/www/pages/index.php';

            /*
             * The user is already authenticated.
             *
             * Add the users attributes to the $state-array, and return control
             * to the authentication process.
             */
            $stateId = \SimpleSAML\Auth\State::saveState($state, 'myauthinstance:MyAuth');
            $parameters=$_REQUEST;
            $returnTo = 'http://localhost/SAMLDEMO/www/pages/index.php';
            $parameters['stateId']=$stateId;
            $returnTo = \SimpleSAML\Utils\HTTP::addURLParameters($returnTo, $parameters);

            $state['Attributes'] = $attributes;
            $state['\SimpleSAML\Auth\Source.Return'] = $returnTo;
            \SimpleSAML\Auth\State::saveState($state, 'myauthinstance:MyAuth');


            return;
        }

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
    }

}