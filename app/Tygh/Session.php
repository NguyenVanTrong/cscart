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

namespace Tygh;

use Tygh\Bootstrap;
use Tygh\Storage;
use Tygh\Tools\SecurityHelper;

/**
 * Class Session
 *
 * This class is deprecated. Use "session" application component instead.
 *
 * @deprecated
 * @package Tygh
 */
class Session
{
    private static $_session;
    private static $_name;

    protected static $ttl_online = SESSION_ONLINE;
    protected static $ttl_storage = SESSIONS_STORAGE_ALIVE_TIME;
    protected static $ttl = SESSION_ALIVE_TIME;

    /**
     * Generate session ID for different area
     *
     * @param string $sess_id session ID from cookie
     * @param string $area    session area
     *
     * @return string modified session ID
     */
    private static function _sid($sess_id, $area = AREA)
    {
        fn_set_hook('sid', $sess_id);

        return $sess_id . '_' . $area;
    }

    /**
     * Generates session id
     *
     * @return string new session ID
     */
    private static function _generateId()
    {
        return SecurityHelper::generateRandomString();
    }

    /**
     * Checks if session needs to be started
     *
     * @return boolean true if session should be started, false - otherwise
     */
    private static function canStart()
    {
        return !defined('NO_SESSION') || defined('FORCE_SESSION_START');
    }

    /**
     * Checks if session needs to be validated
     *
     * @return boolean true if session should be validated, false - otherwise
     */
    private static function needValidate()
    {
        return (defined('SESS_VALIDATE_IP') || defined('SESS_VALIDATE_UA')) && !defined('FORCE_SESSION_START');
    }

    /**
     * Session serializer
     *
     * @param array $data session data
     *
     * @return string serialized data
     */
    public static function encode($data)
    {
        return \Tygh::$app['session']->encode($data);
    }

    /**
     * Session unserializer
     *
     * @param string $string serialized session data
     *
     * @return array unserialized session data
     */
    public static function decode($string)
    {
        return \Tygh::$app['session']->decode($string);
    }

    /**
     * Get session variable name (default action)
     *
     * @return string session name
     */
    public static function getName()
    {
        return \Tygh::$app['session']->getName();
    }

    /**
     * Get session ID (default action)
     *
     * @return string session ID
     */
    public static function getId()
    {
        return \Tygh::$app['session']->getID();
    }

    /**
     * Set session ID
     *
     * @param string $sess_id      session ID
     * @param bool   $need_postfix Determines whether it is necessary to add company_id and area code to the end of the
     *                             session_id value
     *
     * @return string new session ID
     */
    public static function setId($sess_id, $need_postfix = true)
    {
        if ($need_postfix) {
            return \Tygh::$app['session']->setID(
                \Tygh::$app['session']->addSuffixToSessionID($sess_id)
            );
        } else {
            return \Tygh::$app['session']->setID($sess_id);
        }
    }

    /**
     * Regenerates session ID
     *
     * @return string new session ID
     */
    public static function regenerateId()
    {
        return \Tygh::$app['session']->regenerateID();

    }

    /**
     * Re-create session, returns new session ID
     *
     * @param string $sess_id session ID to start with
     *
     * @return string new session ID
     */
    public static function resetId($sess_id = null)
    {
        return \Tygh::$app['session']->resetID($sess_id);
    }

    /**
     * Starts session
     *
     * @param array $request Request data
     */
    public static function start($request = array())
    {
        return \Tygh::$app['session']->start();
    }

    /**
     * Set session params
     */
    public static function setParams()
    {
    }

    /**
     * Get session validation data
     *
     * @return array validation data
     */
    public static function getValidatorData()
    {
        return \Tygh::$app['session']->getValidatorData();
    }

    /**
     * Set session name
     *
     * @param $account_type - current account type
     *
     * @return boolean always true
     */
    public static function setName($account_type = ACCOUNT_TYPE)
    {
        return \Tygh::$app['session']->setName($account_type);
    }

    /**
     * Save session to storage
     *
     * @param string $sess_id session ID
     * @param array  $data    session data
     * @param string $area    session area
     *
     * @return boolean true if saved, false otherwise
     */
    public static function save($sess_id, $data, $area = AREA)
    {
        return \Tygh::$app['session']->save($sess_id, $data);
    }

    /**
     * Init session
     *
     * @return boolean true if session was init correctly, false otherwise
     */
    public static function init($request)
    {
        \Tygh::$app['session']->start();
    }

    /**
     * Gets online sessions
     *
     * @param  string $area session area
     *
     * @return array  list of session IDs
     */
    public static function getOnline($area = AREA)
    {
        return \Tygh::$app['session']->getStorageDriver()->getOnline($area);
    }

    /**
     * Expire session, move it to stored sessions and log out user
     *
     * @param string $sess_id session ID
     * @param array  $session session data
     */
    public static function expire($sess_id, $session)
    {
        $sess_data = Session::decode($session['data']);

        db_query('REPLACE INTO ?:stored_sessions ?e', array(
            'session_id' => $sess_id,
            'data' => self::encode(array('settings' => $sess_data['settings'])),
            'expiry' => $session['expiry']
        ));

        if (!empty($sess_data['auth'])) {
            fn_log_user_logout($sess_data['auth'], $session['expiry']);
        }
    }

    /**
     * Return flag the session has started
     * @return boolean
     */
    public static function isStarted()
    {
        if (function_exists('session_status')) {
            return session_status() === PHP_SESSION_ACTIVE;
        } else {
            return session_id() !== '';
        }
    }
}
