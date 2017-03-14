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

use Tygh\BlockManager\Block;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProductFeatures;
use Tygh\Tools\Math;
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Configurable constants
//
fn_define('SE_SEARCH_TIMEOUT', 3); //Search and navigation request timeout
fn_define('SE_REQUEST_TIMEOUT', 10); // API request timeout
fn_define('SE_PRODUCTS_PER_PASS', 100); // Number of products submitted in a single API request during a full catalog synchronization
fn_define('SE_USE_RELEVANCE_AS_DEFAULT_SORTING', 'Y'); // Y or N  (Set Sorting by relevance as the default sorting on product search in the storefront)

//
// Not configurable constants
//
fn_define('SE_VERSION', '1.3');
fn_define('SE_IMAGE_SIZE', 100);
fn_define('SE_MEMORY_LIMIT', 512);
fn_define('SE_MAX_ERROR_COUNT', 15);
fn_define('SE_MAX_PROCESSING_TIME', 720);
fn_define('SE_MAX_SEARCH_REQUEST_LENGTH', '8000');
fn_define('SE_SERVICE_URL', 'http://www.searchanise.com');
fn_define('SE_PLATFORM', 'cs-cart4');

function fn_searchanise_init_secure_controllers(&$controllers)
{
    $controllers['searchanise'] = 'passive';
}

function fn_searchanise_dispatch_assign_template($controller, $mode, $area)
{
    if (AREA != 'C') {
        return;
    }

    if (!fn_allowed_for('ULTIMATE:FREE') && count(Tygh::$app['session']['auth']['usergroup_ids']) > 1) {
        foreach (Tygh::$app['session']['auth']['usergroup_ids'] as $usergroup_id) {
            $_prices[] = 'price_' . $usergroup_id;
        }

        Tygh::$app['view']->assign('searchanise_prices', join('|', $_prices));
    }

    fn_se_check_import_is_done(fn_se_get_company_id(), CART_LANGUAGE);

    Tygh::$app['view']->assign('searchanise_api_key', fn_se_get_api_key(fn_se_get_company_id(), CART_LANGUAGE));
    Tygh::$app['view']->assign('searchanise_import_status', fn_se_get_import_status(fn_se_get_company_id(), CART_LANGUAGE));
}

function fn_se_get_active_company_ids($joined = false)
{
    static $companies = array();

    if (fn_allowed_for('MULTIVENDOR')) {
        $null_company = array(0); //workaround;
        $companies = array_merge($null_company, db_get_fields("SELECT company_id FROM ?:companies WHERE status = 'A'"));
    }

    return $joined? join('|', $companies) : $companies;
}

function fn_se_check_company_id($company_id = NULL)
{
    if (!fn_allowed_for('ULTIMATE')) {
        $company_id = 0;
    }

    return $company_id;
}

function fn_se_get_company_id()
{
    $company_id = 0;
    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id')) {
            $company_id = Registry::get('runtime.company_id');
        }
        if (Registry::get('runtime.forced_company_id')) {
            $company_id = Registry::get('runtime.forced_company_id');
        }
    }

    return $company_id;
}

function fn_se_get_all_settings($update = false)
{
    static $settings = array();

    if (empty($settings) || !empty($update)) {
        $_settings = db_get_array("SELECT * FROM ?:se_settings");
        foreach ($_settings as $s) {
            $settings[$s['company_id']][$s['lang_code']][$s['name']] = $s['value'];
        }

        if (empty($settings)) {
            $settings = array(null);
        }
    }

    return $settings;
}

function fn_se_get_setting($name, $company_id, $lang_code)
{
    $settings = fn_se_get_all_settings();

    return isset($settings[$company_id][$lang_code][$name])? $settings[$company_id][$lang_code][$name] : NULL;
}

function fn_se_set_setting($name, $company_id, $lang_code, $value)
{
    if (empty($name) || empty($lang_code)) {
        return;
    }

    db_replace_into('se_settings', array(
        'name' => $name,
        'company_id' => $company_id,
        'lang_code' => $lang_code,
        'value'     => $value,
    ));

    fn_se_get_all_settings(true);// call to update cache
}

function fn_se_get_simple_setting($name)
{
    return fn_se_get_setting($name, fn_se_get_company_id(), DEFAULT_LANGUAGE);
}

function fn_se_set_simple_setting($name, $value)
{
    if (empty($name)) {
        return;
    }

    fn_se_set_setting($name, fn_se_get_company_id(), DEFAULT_LANGUAGE, $value);
}

function fn_se_set_import_status($status, $company_id, $lang_code)
{
    fn_se_set_setting('import_status', $company_id, $lang_code, $status);
}

function fn_se_get_import_status($company_id, $lang_code)
{
    return fn_se_get_setting('import_status', $company_id, $lang_code);
}

function fn_se_get_parent_private_key($company_id, $lang_code)
{
    $keys = db_get_hash_single_array("SELECT lang_code, value FROM ?:se_settings WHERE name = 'parent_private_key' AND company_id = ?i", array('lang_code', 'value'), $company_id);
    if (!empty($keys[$lang_code])) {
        return $keys[$lang_code];
    } else {
        foreach ((array) $keys as $key) {
            if (!empty($key)) {
                return $key;
            }
        }
    }

    return NULL;
}

function fn_se_get_private_key($company_id, $lang_code)
{
    return fn_se_get_setting('private_key', $company_id, $lang_code);
}

function fn_se_get_api_key($company_id, $lang_code)
{
    return fn_se_get_setting('api_key', $company_id, $lang_code);
}

function fn_se_get_engines_count($company_id = NULL)
{
    return db_get_field("SELECT count(*) FROM ?:se_settings WHERE name = 'private_key' AND company_id = ?i", $company_id);
}

function fn_se_is_registered()
{
    return (bool) db_get_field("SELECT count(*) FROM ?:se_settings WHERE name = 'parent_private_key'");
}

function fn_se_add_action($action, $data = NULL, $company_id = NULL, $lang_code = NULL)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    $data = array(serialize((array) $data));
    $company_id = fn_se_check_company_id($company_id);

    if ($action == 'prepare_full_import' && empty($company_id) && empty($lang_code)) {
        //Trucate queue for all
        db_query("TRUNCATE ?:se_queue");

    } elseif ($action == 'prepare_full_import' && !empty($company_id)) {
        db_query("DELETE FROM ?:se_queue WHERE company_id = ?i", $company_id);
    }

    $engines_data = fn_se_get_engines_data($company_id, $lang_code);

    foreach ($data as $d) {
        foreach ($engines_data as $engine_data) {
			db_query("DELETE FROM ?:se_queue WHERE status = 'pending' AND action = ?s AND data = ?s AND company_id = ?i AND lang_code = ?s", $action, $d, $engine_data['company_id'], $engine_data['lang_code']);

            db_query("INSERT INTO ?:se_queue ?e", array(
                'action'     => $action,
                'data'       => $d,
                'company_id' => $engine_data['company_id'],
                'lang_code'  => $engine_data['lang_code'],
            ));
        }
    }
}

function fn_se_add_chunk_product_action($action, $product_ids, $company_id = NULL, $lang_code = NULL)
{
    if (!empty($product_ids)) {
        $product_ids = array_chunk($product_ids, SE_PRODUCTS_PER_PASS);

        foreach ($product_ids as $_product_ids) {
            fn_se_add_action($action, $_product_ids, $company_id, $lang_code);
        }
    }

    return true;
}

function fn_searchanise_update_product_amount($new_amount, $product_id, $cart_id, $tracking)
{
    if ($tracking == ProductTracking::TRACK_WITHOUT_OPTIONS) { // track whole product inventory only - we don't use combinations yet
        fn_se_add_action('update', (int) $product_id);
    }
}

function fn_searchanise_update_product_post($product_data, $product_id, $lang_code, $create)
{
    fn_se_add_action('update', (int) $product_id);
}

function fn_searchanise_clone_product($product_id, $pid)
{
    fn_se_add_action('update', (int) $pid);
}

/**
 * Global update products data (running inside fn_global_update_products() function before fields update)
 *
 * @param array  $table       List of table names to be updated
 * @param array  $field       List of SQL field names to be updated
 * @param array  $value       List of new fields values
 * @param array  $type        List of field types absolute or persentage
 * @param string $msg         Message containing the information about the changes made
 * @param array  $update_data List of updated fields and product_ids
 */
function fn_searchanise_global_update_products($table, $field, $value, $type, $msg, $update_data)
{
    if (!empty($update_data['product_ids'])) {
        foreach ($update_data['product_ids'] as $pid) {
            fn_se_add_action('update', (int) $pid);
        }
    } else {
        fn_se_queue_import();
    }
}

function fn_searchanise_update_option_combination_post($combination_data, $combination_hash, $inventory_amount)
{
    if (!empty($combination_data['product_id'])) {
        fn_se_add_action('update', (int) $combination_data['product_id']);
    }
}

/**
 * Process product delete (run after product is deleted)
 *
 * @param int  $product_id      Product identifier
 * @param bool $product_deleted True if product was deleted successfully, false otherwise
 */
function fn_searchanise_delete_product_post($product_id, $product_deleted)
{
    if ($product_deleted) {
        fn_se_add_action('delete', (int) $product_id);
    }
}

/**
 * Update category data (running after fn_update_category() function)
 *
 * @param array  $category_data Category data
 * @param int    $category_id   Category identifier
 * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
 */
function fn_searchanise_update_category_post($category_data, $category_id, $lang_code)
{
    $product_ids = db_get_fields('SELECT product_id FROM ?:products_categories WHERE category_id = ?i', $category_id);

    if (!empty($category_data['usergroup_to_subcats']) && $category_data['usergroup_to_subcats'] == 'Y') {
        $id_path = db_get_field('SELECT id_path FROM ?:categories WHERE category_id = ?i', $category_id);
        $product_ids = array_merge($product_ids, db_get_fields("SELECT pc.product_id FROM ?:products_categories AS pc LEFT JOIN ?:categories AS c ON pc.category_id = c.category_id WHERE id_path LIKE ?l", "$id_path/%"));
    }

    fn_se_add_chunk_product_action('update', $product_ids);
    if (!empty($category_data['status']) && $category_data['status'] != 'A') {
        fn_se_add_action('categories_delete', (int) $category_id);
    } else {
        fn_se_add_action('categories_update', (int) $category_id);
    }
}

function fn_searchanise_delete_category_post($category_id, $recurse, $category_ids)
{
    fn_se_add_action('categories_delete', $category_ids);
}

function fn_searchanise_update_page_post($page_data, $page_id, $lang_code, $create, $old_page_data)
{
    if (!empty($page_data['status']) && $page_data['status'] != 'A') {
        fn_se_add_action('pages_delete', (int) $page_id);
    } else {
        fn_se_add_action('pages_update', (int) $page_id);
    }
}

function fn_searchanise_clone_page($page_id, $new_page_id)
{
    fn_se_add_action('pages_update', (int) $new_page_id);
}

function fn_searchanise_delete_page($page_id)
{
    fn_se_add_action('pages_delete', (int) $page_id);
}

function fn_searchanise_tools_change_status($params, $result)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    if ($params['table'] == 'products' && !empty($result)) {
        fn_se_add_action('update', $params['id']);

    } elseif ($params['table'] == 'product_filters' && !empty($result) && !empty($params['id_name']) && $params['id_name'] == 'filter_id' && !empty($params['id'])) {
        // It used exist function-hook
        if ($params['status'] == 'A') {
            fn_searchanise_update_product_filter(NULL, $params['id']);
        } elseif ($params['status'] == 'D') {
            fn_searchanise_delete_product_filter_post($params['id']);
        }

    } elseif ($params['table'] == 'categories' && !empty($result) && !empty($params['id_name']) && $params['id_name'] == 'category_id' && !empty($params['id'])) {
        $product_ids = db_get_fields('SELECT product_id FROM ?:products_categories WHERE category_id = ?i', $params['id']);
        fn_se_add_chunk_product_action('update', $product_ids);

    } elseif ($params['table'] == 'languages' && !empty($result)) {
        $lang_code = $params['id'];

        if ($params['status'] == 'A') {
            fn_se_signup(NULL, $lang_code, false);
            fn_se_queue_import(NULL, $lang_code, false);

            $language_name = db_get_field("SELECT name FROM ?:languages WHERE lang_code = ?s", $lang_code);
            fn_set_notification('N', __('notice'), __('text_se_re_indexation_required', array(
                '[link]' => fn_url('addons.update?addon=searchanise')
            )));
        }
    }
}

function fn_se_get_engines_data($company_id = NULL, $lang_code = NULL, $skip_available_check = false)
{
    static $engines_data = array();

    $company_id = fn_se_check_company_id($company_id);

    if (empty($engines_data) || !empty($skip_available_check)) {
        if (fn_allowed_for('ULTIMATE')) {
            $available = ($skip_available_check == true)? '1' : "c.status = 'A' AND l.status = 'A'";
            $languages = db_get_array("
                SELECT c.company_id, c.storefront, c.email, l.name, l.lang_code
                FROM ?:languages as l
                INNER JOIN ?:ult_objects_sharing as s ON (s.share_object_id = l.lang_id AND s.share_object_type = 'languages')
                LEFT JOIN ?:companies as c ON c.company_id = s.share_company_id WHERE ?p ORDER BY l.lang_code = ?s DESC", $available, DEFAULT_LANGUAGE);
        } else {
            $available = ($skip_available_check == true)? '1' : "status = 'A'";
            $languages = db_get_array("SELECT * FROM ?:languages WHERE $available");
        }

        foreach ($languages as $l) {
            $l_code = $l['lang_code'];

            if (fn_allowed_for('ULTIMATE:FREE')) {
                if (DEFAULT_LANGUAGE != $l_code) {
                   continue;
                }
            }

            if (fn_allowed_for('ULTIMATE')) {
                $c_id = $l['company_id'];
                $url = 'http://' . $l['storefront'] . '/?sl=' . $l_code;
            } else {
                $c_id = 0;
                $url = 'http://' . Registry::get('config.http_host') . Registry::get('config.http_path') . '/?sl=' . $l_code;
            }

            $engines_data[$c_id][$l_code] = array(
                'lang_code'     => $l_code,
                'company_id'    => $c_id,
                'language_name' => $l['name'],
                'url'           => $url,
                'api_key'       => fn_se_get_api_key($c_id, $l_code),
                'private_key'   => fn_se_get_private_key($c_id, $l_code),
                'import_status' => fn_se_get_import_status($c_id, $l_code),
                'parent_private_key' => fn_se_get_parent_private_key($c_id, $l_code),
            );
        }
    }

    $return = array();
    foreach ($engines_data as $s_keys_data) {
        foreach ($s_keys_data as $s_l_keys_data) {
            if (!is_null($lang_code) && !is_null($company_id) && $s_l_keys_data['lang_code'] == $lang_code && $s_l_keys_data['company_id'] == $company_id) {
                $return[] = $s_l_keys_data;
            } elseif (!is_null($lang_code) && is_null($company_id) && $s_l_keys_data['lang_code'] == $lang_code) {
                $return[] = $s_l_keys_data;
            } elseif (is_null($lang_code) && !is_null($company_id) && $s_l_keys_data['company_id'] == $company_id) {
                $return[] = $s_l_keys_data;
            } elseif (is_null($lang_code) && is_null($company_id)) {
                $return[] = $s_l_keys_data;
            }
        }
    }

    if (!empty($skip_available_check)) {
        $engines_data = array();
    }

    return $return;
}

function fn_se_signup($_company_id = NULL, $_lang_code = NULL, $show_notification = true)
{
    @ignore_user_abort(1);
    @set_time_limit(3600);

    $connected = false;

    $is_showed = false;

    if ((!empty($_company_id) || !empty($_lang_code)) && fn_se_is_registered() == false) {
        return false;
    }

    $email = Registry::ifGet('user_info.email', db_get_field("SELECT email FROM ?:users WHERE user_id = 1"));

    $engines_data = fn_se_get_engines_data($_company_id, $_lang_code);

    foreach ($engines_data as $engine_data) {
        $lang_code = $engine_data['lang_code'];
        $company_id = $engine_data['company_id'];
        $private_key = $engine_data['private_key'];
        $parent_private_key = fn_se_get_parent_private_key($company_id, $lang_code);

        if (!empty($private_key)) {
            continue;
        }

        if ($show_notification == true && empty($is_showed)) {
            fn_se_echo_connect_progress('Connecting to Searchanise..');
            $is_showed = true;
        }

        $response = Http::post(SE_SERVICE_URL . '/api/signup/json', array(
            'url'  => $engine_data['url'],
            'email'   => $email,
            'version' => SE_VERSION,
            'language' => $lang_code,
            'parent_private_key' => $parent_private_key,
            'platform' => SE_PLATFORM,
        ));

        if ($show_notification == true) {
            fn_se_echo_connect_progress('.');
        }

        if (!empty($response)) {
            $response = fn_se_parse_response($response, true);

            if (!empty($response['keys']['api']) && !empty($response['keys']['private'])) {
                $api_key = (string) $response['keys']['api'];
                $private_key = (string) $response['keys']['private'];

                if (empty($api_key) || empty($private_key)) {
                    return false;
                }

                if (empty($parent_private_key)) {
                    fn_se_set_setting('parent_private_key', $company_id, $lang_code, $private_key);
                }

                fn_se_set_setting('api_key', $company_id, $lang_code, $api_key);
                fn_se_set_setting('private_key', $company_id, $lang_code, $private_key);

                $connected = true;
            } else {
                if (!fn_allowed_for('ULTIMATE')) {
                    if ($show_notification == true) {
                        fn_se_echo_connect_progress(' Error<br />');
                    }

                    return false;
                }
            }
        }

        fn_se_set_import_status('none', $company_id, $lang_code);
    }

    if ($connected == true && $show_notification == true) {
        fn_se_echo_connect_progress(' Done<br />');
        fn_set_notification('N', __('notice'), __('text_se_just_connected'));
    }

    fn_set_hook('searchanise_signup_post', $connected);

    return $connected;
}

function fn_se_echo_connect_progress($text)
{
    if (!defined('AJAX_REQUEST')) {
        fn_echo($text);
    }
}

function fn_se_queue_import($company_id = NULL, $lang_code = NULL, $show_notification = true)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    fn_se_add_action('prepare_full_import', NULL, $company_id, $lang_code);

    $engines_data = fn_se_get_engines_data($company_id, $lang_code);
    foreach ($engines_data as $engine_data) {
        fn_se_set_import_status('queued', $engine_data['company_id'], $engine_data['lang_code']);
    }

    if ($show_notification == true) {
        fn_set_notification('N', __('notice'), __('text_se_import_status_queued'));
    }
}

function fn_searchanise_database_restore($files)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    fn_set_notification('W', __('notice'), __('text_se_database_restore_notice', array(
        '[link]' => fn_url('addons.update?addon=searchanise')
    )));

    return true;
}

function fn_se_get_facet_valid_locations()
{
    return array(
        'index.index',
        'products.search',
        'categories.view',
        'product_features.view'
    );
}

function fn_se_get_valid_sortings()
{
    return array(
        'position',
        'product',
        'price',
        'relevance',
        'timestamp',
        'null',
        'popularity', //TODO: server may have not actual `popularity` values.
        // 'bestsellers', // Not supported.
    );
}

function fn_se_check_product_filter_block()
{
    return Block::instance()->isBlockTypeActiveOnCurrentLocation('product_filters');
}

function fn_searchanise_send_search_request($params, $lang_code = CART_LANGUAGE)
{
    $company_id = fn_se_get_company_id();
    $api_key = fn_se_get_api_key($company_id, $lang_code);
    if (empty($api_key)) {
        return;
    }

    $default_params = array(
        'items'       => 'true',
        'facets'      => 'true',
        'output'      => 'json',
    );

    $params = array_merge($default_params, $params);
    if (empty($params['restrictBy'])) {
        unset($params['restrictBy']);
    }

    if (empty($params['union'])) {
        unset($params['union']);
    }

    $query = http_build_query($params);

    if (fn_se_check_debug()) {
        fn_print_r(SE_SERVICE_URL . '/search?api_key=' . $api_key . '&' . http_build_query($params), $params);
    }

    Registry::set('log_cut', true);
    if (strlen($query) > SE_MAX_SEARCH_REQUEST_LENGTH && fn_check_curl()) {
        $received = Http::post(SE_SERVICE_URL . '/search?api_key=' . $api_key, $params, array(
            'timeout' => SE_SEARCH_TIMEOUT
        ));
    } else {
        $params['api_key'] = $api_key;

        $received = Http::get(SE_SERVICE_URL . '/search', $params, array(
            'timeout' => SE_SEARCH_TIMEOUT
        ));
    }

    if (empty($received)) {
        return false;
    }

    $result = json_decode(trim($received), true);
    if (fn_se_check_debug()) {
        fn_print_r($result);
    }

    if (isset($result['error'])) {
        if ($result['error'] == 'NEED_RESYNC_YOUR_CATALOG') {
            fn_se_queue_import($company_id, $lang_code, false);

            return false;

        }
    }

    if (empty($result) || !is_array($result) || !isset($result['totalItems'])) {
        return false;
    }

    return $result;
}

function fn_searchanise_products_sorting(&$sorting, $simple_mode)
{
    if (
        AREA == 'C' &&
        !empty($_REQUEST['search_performed']) &&
        SE_USE_RELEVANCE_AS_DEFAULT_SORTING == 'Y' &&
        fn_se_get_import_status(fn_se_get_company_id(), CART_LANGUAGE) == 'done'
    ) {
        $sorting = array_merge(array('relevance' => array('description' => __('se_relevance'), 'default_order' => 'asc', 'desc' => false)), $sorting);
        $list = array_merge(Registry::get('settings.Appearance.available_product_list_sortings'), array('relevance-asc' => 'Y'));
        Registry::set('settings.Appearance.available_product_list_sortings', $list);
        Tygh::$app['view']->assign('settings', Registry::get('settings'));
    }
}

function fn_se_prepare_request_params($params)
{
    $restrict_by = $query_by = $union = array();

    //
    // Hide products with empty categories and wrong usergroup categories
    //
    $restrict_by['empty_categories'] = 'N';
    $restrict_by['category_usergroup_ids'] = join('|', Tygh::$app['session']['auth']['usergroup_ids']);

    //
    // Visibility
    //
    $restrict_by['status'] = 'A';
    if (!fn_allowed_for('ULTIMATE:FREE')) {
        $restrict_by['usergroup_ids'] = join('|', Tygh::$app['session']['auth']['usergroup_ids']);
    }

    if (Registry::get('settings.General.inventory_tracking') == 'Y' && Registry::get('settings.General.show_out_of_stock_products') == 'N' && AREA == 'C') {
        $restrict_by['amount'] = '1,';
    }

    //
    // Company_id
    //
    if (fn_allowed_for('MULTIVENDOR')) {
        $restrict_by['active_company'] = 'Y';

        if (isset($params['company_id']) && $params['company_id'] != '') {
            $restrict_by['company_id'] = $params['company_id'];
        }
    }

    //
    // Features
    //
    if (!empty($params['features_hash'])) {
        $selected_filters = fn_parse_filters_hash($params['features_hash']);
        if (!empty($selected_filters)) {
            list($filters, ) = fn_get_product_filters(array('filter_id' => array_keys($selected_filters), 'status' => 'A', 'get_variants' => false), 0);

            foreach($filters as $filter) {
                $filter_id = $filter['filter_id'];
                $feature_id = $filter['feature_id'];
                if (!empty($feature_id)) {
                    $restrict_by["feature_{$feature_id}" ] = join('|', $selected_filters[$filter_id]);

                } elseif ($filter['field_type'] == 'A') {
                    $restrict_by["in_stock"] = $selected_filters[$filter_id][0];

                } elseif ($filter['field_type'] == 'F') {
                    $restrict_by['free_shipping'] = $selected_filters[$filter_id][0];

                } elseif ($filter['field_type'] == 'S') {
                    $restrict_by['company_id'] = join('|', $selected_filters[$filter_id]);
                }
            }
        }
    }

    //
    // Timestamp
    //
    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $restrict_by['timestamp'] = "{$params['time_from']},{$params['time_to']}";
    }

    //
    // Price Union
    //
    if (!fn_allowed_for('"ULTIMATE:FREE"')) {
        if (count(Tygh::$app['session']['auth']['usergroup_ids']) > 1) {
            foreach (Tygh::$app['session']['auth']['usergroup_ids'] as $usergroup_id) {
                $_prices[] = 'price_' . $usergroup_id;
            }

            $union['price']['min'] = join('|', $_prices);
        }
    }

    //
    // Price
    //
    $is_price_from = (isset($params['price_from']) && fn_is_numeric($params['price_from']));
    $is_price_to   = (isset($params['price_to']) && fn_is_numeric($params['price_to']));

    if ($is_price_from || $is_price_to) {
        $restrict_by['price'] = (($is_price_from)? $params['price_from'] : '') . ',' . (($is_price_to)? $params['price_to'] : '');
    }

    //
    // Weight
    //
    $is_weight_from = (isset($params['weight_from']) && fn_is_numeric($params['weight_from']));
    $is_weight_to   = (isset($params['weight_to']) && fn_is_numeric($params['weight_to']));

    if ($is_weight_from || $is_weight_to) {
        $restrict_by['weight'] = (($is_weight_from)? $params['weight_from'] : '') . ',' . (($is_weight_to)? $params['weight_to'] : '');
    }

    //
    // Amount
    //
    $is_amount_from = (isset($params['amount_from']) && fn_is_numeric($params['amount_from']));
    $is_amount_to   = (isset($params['amount_to']) && fn_is_numeric($params['amount_to']));

    if ($is_amount_from || $is_amount_to) {
        $restrict_by['amount'] = (($is_amount_from)? $params['amount_from'] : '') . ',' . (($is_amount_to)? $params['amount_to'] : '');
    }

    //
    // Popularity
    //
    $is_popularity_from = (isset($params['popularity_from']) && fn_is_numeric($params['popularity_from']));
    $is_popularity_to   = (isset($params['popularity_to']) && fn_is_numeric($params['popularity_to']));

    if ($is_popularity_from || $is_popularity_to) {
        $restrict_by['popularity'] = (($is_popularity_from)? $params['popularity_from'] : '') . ',' . (($is_popularity_to)? $params['popularity_to'] : '');
    }

    if (isset($params['pcode']) && fn_string_not_empty($params['pcode'])) {
        if (empty($params['pcode_from_q'])) { // FIXME This is a workaround. See @1-14957 for details.
            $query_by['product_code'] = trim($params['pcode']);
        }
    }

    return array($restrict_by, $query_by, $union);
}

function fn_searchanise_get_products_before_select(&$params, &$join, &$condition, &$u_condition, &$inventory_condition, &$sortings, &$total, &$items_per_page, &$lang_code, &$having)
{
    if (
        AREA != 'C' ||
        empty($params['q']) ||
        empty($params['search_performed']) ||
        empty($params['dispatch']) ||
        $params['dispatch'] != 'products.search' && $params['dispatch'] != 'companies.products' ||
        (!empty($_REQUEST['sort_by']) && !in_array($_REQUEST['sort_by'], fn_se_get_valid_sortings())) ||
        fn_se_check_disabled() ||
        !empty($params['disable_searchanise']) ||
        fn_se_get_import_status(fn_se_get_company_id(), CART_LANGUAGE) != 'done'
    ) {
        return;
    }

    list($restrict_by, $query_by, $union) = fn_se_prepare_request_params($params);

    //
    // Categories
    //
    if (!empty($params['cid'])) {
        $cids = is_array($params['cid']) ? $params['cid'] : array($params['cid']);

        $c_condition = '';

        if (AREA == 'C') {
            $_c_statuses = array('A', 'H');// Show enabled categories
            $cids = db_get_fields("SELECT a.category_id FROM ?:categories as a WHERE a.category_id IN (?n) AND a.status IN (?a)", $cids, $_c_statuses);
            $c_condition = db_quote('AND a.status IN (?a) AND (' . fn_find_array_in_set(Tygh::$app['session']['auth']['usergroup_ids'], 'a.usergroup_ids', true) . ')', $_c_statuses);
        }

        $sub_categories_ids = db_get_fields("SELECT a.category_id FROM ?:categories as a LEFT JOIN ?:categories as b ON b.category_id IN (?n) WHERE a.id_path LIKE CONCAT(b.id_path, '/%') ?p", $cids, $c_condition);
        $sub_categories_ids = fn_array_merge($cids, $sub_categories_ids, false);

        if (empty($sub_categories_ids)) {
            $params['force_get_by_ids'] = true;
            $params['pid'] = $params['product_id'] = 0;

            return;
        }

        if (!empty($params['subcats']) && $params['subcats'] == 'Y') {
            $restrict_by['category_id'] = join('|', $sub_categories_ids);
        } else {
            $restrict_by['category_id'] = join('|', $cids);
        }
    }

    //
    // Sortings
    //
    if (!empty($_REQUEST['search_performed']) && empty($_REQUEST['sort_by']) && SE_USE_RELEVANCE_AS_DEFAULT_SORTING == 'Y') {
        $params['sort_by'] = 'relevance';
        $params['sort_order'] = 'asc';
    }

    if (!empty($params['sort_by']) && !in_array($params['sort_by'], fn_se_get_valid_sortings())) {
        return;
    }

    if ($params['sort_by'] == 'product') {
        $sort_by = 'title';
    } elseif ($params['sort_by'] == 'relevance') {
        $params['sort_order'] = 'asc';
        $sort_by = 'relevance';
    } else {
        $sort_by = $params['sort_by'];
    }

    $sort_order = ($params['sort_order'] == 'asc') ? 'asc' : 'desc';

    //
    // Items_per_page
    //
    $items_per_page = empty($params['items_per_page']) ? 10 : (int) $params['items_per_page'];

    if (!empty($params['limit'])) {
        $max_results = $params['limit'];
    } else {
        $max_results = $items_per_page;
    }

    $get_items = true;
    $get_facets = (!fn_allowed_for('ULTIMATE:FREE'))? true : false;

    $request_params = array(
        'sortBy'     => $sort_by,
        'sortOrder'  => $sort_order,

        'union'      => $union,
        'queryBy'    => $query_by,
        'restrictBy' => $restrict_by,

        'items'      => ($get_items == true)? 'true' : 'false',
        'facets'     => ($get_facets == true)? 'true' : 'false',

        'maxResults' => $max_results,
        'startIndex' => ($params['page'] - 1) * $items_per_page,
    );

    if ($request_params['sortBy'] == 'null') {
        unset($request_params['sortBy']);
    }

    if (!empty($params['q']) && fn_strlen($params['q']) > 0) {
        $request_params['q'] = $params['q'];
        $request_params['suggestions'] = 'true';
        $request_params['query_correction'] = 'false';
        $request_params['suggestionsMaxResults'] = 1;
    } else {
        $request_params['q'] = '';
    }

    $result = fn_searchanise_send_search_request($request_params, $lang_code);
    if ($result == false) {
        //revert to standart sorting
        if ($params['sort_by'] == 'relevance') {
            $params = array_merge($params, fn_get_default_products_sorting());
        }
        Registry::set('runtime.se_use_relevance_sorting', false);

        return;
    }

    if (!empty($result['suggestions']) && count($result['suggestions']) > 0) {
        $params['suggestion'] = reset($result['suggestions']);
    }

    if (!empty($result['items'])) {
        foreach ($result['items'] as $product) {
            $params['pid'][] = $product['product_id'];
        }
        if ($params['sort_by'] == 'relevance') {
            $sortings['relevance'] = "FIELD(products.product_id, '" . join("','", $params['pid']) . "')";
            $params['sort_order'] = 'asc';
        }
    } else {
        $products = array();
        $params['force_get_by_ids'] = true;
        $params['pid'] = $params['product_id'] = 0;
    }

    if (isset($result['facets'])) {
        Registry::set('searchanise.received_facets', $result['facets']);
    }

    $total = $result['totalItems'];
    $params['limit'] = $items_per_page; // need to set it manually for proper pagination

    // reset condition with text search && filtering params  - we are get all control under process of  text search and filtering
    $condition = '';
    $join = '';

    return;
}

function fn_searchanise_get_filters_products_count($params = array(), $lang_code = CART_LANGUAGE)
{
    if (
        empty($params['q']) ||
        empty($params['search_performed']) ||
        empty($params['dispatch']) ||
        $params['dispatch'] != 'products.search' && $params['dispatch'] != 'companies.products' ||
        fn_se_check_disabled() ||
        !empty($params['disable_searchanise']) ||
        fn_se_get_import_status(fn_se_get_company_id(), CART_LANGUAGE) != 'done'
    ) {
        return fn_get_filters_products_count($params, $lang_code);
    }

    $received_facets = Registry::get('searchanise.received_facets');
    if (is_null($received_facets)) {
         return fn_get_filters_products_count($params, $lang_code);
    }

    $_params = array('status' => 'A', 'get_variants' => true);
    if (!empty($params['item_ids'])) {
        $_params['item_ids'] = $params['item_ids'];
    }
    list($stored_filters, ) = fn_get_product_filters($_params, 0, $lang_code);

    if (!empty($params['features_hash'])) {
        //
        // Get without
        //
        list($restrict_by, $query_by, $union) = fn_se_prepare_request_params(array_merge($params, array('features_hash' => '')));

        $request_params = array(
            'items'  => 'false',
            'facets' => 'true',
            'q'      => $params['q'],

            'union'      => $union,
            'queryBy'    => $query_by,
            'restrictBy' => $restrict_by,
        );
        $result = fn_searchanise_send_search_request($request_params);

        if (empty($result)) {
            return fn_get_filters_products_count($params);
        } else {
            $clear_rfacets = $result['facets'];
        }
    }

    $filters = array();
    foreach($stored_filters as $sfilter) {
        $sfilter_id = $sfilter['filter_id'];
        $filter = array(
            'feature_id' => $sfilter['feature_id'],
            'filter_id'  => $sfilter_id,
            'field_type' => $sfilter['field_type'],
            'round_to'   => $sfilter['round_to'],
            'display'    => $sfilter['display'],
            'display_count' => $sfilter['display_count'],
            'filter'     => $sfilter['filter'],
            'feature_type' => $sfilter['feature_type'],
            'prefix'     => $sfilter['prefix'],
            'suffix'     => $sfilter['suffix'],
        );

        $rfilter = false;
        foreach($received_facets as $rfacet) {
            if (
                $sfilter['field_type'] == 'P' && $rfacet['attribute'] == "price" ||
                $sfilter['field_type'] == 'F' && $rfacet['attribute'] == "free_shipping" ||
                $sfilter['field_type'] == 'A' && $rfacet['attribute'] == "in_stock" ||
                $sfilter['field_type'] == 'S' && $rfacet['attribute'] == "company_id" ||
                "feature_{$sfilter['feature_id']}" == $rfacet['attribute']
            ) {
                $rfilter = $rfacet;
                break;
            }
        }

        if (empty($rfilter)) {
            continue;
        }

        $crfilter = false;
        foreach($clear_rfacets as $crfacet) {
            if (
                $sfilter['field_type'] == 'P' && $crfacet['attribute'] == "price" ||
                $sfilter['field_type'] == 'F' && $crfacet['attribute'] == "free_shipping" ||
                $sfilter['field_type'] == 'A' && $crfacet['attribute'] == "in_stock" ||
                $sfilter['field_type'] == 'S' && $crfacet['attribute'] == "company_id" ||
                "feature_{$sfilter['feature_id']}" == $crfacet['attribute']
            ) {
                $crfilter = $crfacet;
                break;
            }
        }

        if (
            $sfilter['feature_type'] == ProductFeatures::NUMBER_FIELD ||
            $sfilter['feature_type'] == ProductFeatures::NUMBER_SELECTBOX ||
            $sfilter['feature_type'] == ProductFeatures::DATE ||
            (!empty($sfilter['condition_type']) && $sfilter['condition_type'] == 'D')
        ) {
            if ($sfilter['field_type'] == 'P') {
                $filter_fields = fn_get_product_filter_fields();
                $convert = $filter_fields['P']['convert'];
                list($rfilter['buckets'][0]['from'], $rfilter['buckets'][0]['to']) = $convert($rfilter['buckets'][0]['from'], $rfilter['buckets'][0]['to']);
                if (!empty($rfilter['buckets'][0]['selected'])) {
                    list($rfilter['buckets'][0]['left'], $rfilter['buckets'][0]['right']) = $convert($rfilter['buckets'][0]['left'], $rfilter['buckets'][0]['right']);
                }
            }


            $filter['min']    = Math::ceilToPrecision($rfilter['buckets'][0]['from'], $sfilter['round_to']);
            $filter['max']    = Math::ceilToPrecision($rfilter['buckets'][0]['to'], $sfilter['round_to']);
            $filter['extra']  = CART_SECONDARY_CURRENCY; //TODO
            $filter['slider'] = true;

            if (!empty($rfilter['buckets'][0]['selected'])) {
                $filter['left']  = Math::floorToPrecision($rfilter['buckets'][0]['left'], $sfilter['round_to']);
                $filter['right'] = Math::floorToPrecision($rfilter['buckets'][0]['right'], $sfilter['round_to']);
                $filter['selected_range'] = true;
            }

        } elseif (
            $sfilter['feature_type'] == ProductFeatures::SINGLE_CHECKBOX ||
            $sfilter['field_type'] == 'F' && $rfilter['attribute'] == "free_shipping" ||
            $sfilter['field_type'] == 'A' && $rfilter['attribute'] == "in_stock"
        ) {
            if (!empty($rfilter['buckets'][0])) {
                $rvariant = $rfilter['buckets'][0];
                $insert_to = (!empty($rvariant['selected']))? 'selected_variants' : 'variants';
                $filter[$insert_to]['Y'] = array(
                    'variant_id' => 'Y',
                    'variant'    => __('yes'),
                );
            }

        } else {
            if ($sfilter['field_type'] == 'S' && $rfilter['attribute'] == "company_id") {
                $sfilter['variants'] = db_get_hash_array("SELECT c.company_id as variant_id, c.company as variant FROM ?:companies AS c WHERE c.status = 'A' ORDER BY c.company ASC", 'variant_id');
            }

            //
            // Fill Variants
            //
            foreach($rfilter['buckets'] as $rvariant) {
                $variant_id = $rvariant['value'];
                $insert_to = (!empty($rvariant['selected']))? '_selected_variants' : '_variants';
                $filter[$insert_to][$variant_id] = array(
                    'variant_id' => $variant_id,
                    'variant'    => $sfilter['variants'][$variant_id]['variant'],
                    'position'   => 0,
                );
            }

            //
            // Disabled
            //
            if (!empty($crfilter)) {
                foreach ($crfilter['buckets'] as $crvariant) {
                    $variant_id = $crvariant['value'];
                    if (empty($filter['_selected_variants'][$variant_id]) && empty($filter['_variants'][$variant_id])) {
                        $filter['_variants'][$variant_id] = array(
                            'variant_id' => $variant_id,
                            'variant'    => $sfilter['variants'][$variant_id]['variant'],
                            'position'   => 0,
                            'disabled'  => true,
                        );
                    }
                }
            }

            //
            // Sort
            //
            foreach($sfilter['variants'] as $variant_id => $variant) {
                if (!empty($filter['_variants'][$variant_id]) && empty($filter['_variants'][$variant_id]['disabled'])) {
                    $filter['variants'][$variant_id] = $filter['_variants'][$variant_id];

                } elseif (!empty($filter['_selected_variants'][$variant_id])) {
                    $filter['selected_variants'][$variant_id] = $filter['_selected_variants'][$variant_id];
                }
            }

            foreach($sfilter['variants'] as $variant_id => $variant) {
                if (!empty($filter['_variants'][$variant_id]) && !empty($filter['_variants'][$variant_id]['disabled'])) {
                    $filter['variants'][$variant_id] = $filter['_variants'][$variant_id];
                }
            }

            unset($filter['_variants'], $filter['_selected_variants']);
        }

        $filters[$sfilter_id] = $filter;
    }

    return array($filters);
}

/**
 * Adds additional actions after product feature updating
 *
 * @param array  $feature_data     Feature data
 * @param int    $feature_id       Feature identifier
 * @param array  $deleted_variants Deleted product feature variants identifiers
 * @param string $lang_code        2-letters language code
 */
function fn_searchanise_update_product_feature_post($feature_data, $feature_id, $deleted_variants, $lang_code)
{
    //Send products with Select->Number feature
    if (!empty($feature_id) && !empty($feature_data['feature_type']) && $feature_data['feature_type'] == ProductFeatures::NUMBER_SELECTBOX) {
        $product_ids = db_get_fields('SELECT product_id FROM ?:product_features_values WHERE feature_id = ?i AND lang_code = ?s', $feature_id, DEFAULT_LANGUAGE);

        fn_se_add_chunk_product_action('update', $product_ids);
    }
}

function fn_searchanise_update_product_filter($filter_data, $filter_id, $lang_code)
{
    fn_se_add_action('facet_update', $filter_id);
}

function fn_searchanise_delete_product_filter_pre($filter_id)
{
    $filter = db_get_row("SELECT * FROM ?:product_filters WHERE filter_id = ?i LIMIT 1", $filter_id);

    if (!empty($filter['feature_id'])) {
        $facet_attribute = 'feature_' . $filter['feature_id'];
    } elseif ($filter['field_type'] == 'P') {
        $facet_attribute = 'price';
    } elseif ($filter['field_type'] == 'F') {
        $facet_attribute = 'free_shipping';
    } elseif ($filter['field_type'] == 'S') {
        $facet_attribute = 'company_id';
    } elseif ($filter['field_type'] == 'A') {
        $facet_attribute = 'amount';
    } else {
        return;
    }

    $dublicate = db_get_field("SELECT count(*) FROM ?:product_filters WHERE feature_id = ?i AND field_type = ?s LIMIT 1", $filter['feature_id'], $filter['field_type']);

    if ($dublicate > 1) {
        return; // we have dublicate filter => no request
    }

    fn_se_add_action('facet_delete', $facet_attribute);
}

function fn_se_get_json_header()
{
    return array(
        'header' => array(
            'id'      => Registry::get('config.http_location'),
            'updated' => date('c'),
        ),
    );
}

function fn_se_send_addon_status_request($status = 'enabled', $company_id = NULL, $lang_code = NULL)
{
    $engines_data = fn_se_get_engines_data($company_id, $lang_code, true);

    if ($engines_data) {
        foreach ($engines_data as $engine_data) {
            $private_key = fn_se_get_private_key($engine_data['company_id'], $engine_data['lang_code']);
            fn_se_send_request('/api/state/update/json', $private_key, array('addon_status' => $status));
        }
    }
}

function fn_se_send_request($url_part, $private_key, $data)
{
    if (empty($private_key)) {
        return;
    }

    $params = array('private_key' => $private_key) + $data;

    Registry::set('log_cut', true);

    $result = Http::post(SE_SERVICE_URL . $url_part, $params, array(
        'timeout' => SE_REQUEST_TIMEOUT
    ));

    $response = fn_se_parse_response($result, false);

    fn_se_set_simple_setting('last_request', TIME);

    return $response;
}

function fn_se_parse_response($response, $show_notification = false)
{
    $data = json_decode($response, true);

    if (empty($data)) {
        return false;
    }

    if (!empty($data['errors']) && is_array($data['errors'])) {
        foreach ($data['errors'] as $e) {
            if ($show_notification == true) {
                fn_set_notification('E', __('error'), 'Searchanise: ' . (string) $e);
            }
        }

        return false;

    } elseif ($data === 'ok') {
        return true;

    } else {
        return $data;
    }
}

function fn_se_parse_state_response($response)
{
    $data = array();
    if (empty($response)) {
        return false;
    }

    if (!empty($response['variable'])) {
        foreach ($response['variable'] as $name => $v) {
            $data[$name] = (string) $v;
        }

        return $data;
    }

    return false;
}

function fn_se_get_ids($items, $name = 'product_id')
{
    $ids = array();

    foreach ((array) $items as $v) {
        $ids[] = $v[$name];
    }

    return $ids;
}

function fn_se_delete_keys($company_id = NULL, $lang_code = NULL)
{
    $engines_data = fn_se_get_engines_data($company_id, $lang_code, true);
    foreach ($engines_data as $engine_data) {
        $c_id = $engine_data['company_id'];
        $l_code = $engine_data['lang_code'];

        fn_se_send_addon_status_request('deleted', $c_id, $l_code);
        db_query("DELETE FROM ?:se_settings WHERE company_id = ?i AND lang_code = ?s AND name IN ('api_key', 'private_key', 'import_status', 'parent_private_key')", $c_id, $l_code);
        db_query("DELETE FROM ?:se_queue WHERE company_id = ?i AND lang_code = ?s", $c_id, $l_code);
    }

    fn_se_get_all_settings(true);// call to update cache

    return true;
}

/**
 * Adds additional actions before languages deleting
 *
 * @param array $lang_ids List of language ids
 */
function fn_searchanise_delete_languages_pre($lang_ids)
{
    foreach ((array) $lang_ids as $lang_id) {
        fn_se_delete_keys(NULL, $lang_id);
    }
}

/**
 * Adds additional actions after language update
 *
 * @param array  $language_data Language data
 * @param string $lang_id       language id
 * @param string $action        Current action ('add', 'update' or bool false if failed to update language)
 */
function fn_searchanise_update_language_post($language_data, $lang_id, $action)
{
    fn_set_notification('N', __('notice'), __('text_se_re_indexation_required', array(
        '[link]' => fn_url('addons.update?addon=searchanise')
    )));
}

function fn_searchanise_delete_company_pre($company_id)
{
    fn_se_delete_keys($company_id, NULL);
}

function fn_searchanise_update_company($company_data, $company_id, $lang_code, $action)
{
    if (fn_se_signup($company_id, NULL, false) == true) {
        fn_se_queue_import($company_id, NULL, false);
        fn_set_notification('N', __('notice'), __('text_se_new_engine_store', array(
            '[store]' => $company_data['company']
        )));
    }
}

function fn_searchanise_change_company_status_pre($company_id, $status_to, $reason, $status_from, $skip_query, $notify)
{
    if ($status_to != $status_from) {
        if (fn_allowed_for('MULTIVENDOR')) {
            fn_se_queue_import($company_id, NULL, false);
        }
    }
}


function fn_se_check_import_is_done($company_id = NULL, $lang_code = NULL)
{
    $skip_time_check = false;
    $engines_data = fn_se_get_engines_data($company_id, $lang_code);

    if ($engines_data) {
        foreach ($engines_data as $engine_data) {
            $c_id = $engine_data['company_id'];
            $l_code = $engine_data['lang_code'];

            if ($engine_data['import_status'] == 'sent') {
                if ((TIME - fn_se_get_simple_setting('last_request')) > 10 ||
                    (fn_se_get_simple_setting('last_request') - 10) > TIME || // It is need if last_request incorrect.
                    $skip_time_check == true) {
                    $response = fn_se_send_request('/api/state/get/json', fn_se_get_private_key($c_id, $l_code), array('status' => '', 'full_import' => ''));

                    $variables = fn_se_parse_state_response($response);

                    if (!empty($variables) && isset($variables['status'])) {
                        if ($variables['status'] == 'normal' && $variables['full_import'] == 'done') {
                            $skip_time_check = true;
                            fn_se_set_import_status('done', $c_id, $l_code);
                        } elseif ($variables['status'] == 'disabled') {
                            fn_se_set_import_status('none', $c_id, $l_code); //disable status check for disabled engine
                        }
                    }
                }
            }
        }
    }
}

function fn_se_check_disabled()
{
    $check = false;
    if (isset($_REQUEST['disabled_module_searchanise'])) {
       $check =  $_REQUEST['disabled_module_searchanise'] == 'Y';
    }

    return $check;
}

function fn_se_check_debug()
{
    $check = false;
    if (isset($_REQUEST['debug_module_searchanise'])) {
       $check =  $_REQUEST['debug_module_searchanise'] == 'Y';
    }

    return $check;
}
