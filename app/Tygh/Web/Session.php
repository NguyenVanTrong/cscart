<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/


namespace Tygh\Web;

use Tygh\Application;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tools\SecurityHelper;
use Tygh\Backend\Session\ABackend;

/**
 * Class Session is a component used to handle accessing session data and saving it to persistent storage.
 *
 * @package Tygh\Web
 */
class Session implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var \Tygh\Application
     */
    protected $app;

    /**
     * @var bool Whether to start session at the component initialization time. Defaults to false.
     */
    public $start_on_init = false;

    /**
     * @var bool Whether to start session at the first read access to session data. Defaults to false.
     */
    public $start_on_read = false;

    /**
     * @var bool Whether to start session at the first write access to session data. Defaults to false.
     */
    public $start_on_write = false;

    /**
     * @var string Classname of session storage driver.
     */
    public $storage_class = '\Tygh\Backend\Session\Database';

    /**
     * @var string|null Prefix added to the session name.
     */
    protected $session_name_prefix;

    /**
     * @var string|null Suffix added to the session name.
     */
    protected $session_name_suffix;

    /**
     * @var string|null Suffix added to the session ID.
     */
    protected $session_id_suffix;

    /**
     * @var ABackend Instance of session data storage driver.
     */
    protected $storage_driver;

    /**
     * @var string
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-path
     */
    public $cookie_path = '/';

    /**
     * @var string
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-domain
     */
    public $cookie_domain = '';

    /**
     * @var int
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-lifetime
     */
    public $cookie_lifetime = 0;

    /**
     * @var bool
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-secure
     */
    public $cookie_secure = false;

    /**
     * @var bool
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-httponly
     */
    public $cookie_httponly = true;

    /**
     * @var int
     * @see http://php.net/manual/en/session.configuration.php#ini.session.gc-probability
     */
    public $gc_probability = 1;

    /**
     * @var int
     * @see http://php.net/manual/en/session.configuration.php#ini.session.gc-divisor
     */
    public $gc_divisor = 10;

    /**
     * @var string
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cache-limiter
     */
    public $cache_limiter = 'nocache';

    /**
     * @var int
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cache-expire
     */
    public $cache_expire = 180;

    /**
     * Session constructor.
     *
     * @param \Tygh\Application $application Application IoC container instance.
     */
    public function __construct(Application $application)
    {
        $this->app = $application;

        // Define the superglobal, because if session will not start,
        // third-party add-ons that use $_SESSION directly will catch PHP warnings
        $_SESSION = array();

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Initializes the component.
     * Starts the session if {{$start_on_init}} parameter is enabled.
     *
     * @return void
     */
    public function init()
    {
        if ($this->start_on_init) {
            $this->start();
        }
    }

    /**
     * @return null|string Prefix added to the session name
     */
    public function getSessionNamePrefix()
    {
        return $this->session_name_prefix;
    }

    /**
     * @param null|string $session_name_prefix Prefix added to the session name
     */
    public function setSessionNamePrefix($session_name_prefix)
    {
        $this->session_name_prefix = $session_name_prefix;
    }

    /**
     * @return null|string Suffix added to the session name
     */
    public function getSessionNameSuffix()
    {
        return $this->session_name_suffix;
    }

    /**
     * @param null|string $session_name_suffix Suffix added to the session name
     */
    public function setSessionNameSuffix($session_name_suffix)
    {
        $this->session_name_suffix = $session_name_suffix;
    }

    /**
     * @return null|string
     */
    public function getSessionIDSuffix()
    {
        return $this->session_id_suffix;
    }

    /**
     * @param null|string $session_id_suffix
     */
    public function setSessionIDSuffix($session_id_suffix)
    {
        $this->session_id_suffix = $session_id_suffix;
    }

    /**
     * @return bool Whether the session is active.
     */
    public function isStarted()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        return session_id() !== '';
    }

    /**
     * Returns session name. A wrapper for session_name().
     *
     * @return string Session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Sets new session name. A wrapper for session_name().
     *
     * @param string $name New session name
     */
    public function setName($name)
    {
        /**
         * Actions before setting session name
         *
         * @param string $account_type Current account type
         * @param string $sess_postfix Session postfix
         */
        fn_set_hook('session_set_name_before', $name, $this->session_name_suffix);

        session_name($this->session_name_prefix . $name . $this->session_name_suffix);
    }

    /**
     * Sets new session ID. A wrapper for session_id().
     *
     * @param string $id New session ID
     *
     * @return mixed session_id() call result
     */
    public function setID($id)
    {
        return session_id($id);
    }

    /**
     * Gets the session ID. A wrapper for session_id().
     *
     * @return string Current session ID
     */
    public function getID()
    {
        return session_id();
    }

    /**
     * Runtime shutdown handler. Used to close session.
     */
    public function shutdown()
    {
        // We don't need to register shutdown function if it is an AJAX request,
        // because the AJAX request session manipulations are done in ob_handler.
        // AJAX ob_handlers are lauched AFTER session_close so all session changes by AJAX
        // will be unsaved, so we call session_write_close() directly in our AJAX ob_handler.
        if (!defined('AJAX_REQUEST')) {
            if ($this->isStarted()) {
                session_write_close();
            }
        }
    }

    /**
     * @return bool Whether HTTP request contains the session ID.
     */
    public function requestHasSessionID()
    {
        return $this->requestGetSessionID() !== null;
    }

    /**
     * @return string|null Session identifier contained in HTTP request, null if request doesn't contain session ID.
     */
    public function requestGetSessionID()
    {
        return isset($_REQUEST[$this->getName()])
            ? $_REQUEST[$this->getName()]
            : (
                isset($_COOKIE[$this->getName()])
                    ? $_COOKIE[$this->getName()]
                    : null
            );
    }

    /**
     * Sets up session parameters, handlers, storage driver and starts session.
     * If HTTP request doesn't contain session ID, it will be generated.
     *
     * @return bool Whether session has started
     */
    public function start()
    {
        if ($this->isStarted()) {
            return false;
        }

        if ($this->requestHasSessionID()) {
            $id = $this->requestGetSessionID();
        } else {
            $id = $this->addSuffixToSessionID(
                $this->generateSessionID()
            );
        }

        $this->setID($id);
        $this->setSessionParams();
        $this->initDataStorageDriver();
        if ($this->storage_driver instanceof ABackend) {
            $this->registerHandlers();
        }

        session_start();

        // Validate session
        if ($this->needValidate()) {
            $validator_data = $this->getValidatorData();
            if (!isset($_SESSION['_validator_data'])) {
                $_SESSION['_validator_data'] = $validator_data;
            } else {
                if ($_SESSION['_validator_data'] != $validator_data) {
                    session_regenerate_id(true);
                    $_SESSION = array();
                }
            }
        }

        if (!empty($request['check_session'])) {
            die(!empty($_SESSION) ? 'OK' : '');
        }

        return $this->isStarted();
    }

    /**
     * Checks whether session needs to be validated
     *
     * @return boolean True if session should be validated, false - otherwise
     */
    protected function needValidate()
    {
        return (defined('SESS_VALIDATE_IP') || defined('SESS_VALIDATE_UA')) && !defined('FORCE_SESSION_START');
    }

    /**
     * Get session validation data
     *
     * @return array validation data
     */
    public static function getValidatorData()
    {
        $data = array();

        if (defined('SESS_VALIDATE_IP')) {
            $ip = fn_get_ip();
            $data['ip'] = $ip['host'];
        }

        if (defined('SESS_VALIDATE_UA')) {
            $data['ua'] = md5($_SERVER['HTTP_USER_AGENT']);
        }

        return $data;
    }

    /**
     * Sets session-related INI-parameters.
     *
     * @return void
     */
    protected function setSessionParams()
    {
        ini_set('session.cookie_lifetime', $this->cookie_lifetime);
        ini_set('session.cookie_domain', $this->cookie_domain);
        ini_set('session.cookie_path', $this->cookie_path);
        ini_set('session.gc_probability', $this->gc_probability);
        ini_set('session.gc_divisor', $this->gc_divisor);

        session_cache_limiter($this->cache_limiter);
        session_cache_expire($this->cache_expire);

        session_set_cookie_params(
            $this->cookie_lifetime,
            $this->cookie_path,
            $this->cookie_domain,
            $this->cookie_secure,
            $this->cookie_httponly
        );
    }

    /**
     * Registers internal methods as a handlers for session data saving mechanism.
     *
     * @return void
     */
    protected function registerHandlers()
    {
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    /**
     * Creates an instance of session data storage driver.
     */
    protected function initDataStorageDriver()
    {
        $this->storage_driver = $this->app['session.storage'];
    }

    /**
     * @return \Tygh\Backend\Session\ABackend|null Used session data storage driver or null if session isn't started yet.
     */
    public function getStorageDriver()
    {
        return $this->storage_driver;
    }

    /**
     * Regenerates session ID, preserving session data.
     *
     * @return string New session ID
     */
    public function regenerateID()
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $old_id = $this->getID();
        $new_id = $this->addSuffixToSessionID($this->generateSessionID());

        session_write_close();

        $this->storage_driver->regenerate($old_id, $new_id);

        /**
         * Actions after regenerate session id
         *
         * @param string $old_id Old session Id
         * @param string $new_id New session Id
         */
        fn_set_hook('session_regenerate_id', $old_id, $new_id);

        $this->setID($new_id);

        session_start();

        // Update linked data
        db_query('UPDATE ?:stored_sessions SET session_id = ?s WHERE session_id = ?s', $new_id, $old_id);
        db_query('UPDATE ?:user_session_products SET session_id = ?s WHERE session_id = ?s', $new_id, $old_id);

        return $new_id;
    }

    /**
     * Adds suffix to session ID.
     *
     * @param string $session_id Session ID
     *
     * @return string Session ID with suffix.
     */
    public function addSuffixToSessionID($session_id)
    {
        fn_set_hook('sid', $session_id);

        return $session_id . $this->session_id_suffix;
    }

    /**
     * Re-creates session from scratch or using given session ID.
     *
     * @param string $new_session_id Session ID to use. Defaults to null, meaning new ID will be generated.
     *
     * @return string new session ID
     */
    public function resetID($new_session_id = null)
    {
        if ($new_session_id == $this->getID()) {
            return $new_session_id;
        }

        session_destroy();

        if (empty($new_session_id)) {
            $new_session_id = $this->addSuffixToSessionID($this->generateSessionID());
        }

        $this->setID($new_session_id);

        $this->registerHandlers();

        session_start();

        return $this->getID();
    }

    /**
     * Generates session ID key.
     *
     * @return string New session ID
     */
    protected function generateSessionID()
    {
        return SecurityHelper::generateRandomString();
    }

    /**
     * Method is called on read access to session data.
     *
     * @return void
     */
    protected function onRead()
    {
        if (!$this->isStarted() && $this->start_on_read) {
            $this->start();
        }

        $this->onAccess();
    }

    /**
     * Method is called on write access to session data.
     *
     * @return void
     */
    protected function onWrite()
    {
        if (!$this->isStarted() && $this->start_on_write) {
            $this->start();
        }

        $this->onAccess();
    }

    /**
     * Method is called on read or write access to session data.
     *
     * @return void
     */
    protected function onAccess()
    {
        // Workaround for third-party code that is accessing Tygh::$app['session']
        // superglobal variable when session haven't started.
        if (!isset($_SESSION)) {
            $_SESSION = array();
        }
    }

    /**
     * Saves session data to persistent storage.
     *
     * @param string $session_id   Session ID
     * @param array  $session_data Data to save
     *
     * @return boolean True if saved, false otherwise
     */
    public function save($session_id, $session_data)
    {
        // If used not by standard session handler, can accept data in array, not in serialized array
        if (is_array($session_data)) {
            $session_data = $this->encode($session_data);
        }

        $data = array(
            'expiry' => TIME + SESSION_ALIVE_TIME,
            'data' => $session_data
        );

        return $this->storage_driver->write($session_id, $data);
    }

    /**
     * Session data serializer.
     *
     * @param array $data Data to serialize
     *
     * @return string Serialized data
     */
    public function encode($data)
    {
        $raw = '';
        $line = 0;
        $keys = array_keys($data);

        foreach ($keys as $key) {
            $value = $data[$key];
            $line++;

            $raw .= $key . '|' . serialize($value);
        }

        return $raw;
    }

    /**
     * Session data unserializer.
     *
     * @param string $string Serialized data
     *
     * @return array Unserialized data
     */
    public function decode($string)
    {
        $data = array();

        if (!empty($string)) {
            $vars = preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/',
                $string, -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
            );

            for ($i = 0; !empty($vars[$i]); $i++) {
                $data[$vars[$i++]] = unserialize($vars[$i]);
            }
        }

        return $data;
    }

    /**
     * Session start handler.
     *
     * @param string $save_path path for session storage
     * @param array  $sess_name session name
     *
     * @return bool Always true
     */
    public function open($save_path, $sess_name)
    {
        return true;
    }

    /**
     * Session close handler.
     *
     * @return boolean Always true
     */
    public function close()
    {
        return true;
    }

    /**
     * Session data reading from persistent storage handler.
     *
     * @param string $session_id Session ID
     *
     * @return string Session encoded data
     */
    public function read($session_id)
    {
        $data = $this->storage_driver->read($session_id);
        if ($data === false) {
            $stored_data = db_get_field('SELECT `data` FROM ?:stored_sessions WHERE `session_id` = ?s', $session_id);

            if (!empty($stored_data)) {
                db_query('DELETE FROM ?:stored_sessions WHERE session_id = ?s', $session_id);

                $_stored = $this->decode($stored_data);
                $_current['settings'] = !empty($_stored['settings']) ? $_stored['settings'] : array();

                $data = $this->encode($_current);
            }
        }

        return $data !== false ? $data : '';
    }

    /**
     * Session data writing to persistent storage handler.
     *
     * @param string $session_id   Session ID
     * @param array  $session_data Session data
     *
     * @return boolean True if saved, false otherwise
     */
    public function write($session_id, $session_data)
    {
        return $this->save($session_id, $session_data);
    }

    /**
     * Session data destroy handler.
     *
     * @param string $session_id Session ID
     *
     * @return boolean True if destroyed, false otherwise
     */
    public function destroy($session_id)
    {
        return $this->storage_driver->delete($session_id);
    }

    /**
     * Session data garbage collector handler.
     *
     * @param int $max_lifetime Max session data lifetime
     *
     * @return boolean Always true
     */
    public function gc($max_lifetime)
    {
        $this->storage_driver->gc($max_lifetime);

        // Cleanup stored sessions
        db_query('DELETE FROM ?:stored_sessions WHERE expiry < ?i', TIME - SESSIONS_STORAGE_ALIVE_TIME);

        // Delete custom files (garbage) from unlogged customers
        $files = Storage::instance('custom_files')->getList('sess_data');
        if (is_array($files)) {
            foreach ($files as $file) {
                $fdate = fileatime(Storage::instance('custom_files')->getAbsolutePath('sess_data/' . $file));

                if ($fdate < (TIME - SESSIONS_STORAGE_ALIVE_TIME)) {
                    Storage::instance('custom_files')->delete('sess_data/' . $file);
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        $this->onRead();

        return sizeof($_SESSION);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        $this->onRead();

        return new \ArrayIterator($_SESSION);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->onWrite();

        if (is_null($offset)) {
            $_SESSION[] = $value;
        } else {
            $_SESSION[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        $this->onRead();

        return isset($_SESSION[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->onWrite();

        unset($_SESSION[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
        $this->onRead();

        return $_SESSION[$offset];
    }

    /**
     * Allows to fetch session data as an array.
     *
     * @return array Session data
     */
    public function toArray()
    {
        $this->onRead();

        return $_SESSION;
    }

    /**
     * Allows to set session data from an array
     *
     * @param array $data Session data to set
     */
    public function fromArray(array $data)
    {
        $this->onWrite();

        $_SESSION = $data;
    }
}
