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

/**
 * Cache backend class, implements 8 methods:
 */
abstract class ABackend
{
    protected $_company_id = 0;
    protected $_config = array();

    /**
     * Object constructor
     * @param array $config configuration options
     */
    public function __construct($config)
    {
        $this->_company_id = intval(Registry::get('runtime.company_id'));
    }

    /**
     * Set data to the cache storage
     *
     * @param $name
     * @param $data
     * @param $condition
     * @param null $cache_level
     */
    public function set($name, $data, $condition, $cache_level = NULL)
    {
        return false;
    }

    /**
     * Gets data from the cache storage
     *
     * @param $name
     * @param  null       $cache_level
     * @return array|bool
     */
    public function get($name, $cache_level = NULL)
    {
        return false;
    }

    /**
     * Clears expired data
     *
     * @param $tags
     * @return bool
     */
    public function clear($tags)
    {
        return false;
    }

    /**
     * Deletes all cached data
     *
     * @return mixed
     */
    public function cleanup()
    {
        return false;
    }
}
