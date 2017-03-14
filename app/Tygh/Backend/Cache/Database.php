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

namespace Tygh\Backend\Cache;

use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Exceptions\DatabaseException;

class Database extends ABackend
{
    public function set($name, $data, $condition, $cache_level = NULL)
    {
        $fname = $name . '.' . $cache_level;

        if (!empty($data)) {
            Tygh::$app['db']->raw = true;
            Tygh::$app['db']->query("REPLACE INTO ?:cache ?e", array(
                'name' => $fname,
                'company_id' => $this->_company_id,
                'data' => serialize($data),
                'tags' => $name,
                'expiry' => ($cache_level == Registry::cacheLevel('time')) ? TIME + $condition : 0
            ));
        }

    }

    public function get($name, $cache_level = NULL)
    {
        $fname = $name . '.' . $cache_level;

        $expiry_condition = ($cache_level == Registry::cacheLevel('time')) ? db_quote(" AND expiry > ?i", TIME) : '';
        Tygh::$app['db']->raw = true;
        $res = Tygh::$app['db']->getRow(
            "SELECT data, expiry FROM ?:cache WHERE name = ?s AND company_id = ?i ?p",
            $fname, $this->_company_id, $expiry_condition
        );

        if (!empty($name) && !empty($res)) {
            $_cache_data = (!empty($res['data'])) ? @unserialize($res['data']) : false;
            if ($_cache_data !== false) {
                return array($_cache_data);
            }

            // clean up the cache
            Tygh::$app['db']->raw = true;
            Tygh::$app['db']->query(
                "DELETE FROM ?:cache WHERE name = ?s AND company_id = ?i", $fname, $this->_company_id
            );
        }

        return false;
    }

    public function clear($tags)
    {
        if (!empty($tags)) {
            Tygh::$app['db']->raw = true;
            Tygh::$app['db']->query("DELETE FROM ?:cache WHERE tags IN (?a)", $tags);
        }

        return true;
    }

    public function cleanup()
    {
        Registry::set('runtime.database.skip_errors', true);

        Tygh::$app['db']->raw = true;
        Tygh::$app['db']->query("TRUNCATE ?:cache");

        Registry::set('runtime.database.skip_errors', false);

        return true;
    }

    public function __construct($config)
    {
        Tygh::$app['db']->raw = true;
        if (!Tygh::$app['db']->getField("SHOW TABLES LIKE '?:cache'")) {
            Registry::set('runtime.database.skip_errors', true);
            Tygh::$app['db']->raw = true;
            $res = Tygh::$app['db']->query('
                CREATE TABLE ?:cache (
                    name varchar(255),
                    company_id int(11) unsigned not null default \'0\',
                    data mediumtext,
                    expiry int,
                    tags varchar(255),
                    PRIMARY KEY(name, company_id),
                    KEY (tags),
                    KEY (name, company_id, expiry),
                    KEY (company_id)
                ) Engine=MyISAM DEFAULT CHARSET UTF8
            ');
            Registry::set('runtime.database.skip_errors', false);
            if ($res == false) {
                throw new DatabaseException('Database cache data storage is not supported. Please choose another one.');
            }
        }

        parent::__construct($config);

        return true;
    }
}
