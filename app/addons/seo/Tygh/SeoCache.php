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

use Tygh\Registry;

class SeoCache {

    private static $cache = array();
    private static $init_cache = false;

    /**
     * Gets cached SEO name
     * @param string $name cached object (name or path)
     * @param string $object_type object type
     * @param mixed $object_id object_id/dispatch
     * @param integer $company_id company ID
     * @param string $lang_code language code
     * @param string $area current working area
     * @return string cached name
     */
    public static function set($object_type, $object_id, $object_data, $company_id, $lang_code, $area = AREA)
    {
        if (fn_allowed_for('ULTIMATE')) {
            if (empty($company_id)) {
                $company_id = Registry::get('runtime.company_id');
            }
        }

        if (fn_allowed_for('MULTIVENDOR')) {
            $company_id = 0;
        }

        $key = $lang_code . '_' . $object_id . '_' . $object_type . '_' . $company_id;

        if ($object_type == 's') {
            return self::setStatic($key, $object_data, $lang_code, $area);
        }

        if (!empty($object_data['seo_name']) && isset($object_data['seo_path'])) {
            self::$cache[$key] = array(
                'name' => $object_data['seo_name'],
                'path' => $object_data['seo_path']
            );

        } elseif (isset($object_data['seo_name'])) {
            self::$cache[$key]['name'] = $object_data['seo_name'];
        } elseif (isset($object_data['seo_path'])) {
            self::$cache[$key]['path'] = $object_data['seo_path'];
        }

        return true;
    }

    /**
     * Gets cached SEO name
     * @param string $name cached object (name or path)
     * @param string $object_type object type
     * @param mixed $object_id object_id/dispatch
     * @param integer $company_id company ID
     * @param string $lang_code language code
     * @param string $area current working area
     * @return string cached name
     */
    public static function get($name, $object_type, $object_id, $company_id, $lang_code, $area = AREA)
    {
        if (fn_allowed_for('MULTIVENDOR')) {
            $company_id = 0;
        }

        $key = $lang_code . '_' . $object_id . '_' . $object_type . '_' . $company_id;

        if ($object_type == 's') {
            return self::getStatic($key, $lang_code, $area);
        }

        return isset(self::$cache[$key][$name]) ? self::$cache[$key][$name] : null;
    }

    /**
     * Sets SEO name for static object
     * @param string $key cache key
     * @param array $object_data object data
     * @param string $lang_code language code
     * @param string $area current working area
     * @return boolean true
     */
    private static function setStatic($key, $object_data, $lang_code, $area)
    {
        if ($area != 'C') {
            return true;
        }

        if (!self::$init_cache) {
            Registry::registerCache('seo_cache_static', array('seo_names'), Registry::cacheLevel('static') . $lang_code, true);
        }

        if (isset($object_data['seo_name'])) {
            Registry::set('seo_cache_static.' . $key, $object_data['seo_name']);
        }

        return true;
    }

    /**
     * Puts static SEO name to cache
     * @param string $key cache key
     * @param string $lang_code language code
     * @param string $area current working area
     * @return mixed cached data
     */
    private static function getStatic($key, $lang_code, $area = AREA)
    {
        if ($area != 'C') {
            return null;
        }

        if (!self::$init_cache) {
            Registry::registerCache('seo_cache_static', array('seo_names'), Registry::cacheLevel('static') . $lang_code, true);
        }

        return Registry::get('seo_cache_static.' . $key);
    }
}
