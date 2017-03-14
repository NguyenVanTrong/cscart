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

use Tygh\Exceptions\DeveloperException;

class Registry
{
    private static $_storage = array();
    private static $_cached_keys = array();
    private static $_changed_tables = array();
    private static $_storage_cache = array();
    private static $_cache_levels = array();
    private static $_cache_handlers = array();
    private static $_cache_handlers_are_updated = false;

    /**
     * @var \Tygh\Backend\Cache\ABackend $_cache Cache provider
     */
    private static $_cache = null;

    const NOT_FOUND = '/#not found#/';

    /**
     * Table where cache handlers are stored
     */
    const CACHE_HANDLERS_TABLE = 'cache_handlers';

    /**
     * @var Application
     */
    protected static $application = array();

    /**
     * @var array Keys that should be fetched from Application container
     */
    protected static $legacy_keys = array('crypt' => true, 'view' => true, 'api' => true, 'ajax' => true, 'class_loader' => true);

    public static function getCachedKeys()
    {
        return self::$_cached_keys;
    }

    /**
     * Puts variable to registry
     *
     * @param string  $key      key name
     * @param mixed   $value    key value
     * @param boolean $no_cache if set to true, data won't be cache even if it's registered in the cache
     *
     * @return boolean always true
     */
    public static function set($key, $value, $no_cache = false)
    {
        if (isset(self::$legacy_keys[$key])) {
            // Development::deprecated('Usage of Registry class for storing services is deprecated. Use Tygh::$app instead.');
            self::$application[$key] = $value;
            return true;
        }

        if (strpos($key, '.') !== false) {
            list($_key) = explode('.', $key);
        } else {
            $_key = $key;
        }

        $var = & self::_varByKey('create', $key);
        $var = $value;

        if ($no_cache == false && isset(self::$_cached_keys[$_key]) && self::$_cached_keys[$_key]['track'] == false) { // save cache immediatelly
            $_var = (strpos($key, '.') !== false) ? self::get($_key) : $value;

            self::_saveCache($_key, $_var);
            unset(self::$_cached_keys[$_key]);
        }

        return true;
    }

    /**
     * Gets variable from registry (value can be returned by reference)
     *
     * @param string $key key name
     *
     * @return mixed key value
     */
    public static function get($key)
    {
        if (isset(self::$legacy_keys[$key])) {
            // Development::deprecated('Usage of Registry class for storing services is deprecated. Use Tygh::$app instead.');

            return self::$application[$key];
        }

        $val = self::_varByKey('get', $key);

        return ($val !== self::NOT_FOUND) ? $val : null;
    }

    /**
     * @return array All variables registered at registry.
     */
    public static function getAll()
    {
        return self::$_storage;
    }

    /**
     * Pushes data to array
     *
     * @param string $key key name
     * @paramN mixed values to push to the key value
     *
     * @return boolean always true
     */
    public static function push()
    {
        $args = func_get_args();
        $key = array_shift($args);

        $data = self::get($key);
        if (!is_array($data)) {
            $data = array();
        }

        $data =	array_merge($data, $args);

        return self::set($key, $data);
    }

    /**
     * Deletes key from registry
     *
     * @param string $key key name
     *
     * @return boolean true if key found, false - otherwise
     */
    public static function del($key)
    {
        if (self::_varByKey('delete', $key) === self::NOT_FOUND) {
            return false;
        }

        return true;
    }

    /**
     * Private: performs key action
     *
     * @param string $action key action (get, create, delete)
     * @param string $key    key name
     *
     * @return mixed key value
     */
    private static function & _varByKey($action, $key)
    {
        if ($action == 'get' && isset(self::$_storage_cache[$key])) {
            return self::$_storage_cache[$key];
        }

        $not_found = self::NOT_FOUND;

        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $length = sizeof($parts);
        } else {
            $parts = (array)$key;
            $length = 1;
        }

        $piece = & self::$_storage;

        foreach ($parts as $i => $part) {
            if ($action == 'create' && !isset($piece[$part])) {
                $piece[$part] = array();
            }

            if (is_array($piece) && array_key_exists($part, $piece)) { // isset does not return true on null values
                if ($action == 'delete' && (($i+1) == $length)) {
                    unset($piece[$part]);
                    unset(self::$_storage_cache[$key]);
                    $not_found = true;

                    return $not_found;
                }

                $piece = & $piece[$part];

                continue;
            }

            return $not_found;
        }

        // If we creating new key, cleanup cached key children
        if ($action == 'create') {
            foreach (self::$_storage_cache as $k => $v) {
                if (strpos($k, $key . '.') === 0) {
                    unset(self::$_storage_cache[$k]);
                }
            }
        }

        // cache complex keys only
        if ($length > 1) {
            self::$_storage_cache[$key] = & $piece;

            return self::$_storage_cache[$key];
        }

        return $piece;
    }

    /**
     * Conditional get, returns default value if key does not exist in registry
     *
     * @param string $key     key name
     * @param mixed  $default default value
     *
     * @return mixed key value if exist, default value otherwise
     */
    public static function ifGet($key, $default)
    {
        $var = self::get($key);

        return !empty($var) ? $var : $default;
    }

    /**
     * Checks if key exists in the registry
     *
     * @param string $key key name
     *
     * @return boolean true if key exists, false otherwise
     */
    public static function isExist($key)
    {
        $var = self::_varByKey('get', $key);

        return $var !== self::NOT_FOUND;
    }

    /**
     * Marks table as changed
     *
     * @param string $table table name
     *
     * @return boolean always true
     */
    public static function setChangedTables($table)
    {
        if ($table != self::CACHE_HANDLERS_TABLE) {
            self::$_changed_tables[$table] = true;
        }

        return true;
    }

    /**
     * Registers variable in the cache
     *
     * @param mixed $key         key name. Array with 2 values can be passed: first - key name, second - key alias
     * @param mixed  $condition   cache reset condition - array with table names of expiration time (int)
     * @param string $cache_level indicates the cache dependencies on controller, language, user group, etc
     * @param bool   $track       if set to true, cache data will be collection during script execution and saved when it finished
     *
     * @return boolean true if data is cached and valid, false - otherwise
     */
    public static function registerCache($key, $condition, $cache_level = NULL, $track = false)
    {
        if (empty(self::$_cache)) {
            self::cacheInit();
        }

        if (is_array($key)) {
            list($tag, $alias) = $key;
        } else {
            $alias = $key;
            $tag = '';
        }

        if (empty(self::$_cached_keys[$alias])) {
            self::$_cached_keys[$alias] = array(
                'condition' => $condition,
                'cache_level' => $cache_level . (!empty($tag) ? $alias : ''),
                'track' => $track,
                'hash' => '',
                'tag' => $tag
            );

            if (!self::isExist($alias) && ($val = self::_getCache(!empty($tag) ? $tag : $alias, self::$_cached_keys[$alias]['cache_level'])) !== NULL) {
                self::set($alias, $val, true);

                // Get hash of original value for tracked data
                if ($track == true) {
                    self::$_cached_keys[$alias]['hash'] = md5(serialize($val));
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Inits cache backend
     *
     * @return boolean always true
     */
    public static function cacheInit()
    {
        if (empty(self::$_cache)) {
            $_cache_class = self::ifGet('config.cache_backend', 'file');
            $_cache_class = '\\Tygh\\Backend\\Cache\\' . ucfirst($_cache_class);

            self::$_cache = new $_cache_class(self::get('config'));
        }

        return true;
    }

    /**
     * Gets cached data
     *
     * @param string $key         key name
     * @param string $cache_level indicates the cache dependencies on controller, language, user group, etc
     *
     * @return mixed cached data if exist, NULL otherwise
     */
    private static function _getCache($key, $cache_level = NULL)
    {
        $time_start = microtime(true);
        $data = self::$_cache->get($key, $cache_level);
        Debugger::set_cache_query($key . '::' . $cache_level, microtime(true) - $time_start);

        return (($data !== false) && (!empty($data[0]))) ? $data[0] : NULL;
    }

    /**
     * Assigns database tables to cache key for future cache update
     * @param string $key         key name
     * @param array  $condition   tables list
     * @param string $cache_level cache level
     */
    private static function _updateHandlers($key, $condition, $cache_level)
    {
        if ($cache_level != self::cacheLevel('time')) {
            foreach ($condition as $table) {
                if (empty(self::$_cache_handlers[$table])) {
                    self::$_cache_handlers[$table] = array();
                }

                self::$_cache_handlers[$table][$key] = true;
                self::$_cache_handlers_are_updated = true;
            }
        }
    }

    /**
     * Saves data to cache
     * @param string $key key name
     * @param mixed  $val value
     */
    private static function _saveCache($key, $val)
    {
        if (empty(self::$_cached_keys[$key]['hash']) || self::$_cached_keys[$key]['hash'] != md5(serialize(self::$_storage[$key]))) {

            $_key = !empty(self::$_cached_keys[$key]['tag']) ? self::$_cached_keys[$key]['tag'] : $key;

            self::$_cache->set($_key, $val, self::$_cached_keys[$key]['condition'], self::$_cached_keys[$key]['cache_level']);
            self::_updateHandlers($_key, self::$_cached_keys[$key]['condition'], self::$_cached_keys[$key]['cache_level']);
        }
    }

    /**
     * Saves tracked cached data and clears expired cache
     *
     * @return boolean true if data saved, false if no caches defined
     */
    public static function save()
    {
        /**
         * Hook is being executed before saving cache data to persistent storage and clearing expired cache.
         *
         * @param array $changed_tables List of DB tables that were modified at the runtime.
         * @param array $cached_keys List of registered cache keys.
         */
        fn_set_hook('registry_save_pre', self::$_changed_tables, self::$_cached_keys);

        if (empty(self::$_cache)) {
            return false;
        }

        foreach (self::$_cached_keys as $key => $arg) {
            if (isset(self::$_storage[$key]) && $arg['track'] == true) {
                self::_saveCache($key, self::$_storage[$key]);
            }
        }
        self::$_cached_keys = array();

        if (self::$_cache_handlers_are_updated == true) {
            self::saveCacheHandlers(self::$_cache_handlers);
            self::$_cache_handlers_are_updated = false;
        }

        // Get tags to clear expired cache
        if (!empty(self::$_changed_tables)) {
            $cache_handlers = self::getCacheHandlers(array_keys(self::$_changed_tables));
            $tags = array();
            foreach (self::$_changed_tables as $table => $flag) {
                if (!empty($cache_handlers[$table])) {
                    $tags = array_merge($tags, array_keys($cache_handlers[$table]));
                }
            }

            foreach ($tags as $tag) {
                self::del($tag);
            }

            self::$_cache->clear($tags);
            self::$_changed_tables = array();
        }

        return true;
    }

    /**
     * Cleans up cache data
     *
     * @return boolean always true
     */
    public static function cleanup()
    {
        if (empty(self::$_cache)) {
            self::cacheInit();
        }
        self::$_cache_handlers = array();

        // remove information about cache handlers
        self::cleanCacheHandlers(array('full' => true));

        // remove all the cache
        return self::$_cache->cleanup();
    }


    public static function clearCachedKeyValues()
    {
        foreach (self::$_cached_keys as $key => $definition) {
            self::del($key);
        }
    }

    /**
     * Generates cache level value for key
     *
     * @param string $id Cache level name
     *
     * @return string Cache level value
     */
    public static function cacheLevel($id)
    {
        if (!isset(self::$_cache_levels[$id])) {
            $usergroups_condition = '';
            if (!empty(\Tygh::$app['session']['auth']['usergroup_ids'])) {
                $usergroups_condition = implode('_', \Tygh::$app['session']['auth']['usergroup_ids']);
            }

            if ($id == 'time') {
                $key = 'time';
            } elseif ($id == 'static') {
                $key = 'cache_' . ACCOUNT_TYPE;
            } elseif ($id == 'day') {
                $key = date('z', TIME);
            } elseif ($id == 'locale') {
                $key = (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '_' . CART_SECONDARY_CURRENCY;
            } elseif ($id == 'dispatch') {
                $key = AREA . '_' . $_SERVER['REQUEST_METHOD'] . '_' . str_replace('.', '_', $_REQUEST['dispatch'])
                    . '_' . (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '_' . CART_SECONDARY_CURRENCY;
            } elseif ($id == 'user') {
                $key = AREA . '_' . $_SERVER['REQUEST_METHOD'] . '_' . str_replace('.', '_', $_REQUEST['dispatch'])
                    . '.' . $usergroups_condition
                    . '.' . (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '.' . CART_SECONDARY_CURRENCY;
            } elseif ($id == 'locale_auth') {
                $key = AREA . '_' . $_SERVER['REQUEST_METHOD'] . '_' . (!empty(\Tygh::$app['session']['auth']['user_id']) ? 1 : 0)
                    . '.' . $usergroups_condition
                    . (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '.' . CART_SECONDARY_CURRENCY;
            } elseif ($id == 'html_blocks') {
                $promotion_condition = '';
                if (!empty(\Tygh::$app['session']['auth']['user_id'])) {
                    $active_promotions = db_get_fields(
                        "SELECT promotion_id FROM ?:promotions"
                        . " WHERE status = 'A' AND zone = 'catalog' AND users_conditions_hash LIKE ?l",
                        "%," . \Tygh::$app['session']['auth']['user_id'] . ",%"
                    );
                    if (!empty($active_promotions)) {
                        $promotion_condition = \Tygh::$app['session']['auth']['user_id'];
                    }
                }
                $https_condition = defined('HTTPS') ? '__https' : '';
                $host_condition = REAL_HOST;

                $key = (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '__') : '') . CART_LANGUAGE
                    . '__' . self::cacheLevel('day') . '__'
                    . $usergroups_condition
                    . '__' . $promotion_condition . $https_condition . $host_condition;
            }

            if (!isset($key)) {
                throw new DeveloperException('Registry: undefined cache level');
            }

            self::$_cache_levels[$id] = $key;
        }

        return self::$_cache_levels[$id];
    }

    /**
     * Clears defined cache levels to redefine them again later
     */
    public static function clearCacheLevels()
    {
        self::$_cache_levels = array();
    }

    public static function setAppInstance(Application $application)
    {
        self::$application = $application;
    }

    /**
     * Saves cache handlers in the DB.
     *
     * @param array $cache_handlers         Array of cache handlers to store.
     *                                      Has the same format as self::$_cache_handlers:
     *                                      <code>
     *                                      [
     *                                          'table_name_1' => [
     *                                              'cache_key_1' => true,
     *                                              ...
     *                                              'cache_key_n' => true,
     *                                          ],
     *                                          ...
     *                                          'table_name_n' => [
     *                                              ...
     *                                          ]
     *                                      ];
     *                                      </code>
     *
     * @return bool Always true
     */
    public static function saveCacheHandlers($cache_handlers = array())
    {
        if ($cache_handlers) {
            $data = array();
            foreach ($cache_handlers as $table_name => $handlers) {
                foreach (array_keys($handlers) as $cache_key) {
                    $data[] = array(
                        'table_name' => $table_name,
                        'cache_key' => $cache_key
                    );
                }
                unset($cache_key);
            }

            // disables hooks processing when performing a query
            self::$application['db']->raw = true;
            self::$application['db']->query(
                'REPLACE INTO ?:?f ?m',
                self::CACHE_HANDLERS_TABLE,
                $data
            );
            unset($table_name, $handlers, $data);
        }

        return true;
    }

    /**
     * Removes cache handlers from the DB.
     *
     * @param array $params         Parameters to remove cache handers
     *                              Has the following format:
     *                              <code>
     *                              [
     *                                  // removes all stored cache handlers
     *                                  'full' => true,
     *                                  // removes stored cache handlers for the specified tables
     *                                  'table_names' => [
     *                                      'table_name_1', ..., 'table_name_n'
     *                                  ],
     *                                  // removes stored cache handlers for the specified keys
     *                                  'cache_keys' => [
     *                                      'cache_key_1', ..., 'cache_key_n'
     *                                  ]
     *                              ]
     *                              </code>
     *
     * @return bool Always true
     */
    public static function cleanCacheHandlers(array $params)
    {
        $condition = array();

        if (!empty($params['full'])) {
            $condition[] = '1';
        } else {
            if (!empty($params['table_names'])) {
                $condition[] = self::$application['db']->quote('table_name IN (?a)', $params['table_names']);
            }

            if (!empty($params['cache_keys'])) {
                $condition[] = self::$application['db']->quote('cache_key IN (?a)', $params['cache_keys']);
            }
        }

        if ($condition) {
            self::$application['db']->raw = true;
            self::$application['db']->query(
                'DELETE FROM ?:?f WHERE ?p',
                self::CACHE_HANDLERS_TABLE,
                implode(' OR ', $condition)
            );
        }

        return true;
    }

    /**
     * Gets cache handlers that relate to the specified tables.
     *
     * @param array $tables Tables to get cache handlers for
     *
     * @return array Array of cache handlers.
     *               Has the same format as self::$_cache_handlers
     *
     */
    protected static function getCacheHandlers($tables = array())
    {
        $condition = $tables ? self::$application['db']->quote('AND table_name IN (?a)', $tables) : '';

        self::$application['db']->raw = true;
        return self::$application['db']->getMultiHash(
            'SELECT table_name, cache_key, 1 AS field_value'
            . ' FROM ?:?f'
            . ' WHERE 1 ?p',
            array('table_name', 'cache_key', 'field_value'),
            self::CACHE_HANDLERS_TABLE,
            $condition
        );
    }
}
