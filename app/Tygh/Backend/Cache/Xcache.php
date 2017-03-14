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

class Xcache extends ABackend
{
    const CACHE_PREFIX = 'cs-cart:cache:';

    protected $global_ttl = 0;

    public function __construct($config)
    {
        if (!function_exists('xcache_set')) {
            throw new \Tygh\Exceptions\ClassNotFoundException('"XCache" PHP extension is not installed.');
        }

        if (isset($config['cache_xcache_global_ttl'])) {
            $this->global_ttl = (int)$config['cache_xcache_global_ttl'];
        }

        $this->_config = $config;

        parent::__construct($config);
    }

    public function set($name, $data, $condition, $cache_level = null)
    {
        if (!empty($data)) {
            xcache_set(
                $this->_mapTags($name) . '/' . $cache_level,
                $data,
                ($cache_level == Registry::cacheLevel('time'))
                    ? TIME + $condition
                    : $this->global_ttl
            );
        }
    }

    public function get($name, $cache_level = null)
    {
        $key = $this->_mapTags($name) . '/' . $cache_level;

        if (xcache_isset($key)) {
            return array(xcache_get($key));
        }

        return false;
    }

    public function clear($tags)
    {
        $tags = (array)$this->_mapTags($tags, 0);
        $success = true;

        foreach ($tags as $tag) {
            $success &= xcache_unset_by_prefix($tag);
        }

        return $success;
    }

    public function cleanup()
    {
        xcache_unset_by_prefix(
            self::CACHE_PREFIX
            . (empty($this->_config['store_prefix']) ? '' : ($this->_config['store_prefix'] . ':'))
        );
    }

    private function _mapTags($cache_keys, $company_id = null)
    {
        $cache_keys = (array)$cache_keys;
        $company_id = is_null($company_id) ? $this->_company_id : $company_id;

        foreach ($cache_keys as $i => $key_name) {
            $cache_keys[$i] = self::CACHE_PREFIX
                . (empty($this->_config['store_prefix']) ? '' : ($this->_config['store_prefix'] . ':')) // For Merchium
                . $key_name
                . (empty($company_id) ? '' : (':' . $company_id));
        }

        return sizeof($cache_keys) === 1 ? reset($cache_keys) : $cache_keys;
    }
}