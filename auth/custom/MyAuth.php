<?php
namespace Auth\Custom;
use Auth\Custom\SAMLHandler;

    /**
 * Created by: Ing.Juan Carlos Casadesús Rades
 * Date: 15/07/19
 * Time: 10:29 AM
 */
//class MyAuth extends \SimpleSAML\Module\core\Auth\UserPassBase {
class MyAuth extends SAMLHandler{

    /* The database DSN.
     * See the documentation for the various database drivers for information about the syntax:
     *     http://www.php.net/manual/en/pdo.drivers.php
     */
    private $dsn;

    /* The database username, password & options. */
    private $username;
    private $password;
    private $options;

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
            throw new \SimpleSAML\Error\Error('WRONGUSERPASS');
        }

        /* Check the password. */
        if (!$this->checkPassword($row['password'], $password)) {
            /* Invalid password. */
            \SimpleSAML\Logger::warning('MyAuth: Wrong password for user ' . var_export($username, TRUE) . '.');
            throw new \SimpleSAML\Error\Error('WRONGUSERPASS');
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