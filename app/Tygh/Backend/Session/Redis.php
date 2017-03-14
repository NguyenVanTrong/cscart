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

namespace Tygh\Backend\Session;
use Tygh\Debugger;
use Tygh\Session;
use Tygh\Exceptions\DatabaseException;

class Redis extends ABackend
{
    /**
     * @var Redis
     */
    private $r;

    /**
     * @var int Max reconnect attempts
     */
    private $max_reconnects = 5;

    /**
     * @var int Current reconnect attempts
     */
    private $reconnects = 0;

    /**
     * @var int Sleep between reconnects
     */
    private $sleep = 200000; // 100 ms

    /**
     * @var string Connection host
     */
    private $host;

    /**
     * @var int Connection port
     */
    private $port = 6379;

    /**
     * Init backend
     *
     * @param array $config global configuration params
     * @param array $params additional params passed from Session class
     *
     * @return bool true if backend was init correctly, false otherwise
     */
    public function __construct($config, $params = array())
    {
        parent::__construct($config, $params);

        $this->config = fn_array_merge(array(
            'redis_server' => $config['session_redis_server'],
            'store_prefix' => !empty($config['store_prefix']) ? $config['store_prefix'] : null,
        ), $this->config);

        $this->host = $config['session_redis_server'];

        if (strncmp($this->host, '/', 1) === 0) {
            $this->port = null;
        } else {
            $parsed = parse_url($this->host);

            if ($parsed && isset($parsed['host'], $parsed['port'])) {
                $this->host = $parsed['host'];
                $this->port = $parsed['port'];
            }
        }

        return $this->connect();
    }

    /**
     * Read session data
     *
     * @param string $sess_id session ID
     *
     * @return mixed session data if exist, false otherwise
     */
    public function read($sess_id)
    {
        $session = $this->query('hGetAll', $this->id($sess_id));

        if (!empty($session)) {

            if ($session['expiry'] > TIME) {
                return $session['data'];
            } else {
                // the session did not have time to get in "stored_sessions" and got out of date, it is necessary to return only settings
                $this->delete($sess_id);
                $session = \Tygh::$app['session']->decode($session['data']);

                return \Tygh::$app['session']->encode(array ('settings' => !empty($session['settings']) ? $session['settings'] : array()));
            }
        }

        return false;
    }

    /**
     * Write session data
     *
     * @param string $sess_id session ID
     * @param array  $data    session data
     *
     * @return boolean always true
     */
    public function write($sess_id, $data)
    {
        $this->query('hmSet', $this->id($sess_id), $data);
        $this->query('setTimeout', $this->id($sess_id), $this->config['ttl'] + SECONDS_IN_HOUR); // increase alive time to allow garbage collector move session to stored sessions storage

        $this->query('set', $this->id($sess_id, 'online:'), 1);
        $this->query('setTimeout', $this->id($sess_id, 'online:'), $this->config['ttl_online']);

        return true;
    }

    /**
     * Update session ID
     *
     * @param string $old_id old session ID
     * @param array  $new_id new session ID
     *
     * @return boolean always true
     */
    public function regenerate($old_id, $new_id)
    {
        $this->query('rename', $this->id($old_id), $this->id($new_id));
        $this->query('rename', $this->id($old_id, 'online:'), $this->id($new_id, 'online:'));

        return true;
    }

    /**
     * Delete session data
     *
     * @param string $sess_id session ID
     *
     * @return boolean always true
     */
    public function delete($sess_id)
    {
        $this->query('del', $this->id($sess_id));

        return true;
    }

    /**
     * Garbage collector (do nothing as redis takes care about deletion of expired keys)
     *
     * @param int $max_lifetime session lifetime
     *
     * @return boolean always true
     */
    public function gc($max_lifetime)
    {
        // Move expired sessions to sessions storage
        $session_ids = array_map(function($key) {
            return substr($key, strrpos($key, ':') + 1);
        }, $this->query('keys', $this->id('*')));

        if (!empty($session_ids)) {
            foreach ($session_ids as $sess_id) {
                $session = $this->query('hGetAll', $this->id($sess_id));
                if (empty($session) || $session['expiry'] < TIME) {
                    if (!empty($session['data'])) {
                        Session::expire($sess_id, $session);
                    }
                    $this->delete($sess_id);
                }
            }
        }

        return true;
    }

    /**
     * Gets sessions that were used less than number of seconds, defined in SESSION_ONLINE constant
     * @param  string $area session area
     * @return array  list of session IDs
     */
    public function getOnline($area)
    {
        $keys = $this->query('keys', $this->id('*_' . $area, 'online:'));

        return array_map(function($key) {
            return substr($key, strrpos($key, ':') + 1);
        }, $keys);
    }

    /**
     * Generates prefix for session id to separate sessions with same ID but from different stores
     *
     * @param string $sess_id session ID
     * @param string $prefix  key prefix
     *
     * @return string prefixed session ID
     */
    protected function id($sess_id, $prefix = '')
    {
        return $prefix . 'session:' . (!empty($this->config['store_prefix']) ? $this->config['store_prefix'] . ':' : '') . $sess_id;
    }

    /**
     * Connects to the Redis server
     * @return boolean true on success, false - otherwise
     */
    protected function connect()
    {
        $this->r = new \Redis();

        Debugger::checkpoint('Session: before redis connect');
        if ($this->r->connect($this->host, $this->port) == true) {
            $this->r->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            Debugger::checkpoint('Session: after redis connect');

            return true;
        }

        return false;
    }

    /**
     * Queries Redis server and handle reconnects if case of failure
     */
    protected function query()
    {
        $args = func_get_args();
        $cmd = array_shift($args);

        try {
            return call_user_func_array(array($this->r, $cmd), $args);
        } catch (\RedisException $e) {
            if ($this->reconnects < $this->max_reconnects) {
                $this->reconnects++;
                usleep($this->sleep);
                $this->connect();
                return call_user_func_array(array($this, 'query'), func_get_args());
            }

            throw new DatabaseException('Sessions: can not connect to the Redis server');
        }
    }
}
