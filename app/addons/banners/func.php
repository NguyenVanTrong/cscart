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

use Tygh\Registry;
use Tygh\Languages\Languages;
use Tygh\BlockManager\Block;
use Tygh\Tools\SecurityHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Get banners
//
function fn_get_banners($params = array(), $lang_code = CART_LANGUAGE)
{
    $default_params = array(
        'items_per_page' => 0,
    );

    $params = array_merge($default_params, $params);

    $sortings = array(
        'position' => '?:banners.position',
        'timestamp' => '?:banners.timestamp',
        'name' => '?:banner_descriptions.banner',
    );

    $condition = $limit = '';

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'name', 'asc');

    $condition = (AREA == 'A') ? '' : " AND ?:banners.status = 'A' ";
    $condition .= fn_get_localizations_condition('?:banners.localization');
    $condition .= (AREA == 'A') ? '' : " AND (?:banners.type != 'G' OR ?:banner_images.banner_image_id IS NOT NULL) ";

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(' AND ?:banners.banner_id IN (?n)', explode(',', $params['item_ids']));
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (?:banners.timestamp >= ?i AND ?:banners.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    fn_set_hook('get_banners', $params, $condition, $sorting, $limit, $lang_code);

    $fields = array (
        '?:banners.banner_id',
        '?:banners.type',
        '?:banners.target',
        '?:banners.status',
        '?:banners.position',
        '?:banner_descriptions.banner',
        '?:banner_descriptions.description',
        '?:banner_descriptions.url',
        '?:banner_images.banner_image_id',
    );

    if (fn_allowed_for('ULTIMATE')) {
        $fields[] = '?:banners.company_id';
    }

    $banners = db_get_hash_array(
        "SELECT ?p FROM ?:banners " .
        "LEFT JOIN ?:banner_descriptions ON ?:banner_descriptions.banner_id = ?:banners.banner_id AND ?:banner_descriptions.lang_code = ?s" .
        "LEFT JOIN ?:banner_images ON ?:banner_images.banner_id = ?:banners.banner_id AND ?:banner_images.lang_code = ?s" .
        "WHERE 1 ?p ?p ?p",
        'banner_id', implode(", ", $fields), $lang_code, $lang_code, $condition, $sorting, $limit
    );

    $banner_image_ids = fn_array_column($banners, 'banner_image_id');
    $images = fn_get_image_pairs($banner_image_ids, 'promo', 'M', true, false, $lang_code);

    foreach ($banners as $banner_id => $banner) {
        $banners[$banner_id]['main_pair'] = !empty($images[$banner['banner_image_id']]) ? reset($images[$banner['banner_image_id']]) : array();
    }

    fn_set_hook('get_banners_post', $banners, $params);

    return array($banners, $params);
}

//
// Get specific banner data
//
function fn_get_banner_data($banner_id, $lang_code = CART_LANGUAGE)
{
    // Unset all SQL variables
    $fields = $joins = array();
    $condition = '';

    $fields = array (
        '?:banners.banner_id',
        '?:banners.status',
        '?:banner_descriptions.banner',
        '?:banners.type',
        '?:banners.target',
        '?:banners.localization',
        '?:banners.timestamp',
        '?:banners.position',
        '?:banner_descriptions.description',
        '?:banner_descriptions.url',
        '?:banner_images.banner_image_id',
    );

    if (fn_allowed_for('ULTIMATE')) {
        $fields[] = '?:banners.company_id as company_id';
    }

    $joins[] = db_quote("LEFT JOIN ?:banner_descriptions ON ?:banner_descriptions.banner_id = ?:banners.banner_id AND ?:banner_descriptions.lang_code = ?s", $lang_code);
    $joins[] = db_quote("LEFT JOIN ?:banner_images ON ?:banner_images.banner_id = ?:banners.banner_id AND ?:banner_images.lang_code = ?s", $lang_code);

    $condition = db_quote("WHERE ?:banners.banner_id = ?i", $banner_id);
    $condition .= (AREA == 'A') ? '' : " AND ?:banners.status IN ('A', 'H') ";

    /**
     * Prepare params for banner data SQL query
     *
     * @param int   $banner_id Banner ID
     * @param str   $lang_code Language code
     * @param array $fields    Fields list
     * @param array $joins     Joins list
     * @param str   $condition Conditions query
     */
    fn_set_hook('get_banner_data', $banner_id, $lang_code, $fields, $joins, $condition);

    $banner = db_get_row("SELECT " . implode(", ", $fields) . " FROM ?:banners " . implode(" ", $joins) ." $condition");

    if (!empty($banner)) {
        $banner['main_pair'] = fn_get_image_pairs($banner['banner_image_id'], 'promo', 'M', true, false, $lang_code);
    }

    /**
     * Post processing of banner data
     *
     * @param int   $banner_id Banner ID
     * @param str   $lang_code Language code
     * @param array $banner    Banner data
     */
    fn_set_hook('get_banner_data_post', $banner_id, $lang_code, $banner);

    return $banner;
}

/**
 * Hook for deleting store banners
 *
 * @param int $company_id Company id
 */
function fn_banners_delete_company(&$company_id)
{
    if (fn_allowed_for('ULTIMATE')) {
        $bannser_ids = db_get_fields("SELECT banner_id FROM ?:banners WHERE company_id = ?i", $company_id);

        foreach ($bannser_ids as $banner_id) {
            fn_delete_banner_by_id($banner_id);
        }
    }
}

/**
 * Deletes banner and all related data
 *
 * @param int $banner_id Banner identificator
 */
function fn_delete_banner_by_id($banner_id)
{
    if (!empty($banner_id) && fn_check_company_id('banners', 'banner_id', $banner_id)) {
        db_query("DELETE FROM ?:banners WHERE banner_id = ?i", $banner_id);
        db_query("DELETE FROM ?:banner_descriptions WHERE banner_id = ?i", $banner_id);

        fn_set_hook('delete_banners', $banner_id);

        Block::instance()->removeDynamicObjectData('banners', $banner_id);

        $banner_images_ids = db_get_fields("SELECT banner_image_id FROM ?:banner_images WHERE banner_id = ?i", $banner_id);

        foreach ($banner_images_ids as $banner_image_id) {
            fn_delete_image_pairs($banner_image_id, 'promo');
        }

        db_query("DELETE FROM ?:banner_images WHERE banner_id = ?i", $banner_id);
    }
}

/**
 * Checks of request for need to update the banner image.
 *
 * @return bool
 */
function fn_banners_need_image_update()
{
    if (!empty($_REQUEST['file_banners_main_image_icon']) && is_array($_REQUEST['file_banners_main_image_icon'])) {
        $image_banner = reset($_REQUEST['file_banners_main_image_icon']);

        if ($image_banner == 'banners_main') {
            return false;
        }
    }

    return true;
}

function fn_banners_update_banner($data, $banner_id, $lang_code = DESCR_SL)
{
    SecurityHelper::sanitizeObjectData('banner', $data);

    if (isset($data['timestamp'])) {
        $data['timestamp'] = fn_parse_date($data['timestamp']);
    }

    $data['localization'] = empty($data['localization']) ? '' : fn_implode_localizations($data['localization']);

    if (!empty($banner_id)) {
        db_query("UPDATE ?:banners SET ?u WHERE banner_id = ?i", $data, $banner_id);
        db_query("UPDATE ?:banner_descriptions SET ?u WHERE banner_id = ?i AND lang_code = ?s", $data, $banner_id, $lang_code);

        $banner_image_id = fn_get_banner_image_id($banner_id, $lang_code);
        $banner_image_exist = !empty($banner_image_id);
        $banner_is_multilang = Registry::get('addons.banners.banner_multilang') == 'Y';
        $image_is_update = fn_banners_need_image_update();

        if ($banner_is_multilang) {
            if ($banner_image_exist && $image_is_update) {
                fn_delete_image_pairs($banner_image_id, 'promo');
                db_query("DELETE FROM ?:banner_images WHERE banner_id = ?i AND lang_code = ?s", $banner_id, $lang_code);
                $banner_image_exist = false;
            }
        } else {
            if (isset($data['url'])) {
                db_query("UPDATE ?:banner_descriptions SET url = ?s WHERE banner_id = ?i", $data['url'], $banner_id);
            }
        }

        if ($image_is_update && !$banner_image_exist) {
            $banner_image_id = db_query("INSERT INTO ?:banner_images (banner_id, lang_code) VALUE(?i, ?s)", $banner_id, $lang_code);
        }
        $pair_data = fn_attach_image_pairs('banners_main', 'promo', $banner_image_id, $lang_code);

        if (!$banner_is_multilang && !$banner_image_exist) {
            fn_banners_image_all_links($banner_id, $pair_data, $lang_code);
        }

    } else {
        $banner_id = $data['banner_id'] = db_query("REPLACE INTO ?:banners ?e", $data);

        foreach (Languages::getAll() as $data['lang_code'] => $v) {
            db_query("REPLACE INTO ?:banner_descriptions ?e", $data);
        }

        if (fn_banners_need_image_update()) {
            $banner_image_id = db_get_next_auto_increment_id('banner_images');
            $pair_data = fn_attach_image_pairs('banners_main', 'promo', $banner_image_id, $lang_code);
            if (!empty($pair_data)) {
                $data_banner_image = array(
                    'banner_image_id' => $banner_image_id,
                    'banner_id'       => $banner_id,
                    'lang_code'       => $lang_code
                );

                db_query("INSERT INTO ?:banner_images ?e", $data_banner_image);
                fn_banners_image_all_links($banner_id, $pair_data, $lang_code);
            }
        }
    }

    return $banner_id;
}

function fn_banners_image_all_links($banner_id, $pair_data, $main_lang_code = DESCR_SL)
{
    if (!empty($pair_data)) {
        $pair_id = reset($pair_data);

        $lang_codes = Languages::getAll();
        unset($lang_codes[$main_lang_code]);

        foreach ($lang_codes as $lang_code => $lang_data) {
            $_banner_image_id = db_query("INSERT INTO ?:banner_images (banner_id, lang_code) VALUE(?i, ?s)", $banner_id, $lang_code);
            fn_add_image_link($_banner_image_id, $pair_id);
        }
    }
}

function fn_get_banner_image_id($banner_id, $lang_code = DESCR_SL)
{
    return db_get_field("SELECT banner_image_id FROM ?:banner_images WHERE banner_id = ?i AND lang_code = ?s", $banner_id, $lang_code);
}

//
// Get banner name
//
function fn_get_banner_name($banner_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($banner_id)) {
        return db_get_field("SELECT banner FROM ?:banner_descriptions WHERE banner_id = ?i AND lang_code = ?s", $banner_id, $lang_code);
    }

    return false;
}

function fn_banners_delete_image_pre($image_id, $pair_id, $object_type)
{
    if ($object_type == 'promo') {
        $banner_data = db_get_row("SELECT banner_id, banner_image_id FROM ?:banner_images INNER JOIN ?:images_links ON object_id = banner_image_id WHERE pair_id = ?i", $pair_id);

        if (Registry::get('addons.banners.banner_multilang') == 'Y') {

            if (!empty($banner_data['banner_image_id'])) {
                $lang_code = db_get_field("SELECT lang_code FROM ?:banner_images WHERE banner_image_id = ?i", $banner_data['banner_image_id']);

                db_query("DELETE FROM ?:common_descriptions WHERE object_id = ?i AND object_holder = 'images' AND lang_code = ?s", $image_id, $lang_code);
                db_query("DELETE FROM ?:banner_images WHERE banner_image_id = ?i", $banner_data['banner_image_id']);
            }

        } else {
            $banner_image_ids = db_get_fields("SELECT object_id FROM ?:images_links WHERE image_id = ?i AND object_type = 'promo'", $image_id);

            if (!empty($banner_image_ids)) {
                db_query("DELETE FROM ?:banner_images WHERE banner_image_id IN (?n)", $banner_image_ids);
                db_query("DELETE FROM ?:images_links WHERE object_id IN (?n)", $banner_image_ids);
            }
        }
    }
}

function fn_banners_clone($banners, $lang_code)
{
    foreach ($banners as $banner) {
        if (empty($banner['main_pair']['pair_id'])) {
            continue;
        }

        $data_banner_image = array(
            'banner_id' => $banner['banner_id'],
            'lang_code' => $lang_code
        );
        $banner_image_id = db_query("REPLACE INTO ?:banner_images ?e", $data_banner_image);
        fn_add_image_link($banner_image_id, $banner['main_pair']['pair_id']);
    }
}

function fn_banners_update_language_post($language_data, $lang_id, $action)
{
    if ($action == 'add') {
        list($banners) = fn_get_banners(array(), DEFAULT_LANGUAGE);
        fn_banners_clone($banners, $language_data['lang_code']);
    }
}

function fn_banners_delete_languages_post($lang_ids, $lang_codes, $deleted_lang_codes)
{
    foreach ($deleted_lang_codes as $lang_code) {
        list($banners) = fn_get_banners(array(), $lang_code);

        foreach ($banners as $banner) {
            if (empty($banner['main_pair']['pair_id'])) {
                continue;
            }
            fn_delete_image($banner['main_pair']['image_id'], $banner['main_pair']['pair_id'], 'promo');
        }
    }
}

function fn_banners_install()
{
    $banners = db_get_hash_multi_array("SELECT ?:banners.banner_id, ?:banner_images.banner_image_id, ?:banner_images.lang_code FROM ?:banners LEFT JOIN ?:banner_images ON ?:banner_images.banner_id = ?:banners.banner_id", array('lang_code', 'banner_id'));

    $langs = array_keys(Languages::getAll());
    $need_clone_langs = array_diff($langs, array_keys($banners));

    if (!empty($need_clone_langs)) {
        $clone_lang = DEFAULT_LANGUAGE;

        if (in_array(DEFAULT_LANGUAGE, $need_clone_langs)) {
            $clone_lang = 'en';
        }

        foreach ($banners[$clone_lang] as $banner_id => &$banner) {
            $banner['main_pair'] = fn_get_image_pairs($banner['banner_image_id'], 'promo', 'M', true, false, $clone_lang);
        }

        foreach ($need_clone_langs as $need_clone_lang) {
            fn_banners_clone($banners[$clone_lang], $need_clone_lang);
        }
    }

    foreach ($banners['en'] as $banner_id => &$banner) {
        $banner['main_pair'] = fn_get_image_pairs($banner['banner_image_id'], 'promo', 'M', true, false, 'en');
    }

    if (!in_array('en', $langs)) {
        $banner_images_ids = db_get_fields("SELECT banner_image_id FROM ?:banner_images WHERE lang_code = ?s", 'en');
        foreach ($banner_images_ids as $banner_image_id) {
            fn_delete_image_pairs($banner_image_id, 'promo');
        }

        if (!empty($banner_images_ids)) {
            db_query("DELETE FROM ?:banner_images WHERE banner_image_id IN (?n)", $banner_images_ids);
        }
    }

    return true;
}

if (!fn_allowed_for('ULTIMATE:FREE')) {
    function fn_banners_localization_objects(&$_tables)
    {
        $_tables[] = 'banners';
    }
}

if (fn_allowed_for('ULTIMATE')) {
    function fn_banners_ult_check_store_permission($params, &$object_type, &$object_name, &$table, &$key, &$key_id)
    {
        if (Registry::get('runtime.controller') == 'banners' && !empty($params['banner_id'])) {
            $key = 'banner_id';
            $key_id = $params[$key];
            $table = 'banners';
            $object_name = fn_get_banner_name($key_id, DESCR_SL);
            $object_type = __('banner');
        }
    }
}
