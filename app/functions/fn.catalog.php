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

use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProductFeatures;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\ProductTabs;
use Tygh\Navigation\LastView;
use Tygh\Languages\Languages;
use Tygh\Themes\Themes;
use Tygh\Tools\Math;
use Tygh\Tools\SecurityHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

const FILTERS_HASH_SEPARATOR = '_';
const FILTERS_HASH_FEATURE_SEPARATOR = '-';
const OPTION_EXCEPTION_VARIANT_ANY = -1;
const OPTION_EXCEPTION_VARIANT_NOTHING = -2;

// ------------------------- 'Products' object functions ------------------------------------

/**
 * Gets full product data by its id
 *
 * @param int $product_id Product ID
 * @param mixed $auth Array with authorization data
 * @param string $lang_code 2 letters language code
 * @param string $field_list List of fields for retrieving
 * @param boolean $get_add_pairs Get additional images
 * @param boolean $get_main_pair Get main images
 * @param boolean $get_taxes Get taxes
 * @param boolean $get_qty_discounts Get quantity discounts
 * @param boolean $preview Is product previewed by admin
 * @param boolean $features Get product features
 * @param boolean $skip_company_condition Skip company condition and retrieve product data for displayin on other store page. (Works only in ULT)
 * @return mixed Array with product data
 */
function fn_get_product_data($product_id, &$auth, $lang_code = CART_LANGUAGE, $field_list = '', $get_add_pairs = true, $get_main_pair = true, $get_taxes = true, $get_qty_discounts = false, $preview = false, $features = true, $skip_company_condition = false, $feature_variants_selected_only = false)
{
    $product_id = intval($product_id);

    /**
     * Change parameters for getting product data
     *
     * @param int     $product_id             Product ID
     * @param mixed   $auth                   Array with authorization data
     * @param string  $lang_code              2 letters language code
     * @param string  $field_list             List of fields for retrieving
     * @param boolean $get_add_pairs          Get additional images
     * @param boolean $get_main_pair          Get main images
     * @param boolean $get_taxes              Get taxes
     * @param boolean $get_qty_discounts      Get quantity discounts
     * @param boolean $preview                Is product previewed by admin
     * @param boolean $features               Get product features
     * @param boolean $skip_company_condition Skip company condition and retrieve product data for displaying on other store page. (Works only in ULT)
     */
    fn_set_hook('get_product_data_pre', $product_id, $auth, $lang_code, $field_list, $get_add_pairs, $get_main_pair, $get_taxes, $get_qty_discounts, $preview, $features, $skip_company_condition);

    $usergroup_ids = !empty($auth['usergroup_ids']) ? $auth['usergroup_ids'] : array();

    if (!empty($product_id)) {

        if (empty($field_list)) {
            $descriptions_list = "?:product_descriptions.*";
            $field_list = "?:products.*, $descriptions_list";
        }
        $field_list .= ", MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price, ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100)) as price";
        $field_list .= ", GROUP_CONCAT(IF(?:products_categories.link_type = 'M', CONCAT(?:products_categories.category_id, 'M'), ?:products_categories.category_id)) as category_ids";
        $field_list .= ", popularity.total as popularity";

        $price_usergroup = db_quote(" AND ?:product_prices.usergroup_id IN (?n)", ((AREA == 'A' && !defined('ORDER_MANAGEMENT')) ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $usergroup_ids)));

        $_p_statuses = array('A', 'H');
        $_c_statuses = array('A', 'H');

        $condition = $join = $avail_cond = '';

        if (!fn_allowed_for('ULTIMATE')) {
            $avail_cond .= fn_get_company_condition('?:products.company_id');
        } else {
            if (!$skip_company_condition && Registry::get('runtime.company_id')) {
                if (AREA == 'C') {
                    $avail_cond .= fn_get_company_condition('?:categories.company_id');
                } else {
                    $avail_cond .= ' AND (' . fn_get_company_condition('?:categories.company_id', false) . ' OR ' . fn_get_company_condition('?:products.company_id', false) . ')';
                }
            }

            if (Registry::get('runtime.company_id')) {
                $field_list .= ', IF('
                        . 'shared_prices.product_id IS NOT NULL,'
                        . 'MIN(IF(shared_prices.percentage_discount = 0, shared_prices.price, shared_prices.price - (shared_prices.price * shared_prices.percentage_discount)/100)),'
                        . 'MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price, ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100))'
                    . ') as price'
                ;
                $shared_prices_usergroup = db_quote(" AND shared_prices.usergroup_id IN (?n)", ((AREA == 'A' && !defined('ORDER_MANAGEMENT')) ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $usergroup_ids)));
                $join .= db_quote(' LEFT JOIN ?:ult_product_prices shared_prices ON shared_prices.product_id = ?:products.product_id AND shared_prices.company_id = ?i AND shared_prices.lower_limit = 1 ?p', Registry::get('runtime.company_id'), $shared_prices_usergroup);
            }
        }

        $avail_cond .= (AREA == 'C' && empty($preview)) ? ' AND (' . fn_find_array_in_set($usergroup_ids, "?:categories.usergroup_ids", true) . ')' : '';
        $avail_cond .= (AREA == 'C' && empty($preview)) ? ' AND (' . fn_find_array_in_set($usergroup_ids, "?:products.usergroup_ids", true) . ')' : '';
        $avail_cond .= (AREA == 'C' && empty($preview)) ? db_quote(' AND ?:categories.status IN (?a) AND ?:products.status IN (?a)', $_c_statuses, $_p_statuses) : '';

        $avail_cond .= fn_get_localizations_condition('?:products.localization');
        $avail_cond .= fn_get_localizations_condition('?:categories.localization');

        if (AREA == 'C' && !$preview) {
            $field_list .= ', companies.company as company_name';
            $condition .= " AND (companies.status = 'A' OR ?:products.company_id = 0) ";
            $join .= " LEFT JOIN ?:companies as companies ON companies.company_id = ?:products.company_id";
        }

        $join .= " INNER JOIN ?:products_categories ON ?:products_categories.product_id = ?:products.product_id INNER JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id $avail_cond";
        $join .= " LEFT JOIN ?:product_popularity as popularity ON popularity.product_id = ?:products.product_id";

        /**
         * Change SQL parameters for product data select
         *
         * @param int $product_id Product ID
         * @param string $field_list List of fields for retrieving
         * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param mixed $auth Array with authorization data
         * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
         * @param string $condition Condition for selecting product data
         */
        fn_set_hook('get_product_data', $product_id, $field_list, $join, $auth, $lang_code, $condition);

        $product_data = db_get_row("SELECT $field_list FROM ?:products LEFT JOIN ?:product_prices ON ?:product_prices.product_id = ?:products.product_id AND ?:product_prices.lower_limit = 1 ?p LEFT JOIN ?:product_descriptions ON ?:product_descriptions.product_id = ?:products.product_id AND ?:product_descriptions.lang_code = ?s ?p WHERE ?:products.product_id = ?i ?p GROUP BY ?:products.product_id", $price_usergroup, $lang_code, $join, $product_id, $condition);

        if (empty($product_data)) {
            return false;
        }

        $product_data['base_price'] = $product_data['price']; // save base price (without discounts, etc...)

        list($product_data['category_ids'], $product_data['main_category']) = fn_convert_categories($product_data['category_ids']);

        // Generate meta description automatically
        if (!empty($product_data['full_description']) && empty($product_data['meta_description']) && defined('AUTO_META_DESCRIPTION') && AREA != 'A') {
            $product_data['meta_description'] = fn_generate_meta_description($product_data['full_description']);
        }

        // If tracking with options is enabled, check if at least one combination has positive amount
        if (!empty($product_data['tracking']) && $product_data['tracking'] == ProductTracking::TRACK_WITH_OPTIONS) {
            $product_data['amount'] = db_get_field("SELECT MAX(amount) FROM ?:product_options_inventory WHERE product_id = ?i", $product_id);
        }

        $product_data['product_id'] = $product_id;

        // Get product shipping settings
        if (!empty($product_data['shipping_params'])) {
            $product_data = array_merge(unserialize($product_data['shipping_params']), $product_data);
        }

        // Get main image pair
        if ($get_main_pair == true) {
            $product_data['main_pair'] = fn_get_image_pairs($product_id, 'product', 'M', true, true, $lang_code);
        }

        // Get additional image pairs
        if ($get_add_pairs == true) {
            $product_data['image_pairs'] = fn_get_image_pairs($product_id, 'product', 'A', true, true, $lang_code);
        }

        // Get taxes
        $product_data['tax_ids'] = !empty($product_data['tax_ids']) ? explode(',', $product_data['tax_ids']) : array();

        // Get qty discounts
        if ($get_qty_discounts == true) {
            fn_get_product_prices($product_id, $product_data, $auth);
        }

        if (fn_allowed_for('ULTIMATE')) {
            $product_data['shared_product'] = fn_ult_is_shared_product($product_id);
        }

        if ($features) {
            // Get product features
            $path = !empty($product_data['category_ids']) ? fn_get_category_ids_with_parent($product_data['category_ids']) : '';

            $_params = array(
                'category_ids' => $path,
                'product_id' => $product_id,
                'product_company_id' => !empty($product_data['company_id']) ? $product_data['company_id'] : 0,
                'statuses' => AREA == 'C' ? array('A') : array('A', 'H'),
                'variants' => true,
                'plain' => false,
                'display_on' => AREA == 'A' ? '' : 'product',
                'existent_only' => (AREA != 'A'),
                'variants_selected_only' => $feature_variants_selected_only
            );
            list($product_data['product_features']) = fn_get_product_features($_params, 0, $lang_code);

            if (AREA == 'C') {
                $product_data['header_features'] = fn_get_product_features_list($product_data, 'H');
            }
        } else {
            $product_data['product_features'] = fn_get_product_features_list($product_data, 'A');
        }

    } else {
        return false;
    }

    $product_data['detailed_params']['info_type'] = 'D';

    /**
     * Particularize product data
     *
     * @param array   $product_data List with product fields
     * @param mixed   $auth         Array with authorization data
     * @param boolean $preview      Is product previewed by admin
     * @param string  $lang_code    2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_data_post', $product_data, $auth, $preview, $lang_code);

    return (!empty($product_data) ? $product_data : false);
}

/**
 * Gets feature name by id
 *
 * @param mixed $feature_id Integer feature id, or array of feature ids
 * @param string $lang_code 2-letter language code
 * @param boolean $as_array Flag: if set, result will be returned as array <i>(feature_id => feature)</i>; otherwise only feature name will be returned
 * @return mixed In case 1 <i>feature_id</i> is passed and <i>as_array</i> is not set, a feature name string is returned;
 * Array <i>(feature_id => feature)</i> for all given <i>feature_ids</i>;
 * <i>False</i> if <i>$feature_id</i> is not defined
 */
function fn_get_feature_name($feature_id, $lang_code = CART_LANGUAGE, $as_array = false)
{
    /**
     * Change parameters for getting feature name
     *
     * @param int/array $feature_id Feature integer identifier
     * @param string    $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean   $as_array   Flag determines if even one feature name should be returned as array
     */
    fn_set_hook('get_feature_name_pre', $feature_id, $lang_code, $as_array);

    $result = false;
    if (!empty($feature_id)) {
        if (!is_array($feature_id) && strpos($feature_id, ',') !== false) {
            $feature = explode(',', $feature_id);
        }

        $field_list = 'fd.feature_id as feature_id, fd.description as feature';
        $join = '';
        if (is_array($feature_id) || $as_array == true) {
            $condition = db_quote(' AND fd.feature_id IN (?n) AND fd.lang_code = ?s', $feature_id, $lang_code);
        } else {
            $condition = db_quote(' AND fd.feature_id = ?i AND fd.lang_code = ?s', $feature_id, $lang_code);
        }

        /**
        * Change SQL parameters for getting feature name
        *
        * @param int/array $feature_id Feature integer identifier
        * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
        * @param boolean $as_array Flag determines if even one feature name should be returned as array
        * @param string $field_list List of fields for retrieving
        * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
        * @param string $condition Condition for selecting feature name
        */
        fn_set_hook('get_feature_name', $feature_id, $lang_code, $as_array, $field_list, $join, $condition);

        $result = db_get_hash_single_array("SELECT $field_list FROM ?:product_features_descriptions fd $join WHERE 1 $condition", array('feature_id', 'feature'));
        if (!(is_array($feature_id) || $as_array == true)) {
            if (isset($result[$feature_id])) {
                $result = $result[$feature_id];
            } else {
                $result = null;
            }
        }
    }

    /**
     * Change feature name selected by $feature_id & $lang_code params
     *
     * @param int/array    $feature_id Feature integer identifier
     * @param string       $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean      $as_array   Flag determines if even one feature name should be returned as array
     * @param string/array $result     String containig feature name or array with features names depending on $feature_id param
     */
    fn_set_hook('get_feature_name_post', $feature_id, $lang_code, $as_array, $result);

    return $result;
}

/**
 * Gets product name by id
 *
 * @param mixed $product_id Integer product id, or array of product ids
 * @param string $lang_code 2-letter language code
 * @param boolean $as_array Flag: if set, result will be returned as array <i>(product_id => product)</i>; otherwise only product name will be returned
 * @return mixed In case 1 <i>product_id</i> is passed and <i>as_array</i> is not set, a product name string is returned;
 * Array <i>(product_id => product)</i> for all given <i>product_ids</i>;
 * <i>False</i> if <i>$product_id</i> is not defined
 */
function fn_get_product_name($product_id, $lang_code = CART_LANGUAGE, $as_array = false)
{
    /**
     * Change parameters for getting product name
     *
     * @param int/array $product_id Product integer identifier
     * @param string    $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean   $as_array   Flag determines if even one product name should be returned as array
     */
    fn_set_hook('get_product_name_pre', $product_id, $lang_code, $as_array);

    $result = false;
    if (!empty($product_id)) {
        if (!is_array($product_id) && strpos($product_id, ',') !== false) {
            $product_id = explode(',', $product_id);
        }

        $field_list = 'pd.product_id as product_id, pd.product as product';
        $join = '';
        if (is_array($product_id) || $as_array == true) {
            $condition = db_quote(' AND pd.product_id IN (?n) AND pd.lang_code = ?s', $product_id, $lang_code);
        } else {
            $condition = db_quote(' AND pd.product_id = ?i AND pd.lang_code = ?s', $product_id, $lang_code);
        }

        /**
        * Change SQL parameters for getting product name
        *
        * @param int/array $product_id Product integer identifier
        * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
        * @param boolean $as_array Flag determines if even one product name should be returned as array
        * @param string $field_list List of fields for retrieving
        * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
        * @param string $condition Condition for selecting product name
        */
        fn_set_hook('get_product_name', $product_id, $lang_code, $as_array, $field_list, $join, $condition);

        $result = db_get_hash_single_array("SELECT $field_list FROM ?:product_descriptions pd $join WHERE 1 $condition", array('product_id', 'product'));
        if (!(is_array($product_id) || $as_array == true)) {
            if (isset($result[$product_id])) {
                $result = $result[$product_id];
            } else {
                $result = null;
            }
        }
    }

    /**
     * Change product name selected by $product_id & $lang_code params
     *
     * @param int/array    $product_id Product integer identifier
     * @param string       $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean      $as_array   Flag determines if even one product name should be returned as array
     * @param string/array $result     String containig product name or array with products names depending on $product_id param
     */
    fn_set_hook('get_product_name_post', $product_id, $lang_code, $as_array, $result);

    return $result;
}

/**
 * Gets product price by id
 *
 * @param int $product_id Product id
 * @param int $amount Optional parameter: necessary to calculate quantity discounts
 * @param array $auth Array of authorization data
 * @return float Price
 */
function fn_get_product_price($product_id, $amount, &$auth)
{
    /**
     * Change parameters for getting product price
     *
     * @param int   $product_id Product identifier
     * @param int   $amount     Amount of products, required to get wholesale price
     * @param array $auth       Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     */
    fn_set_hook('get_product_price_pre', $product_id, $amount, $auth);

    $usergroup_condition = db_quote("AND ?:product_prices.usergroup_id IN (?n)", ((AREA == 'C' || defined('ORDER_MANAGEMENT')) ? array_merge(array(USERGROUP_ALL), $auth['usergroup_ids']) : USERGROUP_ALL));

    $price = db_get_field(
        "SELECT MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price, "
            . "?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100)) as price "
        . "FROM ?:product_prices "
        . "WHERE lower_limit <=?i AND ?:product_prices.product_id = ?i ?p "
        . "ORDER BY lower_limit DESC LIMIT 1",
        $amount, $product_id, $usergroup_condition
    );

    /**
     * Change product price
     *
     * @param int   $product_id Product identifier
     * @param int   $amount     Amount of products, required to get wholesale price
     * @param array $auth       Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     * @param float $price
     */
    fn_set_hook('get_product_price_post', $product_id, $amount, $auth, $price);

    return (empty($price))? 0 : floatval($price);
}

/**
 * Gets product descriptions to the given language
 *
 * @param array $products Array of products
 * @param string $fields List of fields to be translated
 * @param string $lang_code 2-letter language code.
 * @param boolean $translate_options Flag: if set, product options are also translated; otherwise not
 */
function fn_translate_products(&$products, $fields = '',$lang_code = '', $translate_options = false)
{
    /**
     * Change parameters for translating product text data
     *
     * @param array  $products          List of products
     * @param string $fields            Fields of products that should be translated
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $translate_options Flag that defines whether we want to translate product options. Set it to "true" in case you want.
     */
    fn_set_hook('translate_products_pre', $products, $fields, $lang_code, $translate_options);

    if (empty($fields)) {
        $fields = 'product, short_description, full_description';
    }

    foreach ($products as $k => $v) {
        if (!empty($v['deleted_product'])) {
            continue;
        }
        $descriptions = db_get_row("SELECT $fields FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $v['product_id'], $lang_code);
        foreach ($descriptions as $k1 => $v1) {
            $products[$k][$k1] = $v1;
        }
        if ($translate_options && !empty($v['product_options'])) {
            foreach ($v['product_options'] as $k1 => $v1) {
                $option_descriptions = db_get_row("SELECT option_name, option_text, description, comment FROM ?:product_options_descriptions WHERE option_id = ?i AND lang_code = ?s", $v1['option_id'], $lang_code);
                foreach ($option_descriptions as $k2 => $v2) {
                    $products[$k]['product_options'][$k1][$k2] = $v2;
                }

                if ($v1['option_type'] == 'C') {
                    $products[$k]['product_options'][$k1]['variant_name'] = (empty($v1['position'])) ? __('no', '', $lang_code) : __('yes', '', $lang_code);
                } elseif ($v1['option_type'] == 'S' || $v1['option_type'] == 'R') {
                    $variant_description = db_get_field("SELECT variant_name FROM ?:product_option_variants_descriptions WHERE variant_id = ?i AND lang_code = ?s", $v1['value'], $lang_code);
                    $products[$k]['product_options'][$k1]['variant_name'] = $variant_description;
                }
            }
        }
    }

    /**
     * Change translated products data
     *
     * @param array  $products          List of products
     * @param string $fields            Fields of products that should be translated
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $translate_options Flag that defines whether we want to translate product options. Set it to "true" in case you want.
     */
    fn_set_hook('translate_products_post', $products, $fields, $lang_code, $translate_options);
}

/**
 * Gets additional products data
 *
 * @param array $products Array with products
 * @param array $params Array of flags which determines which data should be gathered
 * @return array Array of products with additional information
 */
function fn_gather_additional_products_data(&$products, $params)
{
    /**
     * Change parameters for gathering additional products data
     *
     * @param array $products List of products
     * @param array $params   Parameters for gathering data
     */
    fn_set_hook('gather_additional_products_data_pre', $products, $params);

    if (empty($products)) {
        return;
    }

    // Set default values to input params
    $default_params = array (
        'get_icon' => false,
        'get_detailed' => false,
        'get_additional' => false,
        'get_options' => true,
        'get_discounts' => true,
        'get_features' => false,
        'get_extra' => false,
        'get_taxed_prices' => true,
        'get_for_one_product' => (!is_array(reset($products)))? true : false,
        'detailed_params' => true,
        'features_display_on' => 'C'
    );

    $params = array_merge($default_params, $params);

    $auth = & Tygh::$app['session']['auth'];
    $allow_negative_amount = Registry::get('settings.General.allow_negative_amount');
    $inventory_tracking = Registry::get('settings.General.inventory_tracking');

    if ($params['get_for_one_product']) {
        $products = array($products);
    }

    $product_ids = fn_array_column($products, 'product_id');

    if ($params['get_icon'] || $params['get_detailed']) {
        $products_images = fn_get_image_pairs($product_ids, 'product', 'M', $params['get_icon'], $params['get_detailed'], CART_LANGUAGE);
    }

    if ($params['get_additional']) {
        $additional_images = fn_get_image_pairs($product_ids, 'product', 'A', true, true, CART_LANGUAGE);
    }

    if ($params['get_options']) {
        $product_options = fn_get_product_options($product_ids, CART_LANGUAGE);
    } else {
        $has_product_options = db_get_hash_array("SELECT a.option_id, a.product_id FROM ?:product_options AS a WHERE a.product_id IN (?n) AND a.status = 'A'", 'product_id', $product_ids);
        $has_product_options_links = db_get_hash_array("SELECT c.option_id, c.product_id FROM ?:product_global_option_links AS c LEFT JOIN ?:product_options AS a ON a.option_id = c.option_id WHERE a.status = 'A' AND c.product_id IN (?n)", 'product_id', $product_ids);
    }

    /**
     * Changes before gathering additional products data
     *
     * @param array $product_ids               Array of product identifiers
     * @param array $params                    Parameteres for gathering data
     * @param array $products                  Array of products
     * @param mixed $auth                      Array of user authentication data
     * @param array $products_images           Array with product main images
     * @param array $additional_images         Array with product additional images
     * @param array $product_options           Array with product options
     * @param array $has_product_options       Array of flags determines if product has options
     * @param array $has_product_options_links Array of flags determines if product has option links
     */
    fn_set_hook('gather_additional_products_data_params', $product_ids, $params, $products, $auth, $products_images, $additional_images, $product_options, $has_product_options, $has_product_options_links);

    // foreach $products
    foreach ($products as &$_product) {
        $product = $_product;
        $product_id = $product['product_id'];

        // Get images
        if ($params['get_icon'] == true || $params['get_detailed'] == true) {
            if (empty($product['main_pair']) && !empty($products_images[$product_id])) {
                $product['main_pair'] = reset($products_images[$product_id]);
            }
        }

        if ($params['get_additional'] == true) {
            if (empty($product['image_pairs']) && !empty($additional_images[$product_id])) {
                $product['image_pairs'] = $additional_images[$product_id];
            }
        }

        if (isset($product['price']) && !isset($product['base_price'])) {
            $product['base_price'] = $product['price']; // save base price (without discounts, etc...)
        }

        /**
         * Changes before gathering product options
         *
         * @param array $product Product data
         * @param mixed $auth Array of user authentication data
         * @param array $params Parameteres for gathering data
         */
        fn_set_hook('gather_additional_product_data_before_options', $product, $auth, $params);

        // Convert product categories
        if (!empty($product['category_ids']) && !is_array($product['category_ids'])) {
            list($product['category_ids'], $product['main_category']) = fn_convert_categories($product['category_ids']);
        }

        $product['selected_options'] = empty($product['selected_options']) ? array() : $product['selected_options'];

        // Get product options
        if ($params['get_options'] && !empty($product_options[$product['product_id']])) {
            if (!isset($product['options_type']) || !isset($product['exceptions_type'])) {
                $types = db_get_row('SELECT options_type, exceptions_type FROM ?:products WHERE product_id = ?i', $product['product_id']);
                $product['options_type'] = $types['options_type'];
                $product['exceptions_type'] = $types['exceptions_type'];
            }

            if (empty($product['product_options'])) {
                if (!empty($product['combination'])) {
                    $selected_options = fn_get_product_options_by_combination($product['combination']);
                }

                $product['product_options'] = (!empty($selected_options)) ? fn_get_selected_product_options($product['product_id'], $selected_options, CART_LANGUAGE) : $product_options[$product_id];
            }

            $product = fn_apply_options_rules($product);

            if (!empty($params['get_icon']) || !empty($params['get_detailed'])) {
                // Get product options images
                if (!empty($product['combination_hash']) && !empty($product['product_options'])) {
                    $image = fn_get_image_pairs($product['combination_hash'], 'product_option', 'M', $params['get_icon'], $params['get_detailed'], CART_LANGUAGE);
                    if (!empty($image)) {
                        $product['main_pair'] = $image;
                    }
                }
            }
            $product['has_options'] = !empty($product['product_options']);

            if (!fn_allowed_for('ULTIMATE:FREE')) {
                $exceptions = fn_get_product_exceptions($product['product_id'], true);
                $product = fn_apply_exceptions_rules($product, $exceptions);
            }

            $selected_options = isset($product['selected_options']) ? $product['selected_options'] : array();
            foreach ($product['product_options'] as $option) {
                if (!empty($option['disabled'])) {
                    unset($selected_options[$option['option_id']]);
                }
            }
            $product['selected_options'] = $selected_options;

            // Change price
            if (isset($product['price']) && empty($product['modifiers_price'])) {
                $product['base_modifier'] = fn_apply_options_modifiers($selected_options, $product['base_price'], 'P', array(), array('product_data' => $product));
                $old_price = $product['price'];
                $product['price'] = fn_apply_options_modifiers($selected_options, $product['price'], 'P', array(), array('product_data' => $product));

                if (empty($product['original_price'])) {
                    $product['original_price'] = $old_price;
                }

                $product['original_price'] = fn_apply_options_modifiers($selected_options, $product['original_price'], 'P', array(), array('product_data' => $product));
                $product['modifiers_price'] = $product['price'] - $old_price;
            }

            if (!empty($product['list_price'])) {
                $product['list_price'] = fn_apply_options_modifiers($selected_options, $product['list_price'], 'P', array(), array('product_data' => $product));
            }

            if (!empty($product['prices']) && is_array($product['prices'])) {
                foreach ($product['prices'] as $pr_k => $pr_v) {
                    $product['prices'][$pr_k]['price'] = fn_apply_options_modifiers($selected_options, $pr_v['price'], 'P', array(), array('product_data' => $product));
                }
            }
        } else {
            $product['has_options'] = (!empty($has_product_options[$product_id]) || !empty($has_product_options_links[$product_id]))? true : false;
            $product['product_options'] = empty($product['product_options']) ? array() : $product['product_options'];
        }

        unset($selected_options);

        /**
         * Changes before gathering product discounts
         *
         * @param array $product Product data
         * @param mixed $auth Array of user authentication data
         * @param array $params Parameteres for gathering data
         */
        fn_set_hook('gather_additional_product_data_before_discounts', $product, $auth, $params);

        // Get product discounts
        if ($params['get_discounts'] && !isset($product['exclude_from_calculate'])) {
            fn_promotion_apply('catalog', $product, $auth);
            if (!empty($product['prices']) && is_array($product['prices'])) {
                $product_copy = $product;
                foreach ($product['prices'] as $pr_k => $pr_v) {
                    $product_copy['base_price'] = $product_copy['price'] = $pr_v['price'];
                    fn_promotion_apply('catalog', $product_copy, $auth);
                    $product['prices'][$pr_k]['price'] = $product_copy['price'];
                }
            }

            if (empty($product['discount']) && !empty($product['list_price']) && !empty($product['price']) && floatval($product['price']) && $product['list_price'] > $product['price']) {
                $product['list_discount'] = fn_format_price($product['list_price'] - $product['price']);
                $product['list_discount_prc'] = sprintf('%d', round($product['list_discount'] * 100 / $product['list_price']));
            }
        }

        // FIXME: old product options scheme
        $product['discounts'] = array('A' => 0, 'P' => 0);
        if (!empty($product['promotions'])) {
            foreach ($product['promotions'] as $v) {
                foreach ($v['bonuses'] as $a) {
                    if ($a['discount_bonus'] == 'to_fixed') {
                        $product['discounts']['A'] += $a['discount'];
                    } elseif ($a['discount_bonus'] == 'by_fixed') {
                        $product['discounts']['A'] += $a['discount_value'];
                    } elseif ($a['discount_bonus'] == 'to_percentage') {
                        $product['discounts']['P'] += 100 - $a['discount_value'];
                    } elseif ($a['discount_bonus'] == 'by_percentage') {
                        $product['discounts']['P'] += $a['discount_value'];
                    }
                }
            }
        }

        // Add product prices with taxes and without taxes
        if ($params['get_taxed_prices'] && AREA != 'A' && Registry::get('settings.Appearance.show_prices_taxed_clean') == 'Y' && $auth['tax_exempt'] != 'Y') {
            fn_get_taxed_and_clean_prices($product, $auth);
        }

        if ($params['get_features'] && !isset($product['product_features'])) {
            $product['product_features'] = fn_get_product_features_list($product, $params['features_display_on']);
        }

        if ($params['get_extra'] && !empty($product['is_edp']) && $product['is_edp'] == 'Y') {
            $product['agreement'] = array(fn_get_edp_agreements($product['product_id']));
        }

        $product['qty_content'] = fn_get_product_qty_content($product, $allow_negative_amount, $inventory_tracking);

        if ($params['detailed_params']) {
            $product['detailed_params'] = empty($product['detailed_params']) ? $params : array_merge($product['detailed_params'], $params);
        }

        /**
         * Add additional data to product
         *
         * @param array $product Product data
         * @param mixed $auth Array of user authentication data
         * @param array $params Parameteres for gathering data
         */
        fn_set_hook('gather_additional_product_data_post', $product, $auth, $params);
        $_product = $product;
    }// \foreach $products

    /**
     * Add additional data to products after gathering additional products data
     *
     * @param array $product_ids Array of product identifiers
     * @param array $params      Parameteres for gathering data
     * @param array $products    Array of products
     * @param mixed $auth        Array of user authentication data
     */
    fn_set_hook('gather_additional_products_data_post', $product_ids, $params, $products, $auth);

    if ($params['get_for_one_product'] == true) {
        $products = array_shift($products);
    }
}

/**
 * Forms a drop-down list of possible product quantity values with the given quantity step
 *
 * @param array $product Product data
 * @param char $allow_negative_amount Flag: allow or disallow negative product quantity(Y - allow, N - disallow)
 * @param char $inventory_tracking Flag: track product qiantity or not (Y - track, N - do not track)
 * @return array qty_content List of available quantity values with the given step
 */
function fn_get_product_qty_content($product, $allow_negative_amount, $inventory_tracking)
{
    if (empty($product['qty_step'])) {
        return array();
    }

    $qty_content = array();
    $default_list_qty_count = 100;

    $max_allowed_qty_steps = 50;

    if (empty($product['min_qty'])) {
        $min_qty = $product['qty_step'];
    } else {
        $min_qty = fn_ceil_to_step($product['min_qty'], $product['qty_step']);
    }

    if (!empty($product['list_qty_count'])) {
        $max_list_qty = $product['list_qty_count'] * $product['qty_step'] + $min_qty - $product['qty_step'];
    } else {
        $max_list_qty = $default_list_qty_count * $product['qty_step'] + $min_qty - $product['qty_step'];
    }

    if ($product['tracking'] != ProductTracking::DO_NOT_TRACK
        && $allow_negative_amount != 'Y'
        && $inventory_tracking == 'Y'
) {
        if (isset($product['in_stock'])) {
            $max_qty = fn_floor_to_step($product['in_stock'], $product['qty_step']);

        } elseif (isset($product['inventory_amount'])) {
            $max_qty = fn_floor_to_step($product['inventory_amount'], $product['qty_step']);

        } elseif ($product['amount'] < $product['qty_step']) {
            $max_qty = $product['qty_step'];

        } else {
            $max_qty = fn_floor_to_step($product['amount'], $product['qty_step']);
        }

        if (!empty($product['list_qty_count'])) {
            $max_qty = min($max_qty, $max_list_qty);
        }
    } else {
        $max_qty = $max_list_qty;
    }

    if (!empty($product['max_qty'])) {
        $max_qty = min($max_qty, fn_floor_to_step($product['max_qty'], $product['qty_step']));
    }

    $total_steps_count = 1 + (($max_qty - $min_qty) / $product['qty_step']);

    if ($total_steps_count > $max_allowed_qty_steps) {
        return array();
    }

    for ($qty = $min_qty; $qty <= $max_qty; $qty += $product['qty_step']) {
        $qty_content[] = $qty;
    }

    return $qty_content;
}

/**
 * Gets additional data for a single product
 *
 * @param array $product Product data
 * @param boolean $get_icon Flag that define if product icon should be gathered
 * @param boolean $get_detailed Flag determines if detailed image should be gathered
 * @param boolean $get_options Flag that define if product options should be gathered
 * @param boolean $get_discounts Flag that define if product discounts should be gathered
 * @param boolean $get_features Flag that define if product features should be gathered
 * @return array Product data with the additional information
 */
function fn_gather_additional_product_data(&$product, $get_icon = false, $get_detailed = false, $get_options = true, $get_discounts = true, $get_features = false)
{
    // Get specific settings
    $params = array(
        'get_icon' => $get_icon,
        'get_detailed' => $get_detailed,
        'get_options' => $get_options,
        'get_discounts' => $get_discounts,
        'get_features' => $get_features,
    );

    /**
     * Change parameters for gathering additional data for a product
     *
     * @param array $product Product data
     * @param array $params  parameters for gathering data
     */
    fn_set_hook('gather_additional_product_data_params', $product, $params);

    fn_gather_additional_products_data($product, $params);
}

/**
 * Returns product folders
 *
 * @param array $params
 *        int product_id     - ID of product
 *        string folder_ids  - get folders by ids
 *        string order_by
 * @return array folders, params
 */
function fn_get_product_file_folders($params, $lang_code = DESCR_SL)
{
    $params['product_id'] = !empty($params['product_id'])? $params['product_id'] : 0;
    $fields = array(
        'SUM(?:product_files.file_size) as folder_size',
        '?:product_file_folders.*',
        '?:product_file_folder_descriptions.folder_name'
    );
    $default_params = array(
        'product_id' => 0,
        'folder_ids' => '',
        'order_by' => 'position, folder_name',
    );
    $params = array_merge($default_params, $params);

    $join = db_quote(" LEFT JOIN ?:product_files ON ?:product_file_folders.folder_id = ?:product_files.folder_id LEFT JOIN ?:product_file_folder_descriptions ON ?:product_file_folder_descriptions.folder_id = ?:product_file_folders.folder_id AND ?:product_file_folder_descriptions.lang_code = ?s", $lang_code);
    $order = $params['order_by'];

    if (!empty($params['folder_ids'])) {
        $condition = db_quote("WHERE ?:product_file_folders.folder_id IN (?n)", $params['folder_ids']);
    } else {
        $condition = db_quote("WHERE ?:product_file_folders.product_id = ?i", $params['product_id']);
    }

    if (AREA == 'C') {
        $condition .= " AND ?:product_file_folders.status = 'A'";
    }

    $folders = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:product_file_folders ?p ?p GROUP BY folder_id ORDER BY ?p", $join, $condition, $order);

    return array($folders, $params);
}

/**
 * Returns product files
 * @param array $params
 *        int product_id     - ID of product
 *        bool preview_check - get files only with preview
 *        int order_id       - get order ekeys for the files
 *        string file_ids    - get files by ids
 * @return array files, params
 */
function fn_get_product_files($params, $lang_code = DESCR_SL)
{
    $default_params = array (
        'product_id'    => 0,
        'preview_check' => false,
        'order_id'      => 0,
        'file_ids'      => '',
    );
    $params = array_merge($default_params, $params);

    /**
     * Change parameters for getting product files
     *
     * @param array  $params
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_product_files_pre', $params, $lang_code);
    $fields = array(
        '?:product_files.*',
        '?:product_file_descriptions.file_name',
        '?:product_file_descriptions.license',
        '?:product_file_descriptions.readme'
    );

    $join = db_quote(" LEFT JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id AND ?:product_file_descriptions.lang_code = ?s", $lang_code);

    if (!empty($params['order_id'])) {
        $fields[] = '?:product_file_ekeys.active';
        $fields[] = '?:product_file_ekeys.downloads';
        $fields[] = '?:product_file_ekeys.ekey';

        $join .= db_quote(" LEFT JOIN ?:product_file_ekeys ON ?:product_file_ekeys.file_id = ?:product_files.file_id AND ?:product_file_ekeys.order_id = ?i", $params['order_id']);
        $join .= (AREA == 'C') ? " AND ?:product_file_ekeys.active = 'Y'" : '';
    }

    if (!empty($params['file_ids'])) {
        $condition = db_quote("WHERE ?:product_files.file_id IN (?n)", $params['file_ids']);
    } else {
        $condition = db_quote("WHERE ?:product_files.product_id = ?i", $params['product_id']);
    }

    if ($params['preview_check'] == true) {
        $condition .= " AND preview_path != ''";
    }

    if (AREA == 'C') {
        $condition .= " AND ?:product_files.status = 'A'";
    }

    /**
     * Change SQL parameters for product files selection
     *
     * @param array  $params
     * @param array  $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     */
    fn_set_hook('get_product_files_before_select', $params, $fields, $join, $condition);

    $files = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:product_files ?p ?p ORDER BY position, file_name", $join, $condition);

    if (!empty($files)) {
        foreach ($files as $k => $file) {
            if (!empty($file['license']) && $file['agreement'] == 'Y') {
                $files[$k]['agreements'] = array($file);
            }
            if (!empty($file['product_id']) && !empty($file['ekey'])) {
                $files[$k]['edp_info'] = fn_get_product_edp_info($file['product_id'], $file['ekey']);
            }
        }
    }

    /**
     * Change product files
     *
     * @param array $params
     * @param array $files  Product files
     */
    fn_set_hook('get_product_files_post', $params, $files);

    return array($files, $params);
}

/**
 * Returns product folders and files merged and presented as a tree
 *
 * @param array  $folders Product folders
 * @param array  $files Product files
 * @return array tree
 */
function fn_build_files_tree($folders, $files)
{
    $tree = array();
    $folders = !empty($folders)? $folders : array();
    $files = !empty($files)? $files : array();

    if (is_array($folders) && is_array($files)) {

        foreach ($folders as $v_folder) {
            $subfiles = array();
            foreach ($files as $v_file) {
                if ($v_file['folder_id'] == $v_folder['folder_id']) {
                    $subfiles[] = $v_file;
                }
            }

            $v_folder['files'] = $subfiles;
            $tree['folders'][] = $v_folder;
        }

        foreach ($files as $v_file) {
            if (empty($v_file['folder_id'])) {
                $tree['files'][] = $v_file;
            }
        }

    }

    return $tree;
}

/**
 * Returns EDP ekey info
 *
 * @param int $product_id Product identifier
 * @param string $ekey Download key
 * @return array Download key info
 */
function fn_get_product_edp_info($product_id, $ekey)
{
    /**
     * Prepare params before getting EDP information
     *
     * @param int    $product_id Product identifier
     * @param string $ekey       Download key
     */
    fn_set_hook('get_product_edp_info_pre', $product_id, $ekey);

    $unlimited = db_get_field("SELECT unlimited_download FROM ?:products WHERE product_id = ?i", $product_id);
    $ttl_condition = ($unlimited == 'Y') ? '' :  db_quote(" AND ttl > ?i", TIME);

    $edp_info = db_get_row(
        "SELECT product_id, order_id, file_id "
            . "FROM ?:product_file_ekeys "
        . "WHERE product_id = ?i AND active = 'Y' AND ekey = ?s ?p",
        $product_id, $ekey, $ttl_condition
    );

    /**
     * Change product edp info
     *
     * @param array  $edp_info   EDP information
     * @param int    $product_id Product identifier
     * @param string $ekey       Download key
     */
    fn_set_hook('get_product_edp_info_post', $product_id, $ekey, $edp_info);

    return $edp_info;
}

/**
 * Gets EDP agreemetns
 *
 * @param int $product_id Product identifier
 * @param bool $file_name If true get file name in info, false otherwise
 * @return array EDP agreements data
 */
function fn_get_edp_agreements($product_id, $file_name = false)
{
    /**
     * Actions before getting edp agreements
     *
     * @param int  $product_id Product identifier
     * @param bool $file_name  Get file name
     */
    fn_set_hook('get_edp_agreements_pre', $product_id, $file_name);

    $join = '';
    $fields = array(
        '?:product_files.file_id',
        '?:product_files.agreement',
        '?:product_file_descriptions.license'
    );

    if ($file_name == true) {
        $join .= db_quote(" LEFT JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id AND product_file_descriptions.lang_code = ?s", CART_LANGUAGE);
        $fields[] = '?:product_file_descriptions.file_name';
    }

    /**
     * Prepare params before getting edp agreements
     *
     * @param int    $product_id Product identifier
     * @param string $join       Query join; it is treated as a JOIN clause
     * @param array  $fields     Array of table column names to be returned
     */
    fn_set_hook('get_edp_agreements_before_get_agriments', $product_id, $fields, $join);

    $edp_agreements = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:product_files INNER JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id AND ?:product_file_descriptions.lang_code = ?s WHERE ?:product_files.product_id = ?i AND ?:product_file_descriptions.license != '' AND ?:product_files.agreement = 'Y'", CART_LANGUAGE, $product_id);

    /**
     * Actions after getting edp agreements
     *
     * @param int   $product_id     Product identifier
     * @param bool  $file_name      If true get file name in info, false otherwise
     * @param array $edp_agreements EDP agreements data
     */
    fn_set_hook('get_edp_agreements_post', $product_id, $file_name, $edp_agreements);

    return $edp_agreements;
}

//-------------------------------------- 'Categories' object functions -----------------------------

/**
 * Gets subcategories list for current category (first-level categories only)
 *
 * @param  int    $category_id Category identifier
 * @param  array  $params      Params
 * @param  string $lang_code   2-letters language code
 * @return array
 */
function fn_get_subcategories($category_id = '0', $params = array(), $lang_code = CART_LANGUAGE)
{
    if (is_string($params)) { // Backward compatibility
        $lang_code = $params;
        $params = array();
    }

    $params = array_merge(array(
        'category_id' => $category_id,
        'visible' => true,
        'get_images' => true,
    ), $params);

    /**
     * Change params before subcategories select
     *
     * @param int    $category_id Category identifier
     * @param int    $params      Params of subcategories search
     * @param string $lang_code   2-letters language code
     */
    fn_set_hook('get_subcategories_params', $category_id, $lang_code, $params);

    list($categories) = fn_get_categories($params, $lang_code);

    /**
     * Change subcategories
     *
     * @param int    $params     Params of subcategories search
     * @param string $lang_code  2-letters language code
     * @param array  $categories Subcategories
     */
    fn_set_hook('get_subcategories_post', $params, $lang_code, $categories);

    return $categories;
}

/**
 * Gets categories tree (multidimensional) from the current category
 *
 * @param int $category_id Category identifier
 * @param boolean $simple Flag that defines if category names path and product count should not be gathered
 * @param string $lang_code 2-letters language code
 * @return array Array of subcategories as a hierarchical tree
 */
function fn_get_categories_tree($category_id = '0', $simple = true, $lang_code = CART_LANGUAGE)
{
    $params = array (
        'category_id' => $category_id,
        'simple' => $simple
    );

    /**
     * Change params before categories tree select
     *
     * @param int     $category_id Category identifier
     * @param boolean $simple      Flag that defines if category names path and product count should not be gathered
     * @param string  $lang_code   2-letters language code
     * @param int     $params      Params of subcategories search
     */
    fn_set_hook('get_categories_tree_params', $category_id, $simple, $lang_code, $params);

    list($categories, ) = fn_get_categories($params, $lang_code);

    /**
     * Change categories tree
     *
     * @param int    $params     Params of subcategories search
     * @param string $lang_code  2-letters language code
     * @param array  $categories Categories tree
     */
    fn_set_hook('get_categories_tree_post', $params, $lang_code, $categories);

    return $categories;
}

/**
 * Gets categories tree (plain) from the current category
 *
 * @param int $category_id Category identifier
 * @param boolean $simple Flag that defines if category names path and product count should not be gathered
 * @param string $lang_code 2-letters language code
 * @param array $company_ids Identifiers of companies for that categories should be gathered
 * @return array Array of subategories as a simple list
 */
function fn_get_plain_categories_tree($category_id = '0', $simple = true, $lang_code = CART_LANGUAGE, $company_ids = '')
{
    $params = array (
        'category_id' => $category_id,
        'simple' => $simple,
        'visible' => false,
        'plain' => true,
        'company_ids' => $company_ids,
    );

    /**
     * Change params before plain categories tree select
     *
     * @param int     $category_id Category identifier
     * @param boolean $simple      Flag that defines if category names path and product count should not be gathered
     * @param string  $lang_code   2-letters language code
     * @param array   $company_ids Identifiers of companies for that categories should be gathered
     * @param int     $params      Params of subcategories search
     */
    fn_set_hook('get_plain_categories_tree_params', $category_id, $simple, $lang_code, $company_ids, $params);

    list($categories, ) = fn_get_categories($params, $lang_code);

    /**
     * Change categories tree
     *
     * @param int    $params     Params of subcategories search
     * @param string $lang_code  2-letters language code
     * @param array  $categories Categories tree
     */
    fn_set_hook('get_plain_categories_tree_post', $params, $lang_code, $categories);

    return $categories;
}

/**
 * Categories sorting function, compares two categories
 *
 * @param array $a First category data
 * @param array $b Second category data
 * @return int Result of comparison categories positions or categories names( if both categories positions are empty)
 */
function fn_cat_sort($a, $b)
{
    /**
     * Changes categories data before the comparison
     *
     * @param array $a First category data
     * @param array $b Second category data
     */
    fn_set_hook('cat_sort_pre', $a, $b);

    $result = 0;

    if (empty($a["position"]) && empty($b['position'])) {
        $result = strnatcmp($a["category"], $b["category"]);
    } else {
        $result = strnatcmp($a["position"], $b["position"]);
    }

    /**
     * Changes the result of categories comparison
     *
     * @param array $a      First category data
     * @param array $b      Second category data
     * @param int   $result Result of comparison categories positions or categories names( if both categories positions are empty)
     */
    fn_set_hook('cat_sort_post', $a, $b, $result);

    return $result;
}

/**
 * Checks if objects should be displayed in a picker
 *
 * @param string $table Name of SQL table with objects
 * @param int $threshold Value of the threshold after which the picker should be displayed
 * @return boolean Flag that defines if picker should be displayed
 */
function fn_show_picker($table, $threshold)
{
    /**
     * Changes params for the 'fn_show_picker' function
     *
     * @param string $table     Table name
     * @param string $threshold Value of the threshold after which the picker should be displayed
     */
    fn_set_hook('show_picker_pre', $table, $threshold);

    $picker = db_has_table($table) && db_get_field("SELECT COUNT(*) FROM ?:$table") > $threshold ? true : false;

    /**
     * Changes result of the 'fn_show_picker' function
     *
     * @param string  $table     Table name
     * @param string  $threshold Value of the threshold after which the picker should be displayed
     * @param boolean $picker    Flag that defines if data should be displayed in picker
     */
    fn_set_hook('show_picker_post', $table, $threshold, $picker);

    return $picker;
}

/**
 * Gets categories tree beginning from category identifier defined in params or root category
 * @param array $params Categories search params
 *      category_id - Root category identifier
 *      visible - Flag that defines if only visible categories should be included
 *      current_category_id - Identifier of current node for visible categories
 *      simple - Flag that defines if category path should be getted as set of category IDs
 *      plain - Flag that defines if continues list of categories should be returned
 *      --------------------------------------
 *      Examples:
 *      Gets whole categories tree:
 *      fn_get_categories()
 *      --------------------------------------
 *      Gets subcategories tree of the category:
 *      fn_get_categories(array(
 *          'category_id' => 123
 *      ))
 *      --------------------------------------
 *      Gets all first-level nodes of the category
 *      fn_get_categories(array(
 *          'category_id' => 123,
 *          'visible' => true
 *      ))
 *      --------------------------------------
 *      Gets all visible nodes of the category, start from the root
 *      fn_get_categories(array(
 *          'category_id' => 0,
 *          'current_category_id' => 234,
 *          'visible' => true
 *      ))
 * @param string $lang_code 2-letters language code
 * @return array Categories tree
 */
function fn_get_categories($params = array(), $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params for the categories search
     *
     * @param array  $params    Categories search params
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_categories_pre', $params, $lang_code);

    $default_params = array(
        'category_id' => 0,
        'visible' => false,
        'current_category_id' => 0,
        'simple' => true,
        'plain' => false,
        'limit' => 0,
        'item_ids' => '',
        'group_by_level' => true,
        'get_images' => false,
        'category_delimiter' => '/',
        'get_frontend_urls' => false,
        'max_nesting_level' => null,    // null means no limitation
    );

    $params = array_merge($default_params, $params);

    $sortings = array(
        'timestamp' => '?:categories.timestamp',
        'name' => '?:category_descriptions.category',
        'position' => array(
            '?:categories.is_trash',
            '?:categories.position',
            '?:category_descriptions.category'
        )
    );

    $auth = & Tygh::$app['session']['auth'];

    $fields = array(
        '?:categories.category_id',
        '?:categories.parent_id',
        '?:categories.id_path',
        '?:category_descriptions.category',
        '?:categories.position',
        '?:categories.status'
    );

    if (!$params['simple']) {
        $fields[] = '?:categories.product_count';
    }

    if (empty($params['current_category_id']) && !empty($params['product_category_id'])) {
        $params['current_category_id'] = $params['product_category_id'];
    }

    $condition = '';

    if (AREA == 'C') {
        $_statuses = array('A'); // Show enabled products/categories
        $condition .= fn_get_localizations_condition('?:categories.localization', true);
        $condition .= " AND (" . fn_find_array_in_set($auth['usergroup_ids'], '?:categories.usergroup_ids', true) . ")";
        $condition .= db_quote(" AND ?:categories.status IN (?a)", $_statuses);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:categories.status IN (?a)", $params['status']);
    }

    if (isset($params['parent_category_id'])) {
        // set parent id, that was set in block properties
        $params['category_id'] = $params['parent_category_id'];
    }

    if ($params['visible'] == true && empty($params['b_id'])) {
        if (!empty($params['current_category_id'])) {
            $cur_id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $params['current_category_id']);
            if (!empty($cur_id_path)) {
                $parent_categories_ids = explode('/', $cur_id_path);
            }
        }
        if (!empty($params['category_id']) || empty($parent_categories_ids)) {
            $parent_categories_ids[] = $params['category_id'];
        }
        $condition .= db_quote(" AND ?:categories.parent_id IN (?n)", $parent_categories_ids);
    }

    if (!empty($params['category_id'])) {
        $from_id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $params['category_id']);
        $condition .= db_quote(" AND ?:categories.id_path LIKE ?l", "$from_id_path/%");
    }

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(' AND ?:categories.category_id IN (?n)', explode(',', $params['item_ids']));
    }

    if (!empty($params['except_id']) && (empty($params['item_ids']) || !empty($params['item_ids']) && !in_array($params['except_id'], explode(',', $params['item_ids'])))) {
        $condition .= db_quote(' AND ?:categories.category_id != ?i AND ?:categories.parent_id != ?i', $params['except_id'], $params['except_id']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (?:categories.timestamp >= ?i AND ?:categories.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    if (!empty($params['max_nesting_level'])) {
        if (!empty($params['parent_category_id'])) {
            $parent_nesting_level = (int) db_get_field("SELECT level FROM ?:categories WHERE category_id = ?i", $params['parent_category_id']);
        } else {
            $parent_nesting_level = 0;
        }
        $condition .= db_quote(" AND ?:categories.level <= ?i", $params['max_nesting_level'] + $parent_nesting_level);
    }

    $limit = $join = $group_by = '';

    /**
     * Changes SQL params for the categories search
     *
     * @param array  $params    Categories search params
     * @param string $join      Join parametrs
     * @param string $condition Request condition
     * @param array  $fields    Selectable fields
     * @param string $group_by  Group by parameters
     * @param array  $sortings  Sorting fields
     * @param string $lang_code Language code
     */
    fn_set_hook('get_categories', $params, $join, $condition, $fields, $group_by, $sortings, $lang_code);

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'position', 'asc');

    if (!empty($params['get_conditions'])) {
        return array($fields, $join, $condition, $group_by, $sorting, $limit);
    }

    $categories = db_get_hash_array('SELECT ' . implode(',', $fields) . " FROM ?:categories LEFT JOIN ?:category_descriptions ON ?:categories.category_id = ?:category_descriptions.category_id AND ?:category_descriptions.lang_code = ?s $join WHERE 1 ?p $group_by $sorting ?p", 'category_id', $lang_code, $condition, $limit);

    /**
     * Process categories list after getting it
     * @param array  $categories Categories list
     * @param array  $params     Categories search params
     * @param string $join       Join parametrs
     * @param string $condition  Request condition
     * @param array  $fields     Selectable fields
     * @param string $group_by   Group by parameters
     * @param array  $sortings   Sorting fields
     * @param string $sorting    Sorting parameters
     * @param string $limit      Limit parameter
     * @param string $lang_code  Language code
     */
    fn_set_hook('get_categories_after_sql', $categories, $params, $join, $condition, $fields, $group_by, $sortings, $sorting, $limit, $lang_code);

    if (empty($categories)) {
        return array(array());
    }

    // @TODO remove from here, because active category may not exist in the resulting set. This is the job for controller.
    if (!empty($params['active_category_id']) && !empty($categories[$params['active_category_id']])) {
        $categories[$params['active_category_id']]['active'] = true;
        Registry::set('runtime.active_category_ids', explode('/', $categories[$params['active_category_id']]['id_path']));
    }

    $categories_list = array();
    if ($params['simple'] == true || $params['group_by_level'] == true) {
        $child_for = array_keys($categories);
        $where_condition = !empty($params['except_id']) ? db_quote(' AND category_id != ?i', $params['except_id']) : '';
        $has_children = db_get_hash_array("SELECT category_id, parent_id FROM ?:categories WHERE parent_id IN(?n) ?p", 'parent_id', $child_for, $where_condition);
    }

    $category_ids = array();
    // Group categories by the level (simple)
    if ($params['simple'] == true) {
        foreach ($categories as $k => $v) {
            $v['level'] = substr_count($v['id_path'], '/');
            if ((!empty($params['current_category_id']) || $v['level'] == 0) && isset($has_children[$k])) {
                $v['has_children'] = $has_children[$k]['category_id'];
            }
            $categories_list[$v['level']][$v['category_id']] = $v;
            $category_ids[] = $v['category_id'];
        }
    } elseif ($params['group_by_level'] == true) {
        // Group categories by the level (simple) and literalize path
        foreach ($categories as $k => $v) {
            $path = explode('/', $v['id_path']);
            $category_path = array();
            foreach ($path as $__k => $__v) {
                $category_path[$__v] = @$categories[$__v]['category'];
            }
            $v['category_path'] = implode($params['category_delimiter'], $category_path);
            $v['level'] = substr_count($v['id_path'], "/");
            if ((!empty($params['current_category_id']) || $v['level'] == 0) && isset($has_children[$k])) {
                $v['has_children'] = $has_children[$k]['category_id'];
            }
            $categories_list[$v['level']][$v['category_id']] = $v;
            $category_ids[] = $v['category_id'];
        }
    } else {
        // @FIXME: Seems that this code isn't being executed anywhere
        $categories_list = $categories;
        $category_ids = fn_fields_from_multi_level($categories_list, 'category_id', 'category_id');
    }

    ksort($categories_list, SORT_NUMERIC);
    $categories_list = array_reverse($categories_list, !$params['simple'] && !$params['group_by_level']);

    // Lazy-load category image pairs
    if ($params['get_images']) {
        $image_pairs_for_categories = fn_get_image_pairs($category_ids, 'category', 'M', true, true, $lang_code);
    }

    // Rearrangement of subcategories and filling with images
    foreach ($categories_list as $level => $categories_of_level) {
        // Fill categories' image pairs for plain structure of array
        if ($params['get_images']
            && !$params['simple']
            && !$params['group_by_level']
            && !empty($image_pairs_for_categories[$level])
        ) {
            $categories_list[$level]['main_pair'] = reset($image_pairs_for_categories[$level]);
        }
        foreach ($categories_of_level as $category_id => $category_data) {
            // Fill categories' image pairs for multi-level structure of array
            if ($params['get_images']
                && !empty($image_pairs_for_categories[$category_id])
                && ($params['simple'] || $params['group_by_level'])
            ) {
                $categories_list[$level][$category_id]['main_pair'] = reset($image_pairs_for_categories[$category_id]);
            }

            // Move subcategories to their parents' elements
            if (
                isset($category_data['parent_id'])
                &&
                isset($categories_list[$level + 1][$category_data['parent_id']])
            ) {
                $categories_list[$level + 1][$category_data['parent_id']]['subcategories'][] = $categories_list[$level][$category_id];
                unset($categories_list[$level][$category_id]);
            }
        }
    }

    if (!empty($params['get_frontend_urls'])) {
        foreach ($categories_list as &$category) {
            $category['url'] = fn_url('categories.view?category_id=' . $category['category_id'], 'C');
        }
    }

    if ($params['group_by_level'] == true) {
        $categories_list = array_pop($categories_list);
    }

    if ($params['plain'] == true) {
        $categories_list = fn_multi_level_to_plain($categories_list, 'subcategories');
    }

    if (!empty($params['item_ids'])) {
        $categories_list = fn_sort_by_ids($categories_list, explode(',', $params['item_ids']), 'category_id');
    }

    if (!empty($params['add_root'])) {
        array_unshift($categories_list, array('category_id' => 0, 'category' => $params['add_root']));
    }

    /**
     * Process categories list before cutting second and fird levels
     *
     * @param array $categories_list Categories list
     * @param array $params          Categories search params
     */
    fn_set_hook('get_categories_before_cut_levels', $categories_list, $params);

    fn_dropdown_appearance_cut_second_third_levels($categories_list, 'subcategories', $params);

    /**
     * Process final category list
     *
     * @param array  $categories_list Categories list
     * @param array  $params          Categories search params
     * @param string $lang_code       Language code
     */
    fn_set_hook('get_categories_post', $categories_list, $params, $lang_code);

    // process search results
    if (!empty($params['save_view_results'])) {
        $request = $params;
        $request['page'] = 1;
        $categories_res = ($params['plain'] == true)
            ?  $categories_list
            : fn_multi_level_to_plain($categories_list, 'subcategories');
        foreach ($categories_res as $key => $item) {
            if (empty($item['category_id'])) {
                unset($categories_res[$key]);
            }
        }
        $request['total_items'] = $request['items_per_page'] = count($categories_res);
        LastView::instance()->processResults('categories', $categories_res, $request);
    }

    return array($categories_list, $params);
}

/**
 * Recursively sorts an array using a user-supplied comparison function
 *
 * @param array $array Array for sorting
 * @param string $key Key of subarray for sorting
 * @param callback $function Comparison function
 */
function fn_sort(&$array, $key, $function)
{
    usort($array, $function);
    foreach ($array as $k => $v) {
        if (!empty($v[$key])) {
            fn_sort($array[$k][$key], $key, $function);
        }
    }
}

/**
 * Gets full category data by its id
 *
 * @param int $category_id ID of category
 * @param string $lang_code 2-letters language code
 * @param string $field_list List of categories table' fields. If empty, data from all fields will be returned.
 * @param boolean $get_main_pair Get or not category image
 * @param boolean $skip_company_condition Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
 * @param boolean $preview Category is requested in a preview mode
 * @param boolean $get_full_path Get full category path with all ancestors
 * @return mixed Array with category data.
 */
function fn_get_category_data($category_id = 0, $lang_code = CART_LANGUAGE, $field_list = '', $get_main_pair = true, $skip_company_condition = false, $preview = false, $get_full_path = false)
{
    // @TODO: remove in 4.3.2, this line is needed for backward compatibility since 4.3.1
    $field_list = str_replace(
        array('selected_layouts', 'default_layout', 'product_details_layout'),
        array('selected_views', 'default_view', 'product_details_view'),
        $field_list
    );

    /**
     * Changes select category data conditions
     *
     * @param int     $category_id            Category ID
     * @param array   $field_list             List of fields for retrieving
     * @param boolean $get_main_pair          Get or not category image
     * @param boolean $skip_company_condition Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
     * @param string  $lang_code              2-letters language code
     */
    fn_set_hook('get_category_data_pre', $category_id, $field_list, $get_main_pair, $skip_company_condition, $lang_code);

    $auth = & Tygh::$app['session']['auth'];

    $conditions = '';
    if (AREA == 'C' && !$preview) {
        $conditions = "AND (" . fn_find_array_in_set($auth['usergroup_ids'], '?:categories.usergroup_ids', true) . ")";
    }

    if (empty($field_list)) {
        $descriptions_list = "?:category_descriptions.*";
        $field_list = "?:categories.*, $descriptions_list";
    }

    if (fn_allowed_for('ULTIMATE') && !$skip_company_condition) {
        $conditions .= fn_get_company_condition('?:categories.company_id');
    }

    $join = '';

    /**
     * Changes SQL parameters before select category data
     *
     * @param int    $category_id Category ID
     * @param array  $field_list  SQL fields to be selected in an SQL-query
     * @param string $join        String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $lang_code   2-letters language code
     * @param string $conditions  Condition params
     */
    fn_set_hook('get_category_data', $category_id, $field_list, $join, $lang_code, $conditions);

    $category_data = db_get_row(
        "SELECT $field_list FROM ?:categories"
        . " LEFT JOIN ?:category_descriptions"
            . " ON ?:category_descriptions.category_id = ?:categories.category_id"
            . " AND ?:category_descriptions.lang_code = ?s ?p"
        . " WHERE ?:categories.category_id = ?i ?p",
        $lang_code, $join, $category_id, $conditions
    );

    if (!empty($category_data)) {
        $category_data['category_id'] = $category_id;

        // Generate meta description automatically
        if (empty($category_data['meta_description']) && defined('AUTO_META_DESCRIPTION') && AREA != 'A') {
            $category_data['meta_description'] = !empty($category_data['description']) ? fn_generate_meta_description($category_data['description']) : '';
        }

        if ($get_main_pair == true) {
            $category_data['main_pair'] = fn_get_image_pairs($category_id, 'category', 'M', true, true, $lang_code);
        }

        if (!empty($category_data['selected_views'])) {
            $category_data['selected_views'] = unserialize($category_data['selected_views']);
        } else {
            $category_data['selected_views'] = array();
        }

        // @TODO: remove in 4.3.2 - these three (3) conditions are needed for backward compatibility since 4.3.1
        if (isset($category_data['selected_views'])) {
            $category_data['selected_layouts'] = $category_data['selected_views'];
        }
        if (isset($category_data['default_view'])) {
            $category_data['default_layout'] = $category_data['default_view'];
        }
        if (isset($category_data['product_details_view'])) {
            $category_data['product_details_layout'] = $category_data['product_details_view'];
        }

        if ($get_full_path) {
            $path = explode('/', $category_data['id_path']);
            if ($path) {
                $ancestors = db_get_array(
                    "SELECT ?:categories.category_id, ?:category_descriptions.category"
                    . " FROM ?:categories"
                    . " LEFT JOIN ?:category_descriptions"
                    . " ON ?:category_descriptions.category_id = ?:categories.category_id"
                    . " AND ?:category_descriptions.lang_code = ?s"
                    . " WHERE ?:categories.category_id IN (?n)",
                    $lang_code,
                    $path
                );
                $ancestors = fn_array_column(fn_sort_by_ids($ancestors, $path, 'category_id'), 'category', 'category_id');
                $category_data['path_names'] = $ancestors;
            }
        }
    }

    /**
     * Changes category data
     *
     * @param int     $category_id            Category ID
     * @param array   $field_list             List of fields for retrieving
     * @param boolean $get_main_pair          Get or not category image
     * @param boolean $skip_company_condition Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
     * @param string  $lang_code              2-letters language code
     * @param array   $category_data          Array with category fields
     */
    fn_set_hook('get_category_data_post', $category_id, $field_list, $get_main_pair, $skip_company_condition, $lang_code, $category_data);

    return (!empty($category_data) ? $category_data : false);
}

/**
 * Gets category name by category identifier
 *
 * @param int/array $category_id Category identifier or array of category identifiers
 * @param string $lang_code 2-letters language code
 * @param boolean $as_array Flag if false one category name is returned as simple string, if true category names are always returned as array
 * @return string/array Category name or array with category names
 */
function fn_get_category_name($category_id = 0, $lang_code = CART_LANGUAGE, $as_array = false)
{
    /**
     * Changes parameters for getting category name
     *
     * @param int/array $category_id Category identifier or array of category identifiers
     * @param string    $lang_code   2-letters language code
     * @param boolean   $as_array    Flag if false one category name is returned as simple string, if true category names are always returned as array
     */
    fn_set_hook('get_category_name_pre', $category_id, $lang_code, $as_array);

    $name = array();

    if (!empty($category_id)) {
        if (!is_array($category_id) && strpos($category_id, ',') !== false) {
            $category_id = explode(',', $category_id);
        }
        if (is_array($category_id) || $as_array == true) {
            $name = db_get_hash_single_array("SELECT category_id, category FROM ?:category_descriptions WHERE category_id IN (?n) AND lang_code = ?s", array('category_id', 'category'), $category_id, $lang_code);
        } else {
            $name = db_get_field("SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = ?s", $category_id, $lang_code);
        }
    }

    /**
     * Changes category names
     *
     * @param int|array    $category_id Category identifier or array of category identifiers
     * @param string       $lang_code   2-letters language code
     * @param boolean      $as_array    Flag if false one category name is returned as simple string, if true category names are always returned as array
     * @param string|array $name        Category name or array with category names
     */
    fn_set_hook('get_category_name_post', $category_id, $lang_code, $as_array, $name);

    return $name;
}

/**
 * Gets category path by category identifier
 *
 * @param int $category_id Category identifier
 * @param string $lang_code 2-letters language code
 * @param string $path_separator String character(s) separating the catergories
 * @return string Category path
 */
function fn_get_category_path($category_id = 0, $lang_code = CART_LANGUAGE, $path_separator = '/')
{
    /**
     * Change parameters for getting category path
     *
     * @param int    $category_id    Category identifier
     * @param string $lang_code      2-letters language code
     * @param string $path_separator String character(s) separating the catergories
     */
    fn_set_hook('fn_get_category_path_pre', $category_id, $lang_code, $path_separator);

    $category_path = false;

    if (!empty($category_id)) {

        $id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);

        $category_names = db_get_hash_single_array(
            "SELECT category_id, category FROM ?:category_descriptions WHERE category_id IN (?n) AND lang_code = ?s",
            array('category_id', 'category'), explode('/', $id_path), $lang_code
        );

        $path = explode('/', $id_path);
        $_category_path = '';
        foreach ($path as $v) {
            $_category_path .= $category_names[$v] . $path_separator;
        }
        $_category_path = rtrim($_category_path, $path_separator);

        $category_path = (!empty($_category_path) ? $_category_path : false);
    }

    /**
     * Change category path
     *
     * @param int    $category_id    Category identifier
     * @param string $lang_code      2-letters language code
     * @param string $path_separator String character(s) separating the catergories
     * @param string $category_path  Category path
     */
    fn_set_hook('fn_get_category_path_post', $category_id, $lang_code, $path_separator, $category_path);

    return $category_path;
}

/**
 * Reduces given list of category IDs, removing IDs of categories which will be removed anyway within
 * the recursive deletion of their parent categories.
 * For example, if input categories are:
 * - Electronics
 * -- Desktops
 * -- Laptops
 * - Road Bikes
 * Ouput categories will be:
 * - Electronics
 * - Road Bikes
 *
 * @param array $category_ids Category IDs to be deleted
 *
 * @return array Reduced list of category IDs
 */
function fn_filter_redundant_deleting_category_ids(array $category_ids)
{
    $result = array();

    $category_ids_from_db = db_get_hash_single_array(
        "SELECT category_id, parent_id FROM ?:categories WHERE category_id IN(?n)",
        array('category_id', 'parent_id'),
        $category_ids
    );

    // We select only the least nested categories, because deletion is recursive
    foreach ($category_ids_from_db as $category_id => $parent_id) {
        $category_id = (int) $category_id;
        $parent_id = (int) $parent_id;

        if (!isset($category_ids_from_db[$parent_id]) && !in_array($category_id, $result)) {
            $result[] = $category_id;
        }
    }

    return $result;
}

/**
 * Removes category by identifier
 *
 * @param int $category_id Category identifier
 * @param boolean $recurse Flag that defines if category should be deleted recursively
 * @return array/boolean Identifiers of deleted categories or false if categories were not found
 */
function fn_delete_category($category_id, $recurse = true)
{
    /**
     * Actions before category and its related data removal
     *
     * @param  int         $category_id Category identifier to delete
     * @param  boolean     $recurse     Flag that defines if category should be deleted recursively
     * @return int|boolean Identifiers of deleted categories or false if categories were not found
     */
    fn_set_hook('delete_category_pre', $category_id, $recurse);

    if (empty($category_id)) {
        return false;
    }

    // Log category deletion
    fn_log_event('categories', 'delete', array(
        'category_id' => $category_id,
    ));

    // Delete all subcategories
    if ($recurse == true) {
        $id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);
        // Order is important
        $category_ids = db_get_fields(
            "SELECT category_id FROM ?:categories WHERE id_path LIKE ?l ORDER BY id_path ASC",
            "$id_path/%"
        );
        // The very first item is category that is being deleted
        array_unshift($category_ids, $category_id);
    } else {
        $category_ids[] = $category_id;
    }

    foreach ($category_ids as $k => $category_id) {
        // When deleting trash category, remove products from it
        if (fn_is_trash_category($category_id)) {
            fn_empty_trash($category_id);
        }

        /**
         * Process category delete (run before category is deleted)
         *
         * @param int $category_id Category identifier
         */
        fn_set_hook('delete_category_before', $category_id);

        Block::instance()->removeDynamicObjectdata('categories', $category_id);

        // Deleting category
        db_query("DELETE FROM ?:categories WHERE category_id = ?i", $category_id);
        db_query("DELETE FROM ?:category_descriptions WHERE category_id = ?i", $category_id);

        // Remove this category from features assignments
        db_query("UPDATE ?:product_features SET categories_path = ?p", fn_remove_from_set('categories_path', $category_id));

        if (fn_allowed_for('MULTIVENDOR')) {
            // Deleting main products association with deleting products
            $products_to_delete = db_get_fields(
                "SELECT product_id FROM ?:products_categories WHERE category_id = ?i AND link_type = 'M'",
                $category_id
            );

            if (!empty($products_to_delete)) {
                foreach ($products_to_delete as $key => $value) {
                    fn_delete_product($value);
                }
            }

            db_query("DELETE FROM ?:products_categories WHERE category_id = ?i", $category_id);
        }

        // Deleting category images
        fn_delete_image_pairs($category_id, 'category');

        /**
         * Process category delete (run after category is deleted)
         *
         * @param int $category_id Category identifier
         */
        fn_set_hook('delete_category_after', $category_id);
    }

    /**
     * Actions after category and its related data removal
     *
     * @param int     $category_id  Category identifier to delete
     * @param boolean $recurse      Flag that defines if category should be deleted recursively
     * @param array   $category_ids Category identifiers that were removed
     */
    fn_set_hook('delete_category_post', $category_id, $recurse, $category_ids);

    return $category_ids; // Returns ids of deleted categories
}

/**
 * Removes product by identifier
 *
 * @param int $product_id Product identifier
 * @return boolean Flag that defines if product was deleted
 */
function fn_delete_product($product_id)
{
    $status = true;
    /**
     * Check product delete (run before product is deleted)
     *
     * @param int     $product_id Product identifier
     * @param boolean $status     Flag determines if product can be deleted, if false product is not deleted
     */
    fn_set_hook('delete_product_pre', $product_id, $status);

    $product_deleted = false;

    if (!empty($product_id)) {

        if (!fn_check_company_id('products', 'product_id', $product_id)) {
            fn_set_notification('W', __('warning'), __('access_denied'));

            return false;
        }

        if ($status == false) {
            return false;
        }

        Block::instance()->removeDynamicObjectData('products', $product_id);

        // Log product deletion
        fn_log_event('products', 'delete', array(
            'product_id' => $product_id,
        ));

        // Delete product files
        fn_delete_product_files(0, $product_id);

        // Delete product folders
        fn_delete_product_file_folders(0, $product_id);

        $category_ids = db_get_fields("SELECT category_id FROM ?:products_categories WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:products_categories WHERE product_id = ?i", $product_id);
        fn_update_product_count($category_ids);

        $res = db_query("DELETE FROM ?:products WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:product_descriptions WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:product_prices WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:product_features_values WHERE product_id = ?i", $product_id);

        if (!fn_allowed_for('ULTIMATE:FREE')) {
            db_query("DELETE FROM ?:product_options_exceptions WHERE product_id = ?i", $product_id);
        }
        db_query("DELETE FROM ?:product_popularity WHERE product_id = ?i", $product_id);

        fn_delete_image_pairs($product_id, 'product');

        // Delete product options and inventory records for this product
        fn_poptions_delete_product($product_id);

        // Executing delete_product functions from active addons

        $product_deleted = $res;
    }

    /**
     * Process product delete (run after product is deleted)
     *
     * @param int  $product_id      Product identifier
     * @param bool $product_deleted True if product was deleted successfully, false otherwise
     */
    fn_set_hook('delete_product_post', $product_id, $product_deleted);

    return $product_deleted;
}

/**
 * Check if product exists in database.
 *
 * @param int $product_id
 * @return bool
 */
function fn_product_exists($product_id)
{
    $result = true;
    fn_set_hook('product_exists', $product_id, $result);

    $res = db_get_field('SELECT COUNT(*) FROM ?:products WHERE product_id = ?i', $product_id);

    return $result && $res;
}

/**
 * Checks whether category with given ID exists at database.
 *
 * @param int         $category_id          Category ID
 * @param string|null $additional_condition Optional checking condition
 *
 * @return bool
 */
function fn_category_exists($category_id, $additional_condition = null)
{
    return (bool) db_get_field(
        'SELECT COUNT(*) FROM ?:categories WHERE category_id = ?i ' . $additional_condition,
        $category_id
    );
}

/**
 * Global products update
 *
 * @param array $update_data List of updated fields and product_ids
 * @return boolean Always true
 */
function fn_global_update_products($update_data)
{
    $table = $field = $value = $type = array();
    $msg = '';

    /**
     * Global update products data (running before fn_global_update_products() function)
     *
     * @param array  $update_data List of updated fields and product_ids
     * @param array  $table       List of table names to be updated
     * @param array  $field       List of SQL field names to be updated
     * @param array  $value       List of new fields values
     * @param array  $type        List of field types absolute or persentage
     * @param string $msg         Message containing the information about the changes made
     */
    fn_set_hook('global_update_products_pre', $update_data, $table, $field, $value, $type, $msg);

    $all_product_notify = false;
    $currencies = Registry::get('currencies');

    if (!empty($update_data['product_ids'])) {
        $update_data['product_ids'] = explode(',', $update_data['product_ids']);
        if (fn_allowed_for('MULTIVENDOR') && !fn_company_products_check($update_data['product_ids'], true)) {
            return false;
        }
    } elseif (fn_allowed_for('MULTIVENDOR')) {
        $all_product_notify = true;
        $update_data['product_ids'] = db_get_fields("SELECT product_id FROM ?:products WHERE 1 ?p", fn_get_company_condition('?:products.company_id'));
    }

    // Update prices
    if (!empty($update_data['price'])) {
        $table[] = '?:product_prices';
        $field[] = 'price';
        $value[] = $update_data['price'];
        $type[] = $update_data['price_type'];

        $msg .= ($update_data['price'] > 0 ? __('price_increased') : __('price_decreased')) . ' ' . abs($update_data['price']) . ($update_data['price_type'] == 'A' ? $currencies[CART_PRIMARY_CURRENCY]['symbol'] : '%') . '.<br />';
    }

    // Update list prices
    if (!empty($update_data['list_price'])) {
        $table[] = '?:products';
        $field[] = 'list_price';
        $value[] = $update_data['list_price'];
        $type[] = $update_data['list_price_type'];

        $msg .= ($update_data['list_price'] > 0 ? __('list_price_increased') : __('list_price_decreased')) . ' ' . abs($update_data['list_price']) . ($update_data['list_price_type'] == 'A' ? $currencies[CART_PRIMARY_CURRENCY]['symbol'] : '%') . '.<br />';
    }

    // Update amount
    if (!empty($update_data['amount'])) {
        $table[] = '?:products';
        $field[] = 'amount';
        $value[] = $update_data['amount'];
        $type[] = 'A';

        $table[] = '?:product_options_inventory';
        $field[] = 'amount';
        $value[] = $update_data['amount'];
        $type[] = 'A';

        $msg .= ($update_data['amount'] > 0 ? __('amount_increased') : __('amount_decreased')) .' ' . abs($update_data['amount']) . '.<br />';
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
    fn_set_hook('global_update_products', $table, $field, $value, $type, $msg, $update_data);

    $where = !empty($update_data['product_ids']) ? db_quote(" AND product_id IN (?n)", $update_data['product_ids']) : '';

    foreach ($table as $k => $v) {
        $_value = db_quote("?d", $value[$k]);
        $sql_expression = $type[$k] == 'A' ? ($field[$k] . ' + ' . $_value) : ($field[$k] . ' * (1 + ' . $_value . '/ 100)');

        if (($type[$k] == 'A') && !empty($update_data['product_ids']) && ($_value > 0)) {
            foreach ($update_data['product_ids'] as $product_id) {
                $send_notification = false;
                $product = fn_get_product_data($product_id, $auth, DESCR_SL, '', true, true, true, true);

                if (($product['tracking'] == ProductTracking::TRACK_WITHOUT_OPTIONS) && ($product['amount'] <= 0)) {
                    $send_notification = true;
                } elseif ($product['tracking'] == ProductTracking::TRACK_WITH_OPTIONS) {
                    $inventory = db_get_array("SELECT * FROM ?:product_options_inventory WHERE product_id = ?i", $product_id);
                    foreach ($inventory as $inventory_item) {
                        if ($inventory_item['amount'] <= 0) {
                            $send_notification = true;
                        }
                    }
                }

                if ($send_notification) {
                    fn_send_product_notifications($product_id);
                }
            }
        }

        if (fn_allowed_for('ULTIMATE') && $field[$k] == 'price') {
            $company_condition = "";
            if (Registry::get('runtime.company_id')) {
                $company_condition .= db_quote(" AND company_id = ?i", Registry::get('runtime.company_id'));
            }

            db_query("UPDATE ?p SET ?p = IF(?p < 0, 0, ?p) WHERE product_id IN (SELECT product_id FROM ?:products WHERE 1 ?p ?p)", $v, $field[$k], $sql_expression, $sql_expression, $where, $company_condition);

            $sql_expression = $type[$k] == 'A' ? '`price` + ?d' : '`price` * (1 + ?d / 100)';
            $sql_expression = db_quote($sql_expression, $update_data['price']);

            db_query("UPDATE ?:ult_product_prices SET `price` = IF(?p < 0, 0, ?p) WHERE 1 ?p ?p", $sql_expression, $sql_expression, $where, $company_condition);
        } else {

            db_query("UPDATE ?p SET ?p = IF(?p < 0, 0, ?p) WHERE 1 ?p", $v, $field[$k], $sql_expression, $sql_expression, $where);

        }
    }

    /**
     * Global update products data (running after fn_global_update_products() function)
     *
     * @param string $msg         Message containing the information about the changes made
     * @param array  $update_data List of updated fields and product_ids
     */
    fn_set_hook('global_update_products_post', $msg, $update_data);

    if (empty($update_data['product_ids']) || $all_product_notify) {
        fn_set_notification('N', __('notice'), __('all_products_have_been_updated') . '<br />' . $msg);
    } else {
        fn_set_notification('N', __('notice'), __('text_products_updated'));
    }

    return true;
}

/**
 * Adds or updates product
 *
 * @param array $product_data Product data
 * @param int $product_id Product identifier
 * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return mixed ID of created/updated product or false in case of error
 */
function fn_update_product($product_data, $product_id = 0, $lang_code = CART_LANGUAGE)
{
    $can_update = true;

    /**
     * Update product data (running before fn_update_product() function)
     *
     * @param array   $product_data Product data
     * @param int     $product_id   Product identifier
     * @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean $can_update   Flag, allows addon to forbid to create/update product
     */
    fn_set_hook('update_product_pre', $product_data, $product_id, $lang_code, $can_update);

    if ($can_update == false) {
        return false;
    }

    SecurityHelper::sanitizeObjectData('product', $product_data);

    if (fn_allowed_for('ULTIMATE')) {
        // check that product owner was not changed by store administrator
        if (Registry::get('runtime.company_id') || empty($product_data['company_id'])) {
            $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
            if (!empty($product_company_id)) {
                $product_data['company_id'] = $product_company_id;
            } else {
                if (Registry::get('runtime.company_id')) {
                    $product_company_id = $product_data['company_id'] = Registry::get('runtime.company_id');
                } else {
                    $product_company_id = $product_data['company_id'] = fn_get_default_company_id();
                }
            }
        } else {
            $product_company_id = $product_data['company_id'];
        }

        if (!empty($product_data['category_ids']) && !fn_check_owner_categories($product_company_id, $product_data['category_ids'])) {
            fn_set_notification('E', __('error'), __('product_must_have_owner_category'));

            return false;
        }

        if (fn_ult_is_shared_product($product_id) == 'Y') {
            $_product_id = fn_ult_update_shared_product($product_data, $product_id, Registry::ifGet('runtime.company_id', $product_company_id), $lang_code);
        }
    }

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id') && !empty($product_company_id) && Registry::get('runtime.company_id') != $product_company_id && !empty($_product_id)) {
        $product_id = $_product_id;
        $create = false;
    } else {
        $product_data['updated_timestamp'] = time();

        $_data = $product_data;

        $_product_time = (isset($product_data['timestamp'])) ? fn_parse_date($product_data['timestamp']) : 0;
        if (empty($product_id) &&
            (empty($_product_time) || $_product_time == mktime(0, 0, 0, date("m"), date("d"), date("Y")))) { //For new products without timestamp or today date we use time()
                $_data['timestamp'] = time();
        } elseif (!empty($_product_time) && $_product_time != fn_get_product_timestamp($product_id, true)) { //If we change date of existing product than update it
             $_data['timestamp'] = $_product_time;
        } else {
            unset($_data['timestamp']);
        }

        if (empty($product_id) && Registry::get('runtime.company_id')) {
            $_data['company_id'] = Registry::get('runtime.company_id');
        }

        if (!empty($product_data['avail_since'])) {
            $_data['avail_since'] = fn_parse_date($product_data['avail_since']);
        }

        if (isset($product_data['tax_ids'])) {
            $_data['tax_ids'] = empty($product_data['tax_ids']) ? '' : fn_create_set($product_data['tax_ids']);
        }

        if (isset($product_data['localization'])) {
            $_data['localization'] = empty($product_data['localization']) ? '' : fn_implode_localizations($_data['localization']);
        }

        if (isset($product_data['usergroup_ids'])) {
            $_data['usergroup_ids'] = empty($product_data['usergroup_ids']) ? '0' : implode(',', $_data['usergroup_ids']);
        }

        if (!empty($product_data['list_qty_count']) && $product_data['list_qty_count'] < 0) {
            $_data['list_qty_count'] = 0;
        }

        if (!empty($product_data['qty_step']) && $product_data['qty_step'] < 0) {
            $_data['qty_step'] = 0;
        }

        if (!empty($product_data['min_qty'])) {
            $_data['min_qty'] = fn_ceil_to_step(abs($product_data['min_qty']), $_data['qty_step']);
        }

        if (!empty($product_data['max_qty'])) {
            $_data['max_qty'] = fn_ceil_to_step(abs($product_data['max_qty']), $_data['qty_step']);
        }

        if (Registry::get('settings.General.inventory_tracking') == "N" && isset($_data['tracking'])) {
            unset($_data['tracking']);
        }

        if (Registry::get('settings.General.allow_negative_amount') == 'N' && isset($_data['amount'])) {
            $_data['amount'] = abs($_data['amount']);
        }

        $shipping_params = array();
        if (!empty($product_id)) {
            $shipping_params = db_get_field('SELECT shipping_params FROM ?:products WHERE product_id = ?i', $product_id);
            if (!empty($shipping_params)) {
                $shipping_params = unserialize($shipping_params);
            }
        }

        // Save the product shipping params
        $_shipping_params = array(
            'min_items_in_box' => isset($_data['min_items_in_box']) ? intval($_data['min_items_in_box']) : (!empty($shipping_params['min_items_in_box']) ? $shipping_params['min_items_in_box'] : 0),
            'max_items_in_box' => isset($_data['max_items_in_box']) ? intval($_data['max_items_in_box']) : (!empty($shipping_params['max_items_in_box']) ? $shipping_params['max_items_in_box'] : 0),
            'box_length' => isset($_data['box_length']) ? intval($_data['box_length']) : (!empty($shipping_params['box_length']) ? $shipping_params['box_length'] : 0),
            'box_width' => isset($_data['box_width']) ? intval($_data['box_width']) : (!empty($shipping_params['box_width']) ? $shipping_params['box_width'] : 0),
            'box_height' => isset($_data['box_height']) ? intval($_data['box_height']) : (!empty($shipping_params['box_height']) ? $shipping_params['box_height'] : 0),
        );

        $_data['shipping_params'] = serialize($_shipping_params);
        unset($_shipping_params);

        // add new product
        if (empty($product_id)) {
            $create = true;
            $product_data['create'] = true;
            // product title can't be empty and not set product_id
            if (empty($product_data['product']) || !empty($product_data['product_id'])) {
                fn_set_notification('E', __('error'), __('need_product_name'));

                return false;
            }

            $product_id = db_query("INSERT INTO ?:products ?e", $_data);

            if (empty($product_id)) {
                $product_id = false;
            }

            //
            // Adding same product descriptions for all cart languages
            //
            $_data = $product_data;
            $_data['product_id'] =  $product_id;
            $_data['product'] = trim($_data['product'], " -");

            foreach (fn_get_translation_languages() as $_data['lang_code'] => $_v) {
                db_query("INSERT INTO ?:product_descriptions ?e", $_data);
            }

        // update product
        } else {
            $create = false;
            if (isset($product_data['product']) && empty($product_data['product'])) {
                unset($product_data['product']);
            }

            if (!empty($_data['amount'])) {
                $old_amount = fn_get_product_amount($product_id);
                if ($old_amount <= 0) {
                    fn_send_product_notifications($product_id);
                }
            }

            $arow = db_query("UPDATE ?:products SET ?u WHERE product_id = ?i", $_data, $product_id);

            $_data = $product_data;
            if (!empty($_data['product'])) {
                $_data['product'] = trim($_data['product'], " -");
            }
            db_query("UPDATE ?:product_descriptions SET ?u WHERE product_id = ?i AND lang_code = ?s", $_data, $product_id, $lang_code);

            if ($arow === false) {
                fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('product'))),'','404');
                $product_id = false;
            }
        }

        if ($product_id) {
            // Log product add/update
            fn_log_event('products', !empty($create) ? 'create' : 'update', array(
                'product_id' => $product_id,
            ));

            $product_data['product_features'] = !empty($product_data['product_features']) ? $product_data['product_features'] : array();
            $product_data['add_new_variant'] = !empty($product_data['add_new_variant']) ? $product_data['add_new_variant'] : array();

            fn_update_product_categories($product_id, $product_data);

            // Update product features value
            fn_update_product_features_value($product_id, $product_data['product_features'], $product_data['add_new_variant'], $lang_code);

            // Update product prices
            $product_data = fn_update_product_prices($product_id, $product_data);

            if (isset($product_data['popularity'])) {
                $_data = array (
                    'product_id' => $product_id,
                    'total' => intval($product_data['popularity'])
                );

                db_query("INSERT INTO ?:product_popularity ?e ON DUPLICATE KEY UPDATE total = ?i", $_data, $product_data['popularity']);
            }

            // Update main images pair
            fn_attach_image_pairs('product_main', 'product', $product_id, $lang_code);

            // Update additional images
            fn_attach_image_pairs('product_additional', 'product', $product_id, $lang_code);

            // Adding new additional images
            fn_attach_image_pairs('product_add_additional', 'product', $product_id, $lang_code);

            if (fn_allowed_for('ULTIMATE')) {
                fn_check_and_update_product_sharing($product_id);
            }
        }
    }

    /**
     * Update product data (running after fn_update_product() function)
     *
     * @param array   $product_data Product data
     * @param int     $product_id   Product integer identifier
     * @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean $create       Flag determines if product was created (true) or just updated (false).
     */
    fn_set_hook('update_product_post', $product_data, $product_id, $lang_code, $create);

    return (int) $product_id;
}

function fn_update_product_features_value($product_id, $product_features, $add_new_variant, $lang_code, $params = array())
{
    if (empty($product_features)) {
        return false;
    }

    $i_data = array(
        'product_id' => $product_id,
        'lang_code' => $lang_code
    );

    $id_paths = db_get_fields(
        "SELECT ?:categories.id_path FROM ?:products_categories "
        . "LEFT JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id "
        . "WHERE product_id = ?i",
        $product_id
    );

    foreach ($product_features as $feature_id => $value) {
        // Check if feature is applicable for this product
        $_params = array(
            'category_ids' => array_unique(explode('/', implode('/', $id_paths))),
            'feature_id' => $feature_id
        );
        list($_feature) = fn_get_product_features($_params);

        if (empty($_feature)) {
            $_feature = db_get_field("SELECT description FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s", $feature_id, $lang_code);
            $_product = db_get_field("SELECT product FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $product_id, $lang_code);
            fn_set_notification('E', __('error'), __('product_feature_cannot_assigned', array(
                '[feature_name]' => $_feature,
                '[product_name]' => $_product
            )));

            continue;
        }

        $i_data['feature_id'] = $feature_id;
        unset($i_data['value']);
        unset($i_data['variant_id']);
        unset($i_data['value_int']);
        $feature_type = db_get_field("SELECT feature_type FROM ?:product_features WHERE feature_id = ?i", $feature_id);

        // Delete variants in current language
        if ($feature_type == ProductFeatures::TEXT_FIELD) {
            db_query("DELETE FROM ?:product_features_values WHERE feature_id = ?i AND product_id = ?i AND lang_code = ?s", $feature_id, $product_id, $lang_code);
        } else {
            db_query("DELETE FROM ?:product_features_values WHERE feature_id = ?i AND product_id = ?i", $feature_id, $product_id);
        }

        if ($feature_type == ProductFeatures::DATE) {
            if (empty($value)) {
                continue;
            } else {
                $i_data['value_int'] = fn_parse_date($value);
            }
        } elseif ($feature_type == ProductFeatures::MULTIPLE_CHECKBOX) {
            if (!empty($add_new_variant[$feature_id]['variant'])
                || (
                    isset($add_new_variant[$feature_id]['variant'])
                    && $add_new_variant[$feature_id]['variant'] === '0'
                )
            ) {
                $value = empty($value) ? array() : $value;
                $value[] = fn_update_product_feature_variant($feature_id, $feature_type, $add_new_variant[$feature_id], $lang_code);
            }
            if (!empty($value)) {
                foreach ($value as $variant_id) {
                    foreach (fn_get_translation_languages() as $i_data['lang_code'] => $_d) { // insert for all languages
                        $i_data['variant_id'] = $variant_id;
                        db_query("REPLACE INTO ?:product_features_values ?e", $i_data);
                    }
                }
            }

            continue;
        } elseif (in_array($feature_type, array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::EXTENDED))) {
            if (!empty($add_new_variant[$feature_id]['variant']) || (isset($add_new_variant[$feature_id]['variant']) && $add_new_variant[$feature_id]['variant'] === '0')) {
                $i_data['variant_id'] = fn_update_product_feature_variant($feature_id, $feature_type, $add_new_variant[$feature_id], $lang_code);
                $i_data['value_int'] = $add_new_variant[$feature_id]['variant'];
            } elseif (!empty($value) && $value != 'disable_select') {
                if ($feature_type == ProductFeatures::NUMBER_SELECTBOX) {
                    $i_data['value_int'] = db_get_field("SELECT variant FROM ?:product_feature_variant_descriptions WHERE variant_id = ?i AND lang_code = ?s", $value, $lang_code);
                }
                $i_data['variant_id'] = $value;
            } else {
                continue;
            }
        } else {
            if ($value == '') {
                continue;
            }
            if ($feature_type == ProductFeatures::NUMBER_FIELD) {
                $i_data['value_int'] = $value;
            } else {
                $i_data['value'] = $value;
            }
        }

        if ($feature_type != ProductFeatures::TEXT_FIELD) { // feature values are common for all languages, except text (T)
            foreach (fn_get_translation_languages() as $i_data['lang_code'] => $_d) {
                db_query("REPLACE INTO ?:product_features_values ?e", $i_data);
            }
        } else { // for text feature, update current language only
            $i_data['lang_code'] = $lang_code;
            db_query("INSERT INTO ?:product_features_values ?e", $i_data);
        }
    }

    return true;
}

/**
 * Recalculates and updates products quantity in categories
 *
 * @param array $category_ids List of categories identifiers for update. When empty list given,
 *                            all categories will be updated.
 *
 * @return true
 */
function fn_update_product_count($category_ids = array())
{

    $category_ids = array_unique((array) $category_ids);

    /**
     * Update product count (running before update)
     *
     * @param array $category_ids List of category ids for update
     */
    fn_set_hook('update_product_count_pre', $category_ids);

    $condition = empty($category_ids) ? '' : db_quote(' WHERE ?:categories.category_id IN (?n)', $category_ids);

    db_query(
        'UPDATE ?:categories SET ?:categories.product_count = ('
        . ' SELECT COUNT(*) FROM ?:products_categories WHERE ?:products_categories.category_id = ?:categories.category_id)'
        . $condition
    );

    /**
     * Update product count (running after update)
     *
     * @param array $category_ids List of category ids for update
     */
    fn_set_hook('update_product_count_post', $category_ids);

    return true;
}


/**
 * Adds or updates category
 *
 * @param array $category_data Category data
 * @param int $category_id Category identifier
 * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return int New or updated category identifier
 */
function fn_update_category($category_data, $category_id = 0, $lang_code = CART_LANGUAGE)
{
    // @TODO: remove in 4.3.2 - these three (3) conditions are needed for backward compatibility since 4.3.1
    if (isset($category_data['selected_layouts'])) {
        $category_data['selected_views'] = $category_data['selected_layouts'];
        unset($category_data['selected_layouts']);
    }
    if (isset($category_data['default_layout'])) {
        $category_data['default_view'] = $category_data['default_layout'];
        unset($category_data['default_layout']);
    }
    if (isset($category_data['product_details_layout'])) {
        $category_data['product_details_view'] = $category_data['product_details_layout'];
        unset($category_data['product_details_layout']);
    }
    /**
     * Update category data (running before fn_update_category() function)
     *
     * @param array  $category_data Category data
     * @param int    $category_id   Category identifier
     * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_category_pre', $category_data, $category_id, $lang_code);

    SecurityHelper::sanitizeObjectData('category', $category_data);

    // category title required
    if (empty($category_data['category'])) {
        //return false; // FIXME: management page doesn't have category name
    }

    if (isset($category_data['localization'])) {
        $category_data['localization'] = empty($category_data['localization']) ? '' : fn_implode_localizations($category_data['localization']);
    }
    if (isset($category_data['usergroup_ids'])) {
        $category_data['usergroup_ids'] = empty($category_data['usergroup_ids']) ? '0' : implode(',', $category_data['usergroup_ids']);
    }
    if (fn_allowed_for('ULTIMATE')) {
        fn_set_company_id($category_data);
    }

    $_data = $category_data;
    unset($_data['parent_id']);

    if (isset($category_data['timestamp'])) {
        $_data['timestamp'] = fn_parse_date($category_data['timestamp']);
    }

    if (isset($_data['position']) && empty($_data['position']) && $_data['position'] != '0' && isset($category_data['parent_id'])) {
        $_data['position'] = db_get_field("SELECT max(position) FROM ?:categories WHERE parent_id = ?i", $category_data['parent_id']);
        $_data['position'] = $_data['position'] + 10;
    }

    if (isset($_data['selected_views'])) {
        $_data['selected_views'] = serialize($_data['selected_views']);
    }

    if (isset($_data['use_custom_templates']) && $_data['use_custom_templates'] == 'N') {
        // Clear the layout settings if the category custom templates were disabled
        $_data['product_columns'] = $_data['selected_views'] = $_data['default_view'] = '';
    }

    // create new category
    if (empty($category_id)) {

        if (fn_allowed_for('ULTIMATE') && empty($_data['company_id'])) {
            fn_set_notification('E', __('error'), __('need_company_id'));

            return false;
        }

        $create = true;

        $category_id = db_query("INSERT INTO ?:categories ?e", $_data);
        $_data['category_id'] = $category_id;

        foreach (fn_get_translation_languages() as $_data['lang_code'] => $v) {
            db_query("INSERT INTO ?:category_descriptions ?e", $_data);
        }

        $category_data['parent_id'] = !empty($category_data['parent_id']) ? $category_data['parent_id'] : 0;

    // update existing category
    } else {

        $category_data['old_company_id'] = db_get_field("SELECT company_id FROM ?:categories WHERE category_id = ?i", $category_id);

        $arow = db_query("UPDATE ?:categories SET ?u WHERE category_id = ?i", $_data, $category_id);
        $arow_description = db_query("UPDATE ?:category_descriptions SET ?u WHERE category_id = ?i AND lang_code = ?s", $_data, $category_id, $lang_code);

        if ($arow === false && $arow_description === false) {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('category'))),'','404');
            $category_id = false;
        }
    }

    if ($category_id) {

        // regenerate id_path for all child categories of the updated category
        if (isset($category_data['parent_id'])) {
            fn_change_category_parent($category_id, intval($category_data['parent_id']));
        }

        // Log category add/update
        fn_log_event('categories', !empty($create) ? 'create' : 'update', array(
            'category_id' => $category_id,
        ));

        // Assign usergroup to all subcategories
        if (!empty($_data['usergroup_to_subcats']) && $_data['usergroup_to_subcats'] == 'Y') {
            $id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);
            db_query("UPDATE ?:categories SET usergroup_ids = ?s WHERE id_path LIKE ?l", $_data['usergroup_ids'], "$id_path/%");
        }
    }

    /**
     * Update category data (running after fn_update_category() function)
     *
     * @param array  $category_data Category data
     * @param int    $category_id   Category identifier
     * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_category_post', $category_data, $category_id, $lang_code);

    return $category_id;

}

/**
 * Changes category's parent to another category. Modifies "id_path and "level" attributes of category and its children.
 *
 * @param int $category_id Category identifier
 * @param int $new_parent_id Identifier of new category parent
 * @return bool True on success, false otherwise
 */
function fn_change_category_parent($category_id, $new_parent_id)
{
    if (empty($category_id) || $category_id == $new_parent_id) { return false; }
    /**
     * Adds additional actions before category parent updating
     *
     * @param int $category_id   Category identifier
     * @param int $new_parent_id Identifier of new category parent
     */
    fn_set_hook('update_category_parent_pre', $category_id, $new_parent_id);

    $categories = db_get_hash_array(
        "SELECT `category_id`, `parent_id`, `id_path`, `level` FROM ?:categories WHERE `category_id` IN (?n)",
        'category_id',
        array($new_parent_id, $category_id)
    );
    if (empty($categories[$category_id])
        || (!empty($new_parent_id) && empty($categories[$new_parent_id]))
    ) {
        return false;
    }

    $category_modified = $categories[$category_id];
    if (!empty($new_parent_id) && !empty($categories[$new_parent_id])) {
        $category_modified['parent_id'] = $new_parent_id;
        $category_modified['level'] = ($categories[$new_parent_id]['level'] + 1);
        $category_modified['id_path'] = $categories[$new_parent_id]['id_path'] . '/' . $category_id;
    } else {
        $category_modified['parent_id'] = 0;
        $category_modified['level'] = 1;
        $category_modified['id_path'] = $category_id;
    }

    // Update category's tree position
    db_query(
        "UPDATE ?:categories SET `parent_id` = ?i, `id_path` = ?s, `level` = ?i WHERE `category_id` = ?i",
        $category_modified['parent_id'],
        $category_modified['id_path'],
        $category_modified['level'],
        $category_id
    );

    // Update existing category's children tree position
    if (isset($categories[$category_id]['parent_id']) && $categories[$category_id]['parent_id'] != $new_parent_id) {
        db_query(
            "UPDATE ?:categories
            SET
              `id_path` = CONCAT(?s, SUBSTRING(`id_path`, ?i)),
              `level` = `level` + ?i
            WHERE `id_path` LIKE ?l",
            $category_modified['id_path'] . "/",
            strlen($categories[$category_id]['id_path'] . '/') + 1,
            ((int) $category_modified['level'] - (int) $categories[$category_id]['level']),
            $categories[$category_id]['id_path'] . '/%'
        );

        /**
         * Adds additional actions after category parent updating
         *
         * @param int $category_id   Category identifier
         * @param int $new_parent_id Identifier of new category parent
         */
        fn_set_hook('update_category_parent_post', $category_id, $new_parent_id);
    }

    return true;
}

/**
 * Delete product option combination
 *
 * @param string $combination_hash Numeric Hash of options combination. (E.g. '3364473348')
 * @return bool Always true
 */
function fn_delete_product_combination($combination_hash)
{
    fn_delete_image_pairs($combination_hash, 'product_option');

    db_query("DELETE FROM ?:product_options_inventory WHERE combination_hash = ?i", $combination_hash);

    return true;
}

/**
 * Removes options and their variants by option identifier
 *
 * @param int $option_id Option identifier
 * @param int $pid Identifier of the product from which the option should be removed (for global options)
 * @return bool True on success, false otherwise
 */
function fn_delete_product_option($option_id, $pid = 0)
{
    /**
     * Adds additional actions before product option deleting
     *
     * @param int $option_id Option identifier
     * @param int $pid       Product identifier
     */
    fn_set_hook('delete_product_option_pre', $option_id, $pid);

    $option_deleted = false;

    if (!empty($option_id)) {
        $condition = fn_get_company_condition('?:product_options.company_id');
        $_otps = db_get_row("SELECT product_id, inventory FROM ?:product_options WHERE option_id = ?i $condition", $option_id);
        if (empty($_otps)) {
            return false;
        }

        $product_id = $_otps['product_id'];
        $option_inventory = $_otps['inventory'];
        $product_link = db_get_fields("SELECT product_id FROM ?:product_global_option_links WHERE option_id = ?i AND product_id = ?i", $option_id, $pid);
        if (empty($product_id) && !empty($product_link)) {
            // Linked option
            $option_description =  db_get_field("SELECT option_name FROM ?:product_options_descriptions WHERE option_id = ?i AND lang_code = ?s", $option_id, CART_LANGUAGE);
            db_query("DELETE FROM ?:product_global_option_links WHERE product_id = ?i AND option_id = ?i", $pid, $option_id);
            fn_set_notification('W', __('warning'), __('option_unlinked', array(
                '[option_name]' => $option_description
            )));
        } else {
            // Product option
            db_query("DELETE FROM ?:product_options_descriptions WHERE option_id = ?i", $option_id);
            db_query("DELETE FROM ?:product_options WHERE option_id = ?i", $option_id);
            fn_delete_product_option_variants($option_id);
        }

        if ($option_inventory == "Y" && !empty($product_id)) {
            fn_delete_product_option_combinations($product_id);
        }

        $option_deleted = true;
    }

    /**
     * Adds additional actions after product option deleting
     *
     * @param int  $option_id      Option identifier
     * @param int  $pid            Product identifier
     * @param bool $option_deleted True if option was successfully deleted, false otherwise
     */
    fn_set_hook('delete_product_option_post', $option_id, $pid, $option_deleted);

    return $option_deleted;
}

/**
 * Deletes product option combinations and its data (images). Used when deleting or changing product option.
 *
 * @param int $product_id Product Id.
 */
function fn_delete_product_option_combinations($product_id)
{
    /**
     * Adds additional actions before product option combinations deleting
     *
     * @param int $product_id Product Id.
     */
    fn_set_hook('delete_product_option_combinations', $product_id);

    if (!empty($product_id)) {
        $c_ids = db_get_fields("SELECT combination_hash FROM ?:product_options_inventory WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:product_options_inventory WHERE product_id = ?i", $product_id);
        foreach ($c_ids as $c_id) {
            fn_delete_image_pairs($c_id, 'product_option', '');
        }
    }
}

/**
 * Removes option variants
 *
 * @param int $option_id Option identifier: if given, all the option variants are deleted
 * @param int $variant_ids Variants identifiers: used if option_id is empty
 * @return bool Always true
 */
function fn_delete_product_option_variants($option_id = 0, $variant_ids = array())
{
    /**
     * Adds additional actions before product option variants deleting
     *
     * @param int $option_id   Option identifier: if given, all the option variants are deleted
     * @param int $variant_ids Variants identifiers: used if option_id is empty
     */
    fn_set_hook('delete_product_option_variants_pre', $option_id, $variant_ids);

    if (!empty($option_id)) {
        $_vars = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i", $option_id);
    } elseif (!empty($variant_ids)) {
        $_vars = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE variant_id IN (?n)", $variant_ids);
    }

    if (!empty($_vars)) {
        foreach ($_vars as $v_id) {
            db_query("DELETE FROM ?:product_option_variants_descriptions WHERE variant_id = ?i", $v_id);
            fn_delete_image_pairs($v_id, 'variant_image');
        }

        db_query("DELETE FROM ?:product_option_variants WHERE variant_id IN (?n)", $_vars);
    }

    /**
     * Adds additional actions after product option variants deleting
     *
     * @param int $option_id   Option identifier: if given, all the option variants are deleted
     * @param int $variant_ids Variants identifiers: used if option_id is empty
     */
    fn_set_hook('delete_product_option_variants_post', $option_id, $variant_ids);

    return true;
}

/**
 * Gets product options
 *
 * @param array $product_ids Product identifiers
 * @param string $lang_code 2-letters language code
 * @param bool $only_selectable Flag that forces to retreive the options with certain types (default: select, radio or checkbox)
 * @param bool $inventory Get only options with the inventory tracking
 * @param bool $only_avail Get only available options
 * @param bool $skip_global Get only general options, not global options, applied as link
 * @return array List of product options data
 */
function fn_get_product_options($product_ids, $lang_code = CART_LANGUAGE, $only_selectable = false, $inventory = false, $only_avail = false, $skip_global = false)
{
    $condition = $_status = $join = '';
    $extra_variant_fields = '';
    $option_ids = $variants_ids = $options = array();
    $selectable_option_types = array('S', 'R', 'C');

    /**
     * Get product options ( at the beggining of fn_get_product_options() )
     *
     * @param array  $product_ids             Product identifiers
     * @param string $lang_code               2-letters language code
     * @param bool   $only_selectable         This flag forces to retreive the options with the certain types (default: select, radio or checkbox)
     * @param bool   $inventory               Get only options with the inventory tracking
     * @param bool   $only_avail              Get only available options
     * @param array  $selectable_option_types Selectable option types
     * @param bool   $skip_global             Get only general options, not global options, applied as link
     */
    fn_set_hook('get_product_options_pre', $product_ids, $lang_code, $only_selectable, $inventory, $only_avail, $selectable_option_types, $skip_global);

    if (AREA == 'C' || $only_avail == true) {
        $_status .= " AND status = 'A'";
    }
    if ($only_selectable == true) {
        $condition .= db_quote(" AND a.option_type IN(?a)", $selectable_option_types);
    }
    if ($inventory == true) {
        $condition .= " AND a.inventory = 'Y'";
    }

    $join = db_quote(" LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s ", $lang_code);
    $fields = "a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment";

    /**
     * Changes request params before product options selecting
     *
     * @param string $fields               Fields to be selected
     * @param string $condition            String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join                 String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $extra_variant_fields Additional variant fields to be selected
     * @param array  $product_ids          Product identifiers
     * @param string $lang_code            2-letters language code
     */
    fn_set_hook('get_product_options', $fields, $condition, $join, $extra_variant_fields, $product_ids, $lang_code);
    if (!empty($product_ids)) {
        $_options = db_get_hash_multi_array(
            "SELECT " . $fields
            . " FROM ?:product_options as a "
            . $join
            . " WHERE a.product_id IN (?n)" . $condition . $_status
            . " ORDER BY a.position",
            array('product_id', 'option_id'), $product_ids
        );
        if (!$skip_global) {
            $global_options = db_get_hash_multi_array(
                "SELECT c.product_id AS cur_product_id, a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment"
                . " FROM ?:product_options as a"
                . " LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s"
                . " LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id"
                . " WHERE c.product_id IN (?n) AND a.product_id = 0" . $condition . $_status
                . " ORDER BY a.position",
                array('cur_product_id', 'option_id'), $lang_code, $product_ids
            );
        }
        foreach ((array) $product_ids as $product_id) {
            $_opts = (empty($_options[$product_id]) ? array() : $_options[$product_id]) + (empty($global_options[$product_id]) ? array() : $global_options[$product_id]);
            $options[$product_id] = fn_sort_array_by_key($_opts, 'position');
        }
    } else {
        //we need a separate query for global options
        $options = db_get_hash_multi_array(
            "SELECT a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment"
            . " FROM ?:product_options as a"
            . $join
            . " WHERE a.product_id = 0" . $condition . $_status
            . " ORDER BY a.position",
            array('product_id', 'option_id')
        );
    }

    foreach ($options as $product_id => $_options) {
        $option_ids = array_merge($option_ids, array_keys($_options));
    }

    if (empty($option_ids)) {
        if (is_array($product_ids)) {
            return $options;
        } else {
            return !empty($options[$product_ids]) ? $options[$product_ids] : array();
        }
    }

    $_status = (AREA == 'A')? '' : " AND a.status='A'";

    $v_fields = "a.variant_id, a.option_id, a.position, a.modifier, a.modifier_type, a.weight_modifier, a.weight_modifier_type, $extra_variant_fields b.variant_name";
    $v_join = db_quote("LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s", $lang_code);
    $v_condition = db_quote("a.option_id IN (?n) $_status", array_unique($option_ids));
    $v_sorting = "a.position, a.variant_id";
    /**
     * Changes request params before product option variants selecting
     *
     * @param string $v_fields    Fields to be selected
     * @param string $v_condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $v_join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $v_sorting   String with the information for the "order by" statement
     * @param array  $option_ids  Options identifiers
     * @param string $lang_code   2-letters language code
     */
    fn_set_hook('get_product_options_get_variants', $v_fields, $v_condition, $v_join, $v_sorting, $option_ids, $lang_code);

    $variants = db_get_hash_multi_array("SELECT $v_fields FROM ?:product_option_variants as a $v_join WHERE $v_condition ORDER BY $v_sorting", array('option_id', 'variant_id'));

    foreach ($variants as $option_id => $_variants) {
        $variants_ids = array_merge($variants_ids, array_keys($_variants));
    }

    if (empty($variants_ids)) {
        return is_array($product_ids)? $options: $options[$product_ids];
    }

    $image_pairs = fn_get_image_pairs(array_unique($variants_ids), 'variant_image', 'V', true, true, $lang_code);

    foreach ($variants as $option_id => &$_variants) {
        foreach ($_variants as $variant_id => &$_variant) {
            $_variant['image_pair'] = !empty($image_pairs[$variant_id])? reset($image_pairs[$variant_id]) : array();
        }
    }

    foreach ($options as $product_id => &$_options) {
        foreach ($_options as $option_id => &$_option) {
            // Add variant names manually, if this option is "checkbox"
            if ($_option['option_type'] == 'C' && !empty($variants[$option_id])) {
                foreach ($variants[$option_id] as $variant_id => $variant) {
                    $variants[$option_id][$variant_id]['variant_name'] = $variant['position'] == 0 ? __('no') : __('yes');
                }
            }

            $_option['variants'] = !empty($variants[$option_id])? $variants[$option_id] : array();
        }
    }

    /**
     * Get product options ( at the end of fn_get_product_options() )
     *
     * @param array  $product_ids     Product ids
     * @param string $lang_code       Language code
     * @param bool   $only_selectable This flag forces to retreive the options with the certain types (default: select, radio or checkbox)
     * @param bool   $inventory       Get only options with the inventory tracking
     * @param bool   $only_avail      Get only available options
     * @param array  $options         The resulting array of the retrieved options
     */
    fn_set_hook('get_product_options_post', $product_ids, $lang_code, $only_selectable, $inventory, $only_avail, $options);

    return is_array($product_ids)? $options: $options[$product_ids];
}

/**
 * Returns a array of product options using some params
 *
 * @param array $params - array of params
 * @param int $items_per_page - items per page
 * @param $lang_code - language code
 * @return array ($product_options, $params, $product_options_count)
 */
function fn_get_product_global_options($params = array(), $items_per_page = 0, $lang_code = CART_LANGUAGE)
{

    /**
     * Changes params for getting product global options
     *
     * @param array  $params         Array of search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_global_options_pre', $params, $items_per_page, $lang_code);

    $params = LastView::instance()->update('product_global_options', $params);

    $default_params = array(
        'product_id' => 0,
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $fields = array (
        '?:product_options.*',
        '?:product_options_descriptions.*',
    );

    $condition = $join = '';

    $join .= db_quote("LEFT JOIN ?:product_options_descriptions ON ?:product_options_descriptions.option_id = ?:product_options.option_id AND ?:product_options_descriptions.lang_code = ?s ", $lang_code);

    $order = 'ORDER BY position';

    $params['product_id'] = !empty($params['product_id']) ? $params['product_id'] : 0;
    $condition .= db_quote(" AND ?:product_options.product_id = ?i", $params['product_id']);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_options $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    /**
     * Changes SQL params before select product global options
     *
     * @param array  $params    Array of search params
     * @param array  $fields    Fields to be selected
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     */
    fn_set_hook('get_product_global_options_before_select', $params, $fields, $condition, $join);

    $data = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:product_options $join WHERE 1 $condition $order $limit ");

    /**
     * Changes product global options
     *
     * @param array $data   Product global options
     * @param array $params Array of search params
     */
    fn_set_hook('get_product_global_options_post', $data, $params);

    LastView::instance()->processResults('product_global_options', $data, $params);

    return array($data, $params);
}

/**
 * Returns an array of product options with values by combination
 *
 * @param string $combination Options combination code
 * @return array Options decoded from combination
 */

function fn_get_product_options_by_combination($combination)
{
    $options = array();

    /**
     * Changes product options (running before fn_get_product_options_by_combination function)
     *
     * @param string $combination Options combination code
     * @param array  $options     Array for options decoded from combination
     */
    fn_set_hook('get_product_options_by_combination_pre', $combination, $options);

    $_comb = explode('_', $combination);
    if (!empty($_comb) && is_array($_comb)) {
        $iterations = count($_comb);
        for ($i = 0; $i < $iterations; $i += 2) {
            $options[$_comb[$i]] = isset($_comb[$i + 1]) ? $_comb[$i + 1] : '';
        }
    }

    /**
     * Changes product options (running after fn_get_product_options_by_combination function)
     *
     * @param string $combination Options combination code
     * @param array  $options     options decoded from combination
     */
    fn_set_hook('get_product_options_by_combination_post', $combination, $options);

    return $options;
}

/**
 * Removes all product options from the product
 * @param int $product_id Product identifier
 */
function fn_poptions_delete_product($product_id)
{
    /**
     * Adds additional actions before delete all product option
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('poptions_delete_product_pre', $product_id);

    $option_ids = db_get_fields('SELECT option_id FROM ?:product_options WHERE product_id = ?i', $product_id);
    if (!empty($option_ids)) {
        foreach ($option_ids as $option_id) {
            fn_delete_product_option($option_id, $product_id);
        }
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        db_query("DELETE FROM ?:product_options_exceptions WHERE product_id = ?i", $product_id);
    }

    db_query("DELETE FROM ?:product_global_option_links WHERE product_id = ?i", $product_id);

    $option_combinations = db_get_fields('SELECT combination_hash FROM ?:product_options_inventory WHERE product_id = ?i', $product_id);
    if (!empty($option_combinations)) {
        foreach ($option_combinations as $hash) {
            fn_delete_product_combination($hash);
        }
    }

    /**
     * Adds additional actions after delete all product option
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('poptions_delete_product_post', $product_id);
}

/**
 * Gets product options with the selected values data
 *
 * @param int $product_id Product identifier
 * @param array $selected_options Selected opotions values
 * @param string $lang_code 2-letters language code
 * @return array List of product options with selected values
 */
function fn_get_selected_product_options($product_id, $selected_options, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params for selecting product options with selected values
     *
     * @param int    $product_id       Product identifier
     * @param array  $selected_options Selected opotions values
     * @param string $lang_code        2-letters language code
     */
    fn_set_hook('get_selected_product_options_pre', $product_id, $selected_options, $lang_code);

    $extra_variant_fields = '';
    $fields = db_quote("a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment, a.status");
    $condition = db_quote("(a.product_id = ?i OR c.product_id = ?i) AND a.status = 'A'", $product_id, $product_id);
    $join = db_quote("LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id", $lang_code);

    /**
     * Changes params before selecting product options
     *
     * @param string $fields               Fields to be selected
     * @param string $condition            String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join                 String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $extra_variant_fields Additional variant fields to be selected
     */
    fn_set_hook('get_selected_product_options_before_select', $fields, $condition, $join, $extra_variant_fields);

    $_opts = db_get_array("SELECT $fields FROM ?:product_options as a $join WHERE $condition ORDER BY a.position");
    if (is_array($_opts)) {
        $_status = (AREA == 'A') ? '' : " AND a.status = 'A'";
        foreach ($_opts as $k => $v) {
            $_vars = db_get_hash_array("SELECT a.variant_id, a.position, a.modifier, a.modifier_type, a.weight_modifier, a.weight_modifier_type, $extra_variant_fields  b.variant_name FROM ?:product_option_variants as a LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s WHERE a.option_id = ?i $_status ORDER BY a.position", 'variant_id', $lang_code, $v['option_id']);

            // Add variant names manually, if this option is "checkbox"
            if ($v['option_type'] == 'C' && !empty($_vars)) {
                foreach ($_vars as $variant_id => $variant) {
                    $_vars[$variant_id]['variant_name'] = $variant['position'] == 0 ? __('no') : __('yes');
                }
            }

            $_opts[$k]['value'] = (!empty($selected_options[$v['option_id']])) ? $selected_options[$v['option_id']] : '';
            $_opts[$k]['variants'] = $_vars;
        }

    }

    /**
     * Changes selected product options
     *
     * @param array  $_opts            Selected product options
     * @param int    $product_id       Product identifier
     * @param array  $selected_options Selected opotions values
     * @param string $lang_code        2-letters language code
     */
    fn_set_hook('get_selected_product_options_post', $_opts, $product_id, $selected_options, $lang_code);

    return $_opts;
}

/**
 * Calculates product price/weight with options modifiers
 *
 * @param array $product_options Product options
 * @param mixed $base_value Base price or weight value
 * @param string $type Calculation type (price or weight)
 * @param array $orig_options Original options
 * @param array $extra Extra data
 * @param mixed Recalculated value
 * @return mixed New base value after applying modifiers
 */
function fn_apply_options_modifiers($product_options, $base_value, $type, $orig_options = array(), $extra = array())
{
    static $option_types_cache = array();
    static $option_modifiers_cache = array();

    $fields = ($type == 'P') ? "a.modifier, a.modifier_type" : "a.weight_modifier as modifier, a.weight_modifier_type as modifier_type";

    /**
     * Apply option modifiers (at the beginning of the fn_apply_options_modifiers())
     *
     * @param array  $product_options Product options
     * @param mixed  $base_value      Base value
     * @param array  $orig_options    Original options
     * @param array  $extra           Extra data
     * @param string $fields          String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $type            Calculation type (price or weight)
     */
    fn_set_hook('apply_option_modifiers_pre', $product_options, $base_value, $orig_options, $extra, $fields, $type);

    foreach ($orig_options as $key => $value) {
        // Exclude disabled (Forbidden) options
        if (empty($value['value'])) {
            unset($orig_options[$key]);
        }
    }

    $orig_value = $base_value;
    if (!empty($product_options)) {

        // Check options type. We need to apply only Selectbox, radiogroup and checkbox modifiers
        if (empty($orig_options)) {
            $cache_key = md5(serialize(array_keys($product_options)));
            if (!isset($option_types_cache[$cache_key])) {
                $option_types = db_get_hash_single_array("SELECT option_type as type, option_id FROM ?:product_options WHERE option_id IN (?n)", array('option_id', 'type'), array_keys($product_options));
                $option_types_cache[$cache_key] = $option_types;
            } else {
                $option_types = $option_types_cache[$cache_key];
            }
        } else {
            $option_types = array();
            foreach ($orig_options as $_opt) {
                $option_types[$_opt['option_id']] = $_opt['option_type'];
            }
        }

        $modifiers_queue = array();
        $applied_variants_ids = array();
        foreach ($product_options as $option_id => $variant_id) {
            if (empty($option_types[$option_id]) || strpos('SRC', $option_types[$option_id]) === false) {
                continue;
            }
            if (empty($orig_options)) {
                $cache_key = md5($fields . $variant_id);
                if (!isset($option_modifiers_cache[$cache_key])) {
                    $modifiers_queue[$variant_id] = array();
                    $applied_variants_ids[] = $variant_id;
                } else {
                    $modifiers_queue[$variant_id] = $option_modifiers_cache[$cache_key];
                }
            } else {
                foreach ($orig_options as $_opt) {
                    if ($_opt['value'] == $variant_id && !empty($variant_id)) {
                        $modifiers_queue[$variant_id] = array(
                            'modifier' => $_opt['modifier'],
                            'modifier_type' => $_opt['modifier_type']
                        );
                    }
                }
            }
        }

        // Fetch modifiers for all variants at once
        if ($applied_variants_ids) {
            $om_join = "";
            $om_condition = db_quote("a.variant_id IN (?n)", $applied_variants_ids);
            /**
             * Changes SQL-query params before option modifiers selecting
             *
             * @param string $type                 Calculation type (price or weight)
             * @param string $fields               Fields to be selected
             * @param string $om_condition         String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
             * @param string $om_join              String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
             * @param array  $applied_variants_ids Variant identifiers
             */
            fn_set_hook('apply_option_modifiers_get_option_modifiers', $type, $fields, $om_join, $om_condition, $applied_variants_ids);
            $modifiers = db_get_hash_array("SELECT ?p, a.variant_id FROM ?:product_option_variants a ?p WHERE 1 AND ?p", 'variant_id', $fields, $om_join, $om_condition);
            foreach ($modifiers as $variant_id => $modifier) {
                $cache_key = md5($fields . $variant_id);
                $option_modifiers_cache[$cache_key] = $modifiers_queue[$variant_id] = $modifier;
            }
            unset($modifier);
        }

        foreach($modifiers_queue as $modifier) {
            if (!empty($modifier)) {
                if ($modifier['modifier_type'] == 'A') {
                    // Absolute
                    $base_value += floatval($modifier['modifier']);
                } else {
                    // Percentage
                    $base_value += ((floatval($modifier['modifier']) * $orig_value)/100);
                }
            }
        }
        unset($modifier);
    }

    $base_value = ($base_value > 0) ? $base_value : 0;

    /**
     * Apply option modifiers (at the end of the fn_apply_options_modifiers())
     *
     * @param array  $product_options Product options
     * @param mixed  $base_value      Base value
     * @param string $type            Calculation type (price or weight)
     * @param array  $orig_options    Original options
     * @param mixed  $orig_value      Original base value
     * @param string $fields          String of comma-separated SQL fields to be selected in an SQL-query
     * @param array  $extra           Extra data
     */
    fn_set_hook('apply_option_modifiers_post', $product_options, $base_value, $type, $orig_options, $orig_value, $fields, $extra);

    return $base_value;
}

/**
 * Returns selected product options.
 * For options wich type is checkbox function gets translation from langvars 'no' and 'yes' and return it as variant_name.
 *
 * @param array  $selected_options Options as option_id => selected_variant_id.
 * @param string $lang_code        2digits language code.
 *
 * @return array Array of associative arrays wich contain options data.
 */
function fn_get_selected_product_options_info($selected_options, $lang_code = CART_LANGUAGE)
{
    /**
     * Get selected product options info (at the beginning of the fn_get_selected_product_options_info())
     *
     * @param array  $selected_options Selected options
     * @param string $lang_code        Language code
     */
    fn_set_hook('get_selected_product_options_info_pre', $selected_options, $lang_code);

    if (empty($selected_options) || !is_array($selected_options)) {
        return array();
    }
    $result = array();
    foreach ($selected_options as $option_id => $variant_id) {
        $_opts = db_get_row(
            "SELECT a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message " .
            "FROM ?:product_options as a LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s " .
            "WHERE a.option_id = ?i ORDER BY a.position",
            $lang_code, $option_id
        );

        if (empty($_opts)) {
            continue;
        }
        $_vars = array();
        if (strpos('SRC', $_opts['option_type']) !== false) {
            $_vars = db_get_row(
                "SELECT a.modifier, a.modifier_type, a.position, b.variant_name FROM ?:product_option_variants as a " .
                "LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s " .
                "WHERE a.variant_id = ?i ORDER BY a.position",
                $lang_code, $variant_id
            );
        }

        if ($_opts['option_type'] == 'C') {
            $_vars['variant_name'] = (empty($_vars['position'])) ? __('no', '', $lang_code) : __('yes', '', $lang_code);
        } elseif ($_opts['option_type'] == 'I' || $_opts['option_type'] == 'T') {
            $_vars['variant_name'] = $variant_id;
        } elseif (!isset($_vars['variant_name'])) {
            $_vars['variant_name'] = '';
        }

        $_vars['value'] = $variant_id;

        $result[] = fn_array_merge($_opts ,$_vars);
    }

    /**
     * Get selected product options info (at the end of the fn_get_selected_product_options_info())
     *
     * @param array  $selected_options Selected options
     * @param string $lang_code        Language code
     * @param array  $result           List of the option info arrays
     */
    fn_set_hook('get_selected_product_options_info_post', $selected_options, $lang_code, $result);

    return $result;
}

/**
 * Gets default product options
 *
 * @param integer $product_id Product identifier
 * @param bool $get_all Whether to get all the default options or not
 * @param array $product Product data
 * @return array The resulting array
 */
function fn_get_default_product_options($product_id, $get_all = false, $product = array())
{
    $result = $default = $exceptions = $product_options = array();
    $selectable_option_types = array('S', 'R', 'C');

    /**
    * Get default product options ( at the beginning of fn_get_default_product_options() )
    *
    * @param integer $product_id Product id
    * @param bool $get_all Whether to get all the default options or not
    * @param array $product Product data
    * @param array $selectable_option_types Selectable option types
    */
    fn_set_hook('get_default_product_options_pre', $product_id, $get_all, $product, $selectable_option_types);

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        $exceptions = fn_get_product_exceptions($product_id, true);
        $exceptions_type = (empty($product['exceptions_type']))? db_get_field('SELECT exceptions_type FROM ?:products WHERE product_id = ?i', $product_id) : $product['exceptions_type'];
    }

    $track_with_options = (empty($product['tracking']))? db_get_field("SELECT tracking FROM ?:products WHERE product_id = ?i", $product_id) : $product['tracking'];

    if (!empty($product['product_options'])) {
        //filter out only selectable options
        foreach ($product['product_options'] as $option_id => $option) {
            if (in_array($option['option_type'], $selectable_option_types)) {
                $product_options[$option_id] = $option;
            }
        }
    } else {
        $product_options = fn_get_product_options($product_id, CART_LANGUAGE, true);
    }

    if (!empty($product_options)) {
        foreach ($product_options as $option_id => $option) {
            if (!empty($option['variants'])) {
                $default[$option_id] = key($option['variants']);
                foreach ($option['variants'] as $variant_id => $variant) {
                    $options[$option_id][$variant_id] = true;
                }
            }
        }
    } else {
        return array();
    }

    unset($product_options);
    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if (empty($exceptions)) {
            return $default;
        }
    }

    $inventory_combinations = array();
    if ($track_with_options == ProductTracking::TRACK_WITH_OPTIONS) {
        $inventory_combinations = db_get_array("SELECT combination FROM ?:product_options_inventory WHERE product_id = ?i AND amount > 0 AND combination != ''", $product_id);
        if (!empty($inventory_combinations)) {
            $_combinations = array();
            foreach ($inventory_combinations as $_combination) {
                $_combinations[] = fn_get_product_options_by_combination($_combination['combination']);
            }
            $inventory_combinations = $_combinations;
            unset($_combinations);
        }
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if ($exceptions_type == 'F') {
            // Forbidden combinations
            $_options = array_keys($options);
            $_variants = array_values($options);
            if (!empty($_variants)) {
                foreach ($_variants as $key => $variants) {
                    $_variants[$key] = array_keys($variants);
                }
            }

            list($result) = fn_get_allowed_options_combination($_options, $_variants, '', 0, $exceptions, $inventory_combinations);

        } else {
            // Allowed combinations
            $result = array();
            $exception = reset($exceptions);
            foreach ($exception as $option_id => $variant_id) {
                if (isset($options[$option_id][$variant_id])) {
                    $result[$option_id] = $variant_id;
                } elseif ($variant_id == OPTION_EXCEPTION_VARIANT_ANY) {
                    $result[$option_id] = isset($options[$option_id]) ? key($options[$option_id]) : '';
                }
            }

            $_opt = array_diff_key($options, $result);
            foreach ($_opt as $option_id => $variants) {
                $result[$option_id] = key($variants);
            }
        }
    }

    /**
    * Get default product options ( at the end of fn_get_default_product_options() )
    *
    * @param integer $product_id Product id
    * @param bool $get_all Whether to get all the default options or not
    * @param array $product Product data
    * @param array $result The resulting array
    */
    fn_set_hook('get_default_product_options_post', $product_id, $get_all, $product, $result);

    return empty($result) ? $default : $result;
}

/**
 * Gets all possible options combinations
 *
 * @param array $options Options identifiers
 * @param array $variants Options variants identifiers in the order according to the $options parameter
 * @return array Combinations
 */
function fn_get_options_combinations($options, $variants)
{
    $combinations = array();

    // Take first option
    $options_key = array_keys($options);
    $variant_number = reset($options_key);
    $option_id = $options[$variant_number];

    // Remove current option
    unset($options[$variant_number]);

    // Get combinations for other options
    $sub_combinations = !empty($options) ? fn_get_options_combinations($options, $variants) : array();

    if (!empty($variants[$variant_number])) {
        // run through variants
        foreach ($variants[$variant_number] as $variant) {
            if (!empty($sub_combinations)) {
                // add current variant to each subcombination
                foreach ($sub_combinations as $sub_combination) {
                    $sub_combination[$option_id] = $variant;
                    $combinations[] = $sub_combination;
                }
            } else {
                $combinations[] = array(
                    $option_id => $variant
                );
            }
        }
    } else {
        $combinations = $sub_combinations;
    }

    return  $combinations;
}

/**
 * Generates product variants combinations
 *
 * @param int $product_id Product identifier
 * @param int $amount Default combination amount
 * @param array $options Array of option identifiers
 * @param array $variants Array of option variant identifier arrays in the order according to the $options parameter
 * @return array Array of combinations
 */
function fn_look_through_variants($product_id, $amount, $options, $variants)
{
    /**
     * Changes params for getting product variants combinations
     *
     * @param int   $product_id Product identifier
     * @param int   $amount     Default combination amount
     * @param array $options    Array of options identifiers
     * @param array $variants   Array of option variants identifiers arrays in order corresponding to $options parameter
     * @param array $string     Array of combinations values
     * @param int   $cycle      Options and variants key
     */
    fn_set_hook('look_through_variants_pre', $product_id, $amount, $options, $variants);

    $position = 0;
    $hashes = array();
    $combinations = fn_get_options_combinations($options, $variants);

    if (!empty($combinations)) {
        foreach ($combinations as $combination) {

            $_data = array();
            $_data['product_id'] = $product_id;

            $_data['combination_hash'] = fn_generate_cart_id($product_id, array('product_options' => $combination));

            if (array_search($_data['combination_hash'], $hashes) === false) {
                $hashes[] = $_data['combination_hash'];
                $_data['combination'] = fn_get_options_combination($combination);
                $_data['position'] = $position++;

                $old_data = db_get_row(
                    "SELECT combination_hash, amount, product_code "
                    . "FROM ?:product_options_inventory "
                    . "WHERE product_id = ?i AND combination_hash = ?i AND temp = 'Y'",
                    $product_id, $_data['combination_hash']
                );

                $_data['amount'] = isset($old_data['amount']) ? $old_data['amount'] : $amount;
                $_data['product_code'] = isset($old_data['product_code']) ? $old_data['product_code'] : '';

                /**
                 * Changes data before update combination
                 *
                 * @param array $combination Array of combination data
                 * @param array $data Combination data to update
                 * @param int $product_id Product identifier
                 * @param int $amount Default combination amount
                 * @param array $options Array of options identifiers
                 * @param array $variants Array of option variants identifiers arrays in order corresponding to $options parameter
                 */
                fn_set_hook('look_through_variants_update_combination', $combination, $_data, $product_id, $amount, $options, $variants);

                db_query("REPLACE INTO ?:product_options_inventory ?e", $_data);
                $combinations[] = $combination;
            }
            echo str_repeat('. ', count($combination));
        }
    }

    /**
     * Changes the product options combinations
     *
     * @param array $combination Array of combinations
     * @param int   $product_id  Product identifier
     * @param int   $amount      Default combination amount
     * @param array $options     Array of options identifiers
     * @param array $variants    Array of option variants identifiers arrays in order corresponding to $options parameter
     */
    fn_set_hook('look_through_variants_post', $combinations, $product_id, $amount, $options, $variants);

    return $combinations;
}

/**
 * Checks and rebuilds product options inventory if necessary
 *
 * @param int $product_id Product identifier
 * @param int $amount Default combination amount
 * @return boolean Always true
 */
function fn_rebuild_product_options_inventory($product_id, $amount = 50)
{

    /**
     * Changes parameters for rebuilding product options inventory
     * @param int $product_id Product identifier
     * @param int $amount     Default combination amount
     */
    fn_set_hook('rebuild_product_options_inventory_pre', $product_id, $amount);

    $_options = db_get_fields("SELECT a.option_id FROM ?:product_options as a LEFT JOIN ?:product_global_option_links as b ON a.option_id = b.option_id WHERE (a.product_id = ?i OR b.product_id = ?i) AND a.option_type IN ('S','R','C') AND a.inventory = 'Y' ORDER BY position", $product_id, $product_id);
    if (empty($_options)) {
        return;
    }

    db_query("UPDATE ?:product_options_inventory SET temp = 'Y' WHERE product_id = ?i", $product_id);
    foreach ($_options as $k => $option_id) {
        $variants[$k] = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i ORDER BY position", $option_id);
    }
    $combinations = fn_look_through_variants($product_id, $amount, $_options, $variants);

    // Delete image pairs assigned to old combinations
    $hashes = db_get_fields("SELECT combination_hash FROM ?:product_options_inventory WHERE product_id = ?i AND temp = 'Y'", $product_id);
    foreach ($hashes as $v) {
        fn_delete_image_pairs($v, 'product_option');
    }

    // Delete old combinations
    db_query("DELETE FROM ?:product_options_inventory WHERE product_id = ?i AND temp = 'Y'", $product_id);

    /**
     * Adds additional actions after rebuilding product options inventory
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('rebuild_product_options_inventory_post', $product_id);

    return true;
}

/**
 * Gets array of product features
 *
 * @param array $params Products features search params
 * @param int $items_per_page Items per page
 * @param string $lang_code 2-letters language code
 * @return array Array with 3 params
 *              array $data Products features data
 *              array $params Products features search params
 *              boolean $has_ungroupped Flag determines if there are features without group
 */
function fn_get_product_features($params = array(), $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params before getting products features
     *
     * @param array  $params         Products features search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_features_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('product_features', $params);

    $default_params = array(
        'product_id' => 0,
        'category_ids' => array(),
        'statuses' => AREA == 'C' ? array('A') : array(),
        'plain' => false,
        'feature_types' => array(),
        'feature_id' => 0,
        'display_on' => '',
        'exclude_group' => false,
        'exclude_filters' => false,
        'page' => 1,
        'items_per_page' => $items_per_page,

        // Whether to load only features that have variants assigned or value applied to given product.
        // Parameter is only used if "product_id" is given.
        'existent_only' => false,

        // Whether to load variants for loaded features.
        'variants' => false,

        // Whether to load only variants that are assigned for given product.
        // Parameter is only used if "product_id" is given and "variants" is set to true.
        'variants_selected_only' => false,

        // Whether to skip restriction on maximal count of variants to be loaded.
        'skip_variants_threshould' => false,

        // List of variant IDs that should be loaded in case of count of variants to be loaded is more
        // than specified variants threshold. Format: [feature_id => [variant_id, ...], ...].
        // Parameter is only used if "variants" param is set to true and "skip_variants_threshould" is set to false.
        'variants_only' => null,
    );

    $params = array_merge($default_params, $params);

    $base_fields = $fields = array (
        'pf.feature_id',
        'pf.company_id',
        'pf.feature_type',
        'pf.parent_id',
        'pf.display_on_product',
        'pf.display_on_catalog',
        'pf.display_on_header',
        '?:product_features_descriptions.description',
        '?:product_features_descriptions.lang_code',
        '?:product_features_descriptions.prefix',
        '?:product_features_descriptions.suffix',
        'pf.categories_path',
        '?:product_features_descriptions.full_description',
        'pf.status',
        'pf.comparison',
        'pf.position'
    );

    $condition = $join = $group = '';
    $group_condition = '';

    $fields[] = 'groups.position AS group_position';
    $join .= db_quote(" LEFT JOIN ?:product_features AS groups ON pf.parent_id = groups.feature_id");
    $join .= db_quote(" LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = pf.feature_id AND ?:product_features_descriptions.lang_code = ?s", $lang_code);

    if (empty($params['feature_types']) || $params['feature_types'] != ProductFeatures::GROUP) {
        $condition .= db_quote(" AND pf.feature_type != ?s", ProductFeatures::GROUP);
    }

    if (!empty($params['product_id'])) {
        $feature_values_join_type = empty($params['existent_only']) ? 'LEFT' : 'INNER';
        $join .= db_quote(
            " {$feature_values_join_type} JOIN ?:product_features_values"
            . " ON ?:product_features_values.feature_id = pf.feature_id"
            . " AND ?:product_features_values.product_id = ?i"
            . " AND ?:product_features_values.lang_code = ?s",
            $params['product_id'],
            $lang_code
        );

        $fields[] = '?:product_features_values.value';
        $fields[] = '?:product_features_values.variant_id';
        $fields[] = '?:product_features_values.value_int';

        $group = ' GROUP BY pf.feature_id';
    }

    if (!empty($params['feature_id'])) {
        $condition .= db_quote(" AND pf.feature_id IN (?n)", $params['feature_id']);
    }

    if (isset($params['description']) && fn_string_not_empty($params['description'])) {
        $condition .= db_quote(" AND ?:product_features_descriptions.description LIKE ?l", "%" . trim($params['description']) . "%");
    }

    if (!empty($params['statuses'])) {
        $condition .= db_quote(" AND pf.status IN (?a)", $params['statuses']);
    }

    if (isset($params['parent_id']) && $params['parent_id'] !== '') {
        $condition .= db_quote(" AND pf.parent_id = ?i", $params['parent_id']);
        $group_condition .= db_quote(" AND pf.feature_id = ?i", $params['parent_id']);
    }

    if (!empty($params['display_on']) && in_array($params['display_on'], array('product', 'catalog', 'header'))) {
        $condition .= " AND pf.display_on_$params[display_on] = 'Y'";
        $group_condition .= " AND pf.display_on_$params[display_on] = 'Y'";
    }

    if (!empty($params['feature_types'])) {
        $condition .= db_quote(" AND pf.feature_type IN (?a)", $params['feature_types']);
    }

    if (!empty($params['category_ids'])) {
        $c_ids = is_array($params['category_ids']) ? $params['category_ids'] : fn_explode(',', $params['category_ids']);
        $find_set = array(
            " pf.categories_path = '' OR ISNULL(pf.categories_path)"
        );

        if (!empty($params['search_in_subcats'])) {
            $child_ids = db_get_fields("SELECT a.category_id FROM ?:categories as a LEFT JOIN ?:categories as b ON b.category_id IN (?n) WHERE a.id_path LIKE CONCAT(b.id_path, '/%')", $c_ids);
            $c_ids = fn_array_merge($c_ids, $child_ids, false);
        }

        foreach ($c_ids as $k => $v) {
            $find_set[] = db_quote(" FIND_IN_SET(?i, pf.categories_path) ", $v);
        }

        $find_in_set = db_quote(" AND (?p)", implode('OR', $find_set));
        $condition .= $find_in_set;
        $group_condition .= $find_in_set;
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if (!empty($params['exclude_filters'])) {
            $_condition = ' WHERE 1 ';

            if (fn_allowed_for('ULTIMATE')) {
                $_condition .= fn_get_company_condition('?:product_filters.company_id');
            }

            $exclude_feature_id = db_get_fields("SELECT ?:product_filters.feature_id FROM ?:product_filters $_condition GROUP BY ?:product_filters.feature_id");
            if (!empty($exclude_feature_id)) {
                $condition .= db_quote(" AND pf.feature_id NOT IN (?n)", $exclude_feature_id);
                unset($exclude_feature_id);
            }
        }
    }

    /**
     * Change SQL parameters before product features selection
     *
     * @param array  $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     */
    fn_set_hook('get_product_features', $fields, $join, $condition, $params);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            "SELECT COUNT(DISTINCT pf.feature_id) FROM ?:product_features AS pf $join WHERE 1 $condition"
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $data = db_get_hash_array(
        "SELECT " . implode(', ', $fields)
        . " FROM ?:product_features AS pf"
        . " $join WHERE 1 $condition $group"
        . " ORDER BY group_position, pf.position, ?:product_features_descriptions.description $limit",
        'feature_id'
    );

    $has_ungroupped = false;

    // Fetch variants for loaded features
    if (!empty($data) && $params['variants']) {

        // Only fetch variants for selectable features
        $feature_ids = array();
        foreach ($data as $feature_id => $feature_data) {
            if (strpos(ProductFeatures::getSelectable(), $feature_data['feature_type']) !== false) {
                $feature_ids[] = $feature_id;
                $data[$feature_id]['variants'] = array(); // initialize variants
            }
        }

        // Variants to load if count of variants to be loaded is more than threshold
        // [feature_id => [variant_id, ...], ...]
        $variant_ids_to_load = isset($params['variants_only']) ? (array) $params['variants_only'] : array();

        foreach ($feature_ids as $feature_id) {
            $variants_params = array(
                'feature_id' => $feature_id,
                'product_id' => $params['product_id'],
                'get_images' => true,
                'selected_only' => $params['variants_selected_only']
            );

            if (AREA == 'A' && empty($params['skip_variants_threshould'])) {
                // Fetch count of variants to be loaded
                $variants_params['fetch_total_count_only'] = true;
                $total_variants_count = fn_get_product_feature_variants($variants_params, 0, $lang_code);
                $variants_params['fetch_total_count_only'] = false;

                if ($total_variants_count > PRODUCT_FEATURE_VARIANTS_THRESHOLD) {
                    // AJAX variants loader will be used
                    $data[$feature_id]['use_variant_picker'] = true;

                    // Fetch only selected variants for given product (if it is given).
                    // These variants would be used for displaying preselection at AJAX variants loader.
                    if (!empty($params['product_id'])) {
                        $variants_params['selected_only'] = true;
                    }
                    // Load specific variants (for example for preselection at AJAX loader at search form)
                    elseif (!empty($variant_ids_to_load[$feature_id])) {
                        // Restrict selection to specified variant IDs
                        $variants_params['variant_id'] = $variant_ids_to_load[$feature_id];
                    }
                    // Skip loading variants.
                    else {
                        continue;
                    }
                }
            }

            list($variants, $search) = fn_get_product_feature_variants($variants_params, 0, $lang_code);

            foreach ($variants as $variant) {
                $data[$variant['feature_id']]['variants'][$variant['variant_id']] = $variant;
            }
        }
    }

    foreach ($data as $feature_data) {
        if (empty($feature_data['parent_id'])) {
            $has_ungroupped = true;
            break;
        }
    }

    // Get groups
    if (empty($params['exclude_group'])) {

        $group_ids = array();
        foreach ($data as $feature_data) {
            if (!empty($feature_data['parent_id'])) {
                $group_ids[$feature_data['parent_id']] = true;
            }
        }

        $groups = db_get_hash_array("SELECT " . implode(', ', $base_fields) . " FROM ?:product_features AS pf LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = pf.feature_id AND ?:product_features_descriptions.lang_code = ?s WHERE pf.feature_type = 'G' AND (pf.feature_id IN (?n) OR pf.feature_id NOT IN (SELECT parent_id FROM ?:product_features)) ?p ORDER BY pf.position, ?:product_features_descriptions.description", 'feature_id', $lang_code, array_keys($group_ids), $group_condition);

        // Insert groups before appropriate features
        $new_data = $groups;
        foreach ($data as $feature_id => $feature_data) {
            if (!empty($feature_data['parent_id']) && !empty($groups[$feature_data['parent_id']])) {
                $new_data[$feature_data['parent_id']] = $groups[$feature_data['parent_id']];
                unset($groups[$feature_data['parent_id']]);
            }
            $new_data[$feature_id] = $feature_data;
        }
        $data = $new_data;
    }

    if ($params['plain'] == false) {
        $delete_keys = array();
        foreach ($data as $k => $v) {
            if (!empty($v['parent_id']) && !empty($data[$v['parent_id']])) {
                $data[$v['parent_id']]['subfeatures'][$v['feature_id']] = $v;
                $data[$k] = & $data[$v['parent_id']]['subfeatures'][$v['feature_id']];
                $delete_keys[] = $k;
            }

            if (!empty($params['get_descriptions']) && empty($v['parent_id'])) {
                $d = fn_get_categories_list($v['categories_path']);
                $data[$k]['feature_description'] = __('display_on') . ': <span>' . implode(', ', $d) . '</span>';
            }
        }

        foreach ($delete_keys as $k) {
            unset($data[$k]);
        }
    }

    /**
     * Change products features data
     *
     * @param array   $data           Products features data
     * @param array   $params         Products features search params
     * @param boolean $has_ungroupped Flag determines if there are features without group
     */
    fn_set_hook('get_product_features_post', $data, $params, $has_ungroupped);

    LastView::instance()->processResults('product_features', $data, $params);

    return array($data, $params, $has_ungroupped);
}

/**
 * Gets single product feature data
 *
 * @param int $feature_id Feature identifier
 * @param boolean $get_variants Flag determines if product variants should be fetched
 * @param boolean $get_variant_images Flag determines if variant images should be fetched
 * @param string $lang_code 2-letters language code
 * @return array Product feature data
 */
function fn_get_product_feature_data($feature_id, $get_variants = false, $get_variant_images = false, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params before getting product feature data
     *
     * @param int     $feature_id         Feature identifier
     * @param boolean $get_variants       Flag determines if product variants should be fetched
     * @param boolean $get_variant_images Flag determines if variant images should be fetched
     * @param string  $lang_code          2-letters language code
     */
    fn_set_hook('get_product_feature_data_pre', $feature_id, $get_variants, $get_variant_images, $lang_code);

    $fields = array(
        '?:product_features.feature_id',
        '?:product_features.feature_code',
        '?:product_features.company_id',
        '?:product_features.feature_type',
        '?:product_features.parent_id',
        '?:product_features.display_on_product',
        '?:product_features.display_on_catalog',
        '?:product_features.display_on_header',
        '?:product_features_descriptions.description',
        '?:product_features_descriptions.lang_code',
        '?:product_features_descriptions.prefix',
        '?:product_features_descriptions.suffix',
        '?:product_features.categories_path',
        '?:product_features_descriptions.full_description',
        '?:product_features.status',
        '?:product_features.comparison',
        '?:product_features.position'
    );

    $join = db_quote("LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id AND ?:product_features_descriptions.lang_code = ?s", $lang_code);

    $condition = db_quote("?:product_features.feature_id = ?i", $feature_id);

    /**
     * Change SQL parameters before fetching product feature data
     *
     * @param array   $fields             Array SQL fields to be selected in an SQL-query
     * @param string  $join               String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string  $condition          String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param int     $feature_id         Feature identifier
     * @param boolean $get_variants       Flag determines if product variants should be fetched
     * @param boolean $get_variant_images Flag determines if variant images should be fetched
     * @param string  $lang_code          2-letters language code
     */
    fn_set_hook('get_product_feature_data_before_select', $fields, $join, $condition, $feature_id, $get_variants, $get_variant_images, $lang_code);

    $feature_data = db_get_row("SELECT " . implode(",", $fields) . " FROM ?:product_features $join WHERE $condition");

    if ($get_variants == true && $feature_data) {
        list($feature_data['variants']) = fn_get_product_feature_variants(array(
            'feature_id' => $feature_id,
            'feature_type' => $feature_data['feature_type'],
            'get_images' => $get_variant_images
        ), 0, $lang_code);
    }

    /**
     * Change product feature data
     *
     * @param array $feature_data Product feature data
     */
    fn_set_hook('get_product_feature_data_post', $feature_data);

    return $feature_data;
}

/**
 * Gets product features list
 *
 * @TODO Merge with {fn_get_product_features()}
 *
 * @param array  $product    Array with product data
 * @param string $display_on Code determines zone (product/catalog page) for that features are selected
 * @param string $lang_code  2-letters language code
 *
 * @return array Product features
 */
function fn_get_product_features_list($product, $display_on = 'C', $lang_code = CART_LANGUAGE)
{
    static $filters = null;

    /**
     * Changes params before getting product features list
     *
     * @param array  $product    Array with product data
     * @param string $display_on Code determines zone (product/catalog page) for that features are selected
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_features_list_pre', $product, $display_on, $lang_code);

    $product_id = $product['product_id'];

    $features_list = array();

    if ($display_on == 'H') {
        $condition = " AND f.display_on_header = 'Y'";
    } elseif ($display_on == 'C') {
        $condition = " AND f.display_on_catalog = 'Y'";
    } elseif ($display_on == 'CP') {
        $condition = " AND (f.display_on_catalog = 'Y' OR f.display_on_product = 'Y')";
    } elseif ($display_on == 'A' || $display_on == 'EXIM') {
        $condition = '';
    } else {
        $condition = " AND f.display_on_product = 'Y'";
    }

    $category_ids = array();

    if (!empty($product['category_ids'])) {
        $category_ids = $product['category_ids'];
    } elseif (!empty($product['main_category'])) {
        $category_ids = (array) $product['main_category'];
    }

    $path = fn_get_category_ids_with_parent($category_ids);

    $find_set = array(
        " f.categories_path = '' "
    );
    foreach ($path as $k => $v) {
        $find_set[] = db_quote(" FIND_IN_SET(?i, f.categories_path) ", $v);
    }
    $find_in_set = db_quote(" AND (?p)", implode('OR', $find_set));
    $condition .= $find_in_set;

    $fields = db_quote("v.feature_id, v.value, v.value_int, v.variant_id, f.feature_type, fd.description, fd.prefix, fd.suffix, vd.variant, f.parent_id, f.position, gf.position as gposition");
    $join = db_quote(
        "LEFT JOIN ?:product_features_values as v ON v.feature_id = f.feature_id "
        . " LEFT JOIN ?:product_features_descriptions as fd ON fd.feature_id = v.feature_id AND fd.lang_code = ?s"
        . " LEFT JOIN ?:product_feature_variants fv ON fv.variant_id = v.variant_id"
        . " LEFT JOIN ?:product_feature_variant_descriptions as vd ON vd.variant_id = fv.variant_id AND vd.lang_code = ?s"
        . " LEFT JOIN ?:product_features as gf ON gf.feature_id = f.parent_id AND gf.feature_type = ?s ",
        $lang_code, $lang_code, ProductFeatures::GROUP);

    // Features should be active and be assigned to given product
    $allowed_feature_statuses = array('A');
    if ($display_on == 'EXIM') {
        $allowed_feature_statuses[] = 'H';
    }
    $condition = db_quote("f.status IN (?a) AND v.product_id = ?i ?p", $allowed_feature_statuses, $product_id, $condition);

    // Parent group of feature (if any) status condition
    $allowed_parent_group_statuses = array('A');
    if ($display_on == 'EXIM') {
        $allowed_parent_group_statuses[] = 'H';
    }
    $condition .= db_quote(
        " AND IF(f.parent_id,"
        . " (SELECT status FROM ?:product_features as df WHERE df.feature_id = f.parent_id), 'A') IN (?a)",
        $allowed_parent_group_statuses
    );

    $condition .= db_quote(
        " AND ("
        . " v.variant_id != 0"
        . " OR (f.feature_type != ?s AND v.value != '')"
        . " OR (f.feature_type = ?s)"
        . " OR v.value_int != ''"
        . ")"
        . " AND v.lang_code = ?s",
        ProductFeatures::SINGLE_CHECKBOX, ProductFeatures::SINGLE_CHECKBOX, $lang_code
    );

    /**
     * Change SQL parameters before fetching product feature data
     *
     * @param string $fields    String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param array  $product   Array with product data
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_product_features_list_before_select', $fields, $join, $condition, $product, $display_on, $lang_code);

    $_data = db_get_array("SELECT $fields FROM ?:product_features as f $join WHERE $condition ORDER BY fd.description, fv.position");
    $_variant_ids = array();

    if (!empty($_data)) {
        if ($filters === null) {
            $filter_condition = "status = 'A'";

            if (fn_allowed_for('ULTIMATE')) {
                $filter_condition .= fn_get_company_condition('?:product_filters.company_id');
            }

            $filters = db_get_hash_array("SELECT filter_id, feature_id FROM ?:product_filters WHERE {$filter_condition}", 'feature_id');
        }

        foreach ($_data as $k => $feature) {
            if ($feature['feature_type'] == ProductFeatures::SINGLE_CHECKBOX) {
                if ($feature['value'] != 'Y' && $display_on != 'A') {
                    unset($_data[$k]);
                    continue;
                }
            }

            if (empty($features_list[$feature['feature_id']])) {
                $features_list[$feature['feature_id']] = $feature;
            }

            if (!empty($feature['variant_id'])) { // feature has several variants
                if (isset($filters[$feature['feature_id']])) {
                    $features_list[$feature['feature_id']]['features_hash'] = fn_add_filter_to_hash(
                        '',
                        $filters[$feature['feature_id']]['filter_id'],
                        $feature['variant_id']
                    );
                }

                $features_list[$feature['feature_id']]['variants'][$feature['variant_id']] = array(
                    'value' => $feature['value'],
                    'value_int' => $feature['value_int'],
                    'variant_id' => $feature['variant_id'],
                    'variant' => $feature['variant'],
                );
                $_variant_ids[] = $feature['variant_id'];
            }
        }

        if (!empty($_variant_ids)) {
            $images = fn_get_image_pairs($_variant_ids, 'feature_variant', 'V', true, true, $lang_code);

            foreach ($features_list as $feature_id => $feature) {
                if (isset($images[$feature['variant_id']])) {
                    $features_list[$feature_id]['variants'][$feature['variant_id']]['image_pairs'] = reset($images[$feature['variant_id']]);
                }
            }
        }
    }

    $groups = array();
    foreach ($features_list as $f_id => $data) {
        $groups[$data['parent_id']]['features'][$f_id] = $data;
        $groups[$data['parent_id']]['position'] = empty($data['parent_id']) ? $data['position'] : $data['gposition'];
    }

    $features_list = array();
    if (!empty($groups)) {
        $groups = fn_sort_array_by_key($groups, 'position');
        foreach ($groups as $g) {
            $g['features'] = fn_sort_array_by_key($g['features'], 'position');
            $features_list = fn_array_merge($features_list, $g['features']);
        }
    }

    unset($groups);
    foreach ($features_list as $f_id => $data) {
        unset($features_list[$f_id]['position']);
        unset($features_list[$f_id]['gposition']);
    }

    /**
     * Changes product features list data
     *
     * @param array  $features_list Product features
     * @param array  $product       Array with product data
     * @param string $display_on    Code determines zone (product/catalog page) for that features are selected
     * @param string $lang_code     2-letters language code
     */
    fn_set_hook('get_product_features_list_post', $features_list, $product, $display_on, $lang_code);

    return $features_list;
}

/**
 * Gets products features
 *
 * @param string $lang_code 2-letters language code
 * @param boolean $simple Flag determines if only feature names(true) or all properties(false) should be selected
 * @param boolean $get_hidden Flag determines if all feature fields should be selected
 * @return array Product features
 */
function fn_get_avail_product_features($lang_code = CART_LANGUAGE, $simple = false, $get_hidden = true)
{
    /**
     * Changes parameters for getting available product features
     *
     * @param string  $lang_code  2-letters language code
     * @param boolean $simple     Flag determines if only feature names(true) or all properties(false) should be selected
     * @param boolean $get_hidden Flag determines if all feature fields should be selected
     */
    fn_set_hook('get_avail_product_features_pre', $lang_code,  $simple, $get_hidden);

    $statuses = array('A');

    if ($get_hidden == false) {
        $statuses[] = 'D';
    }

    if ($simple == true) {
        $fields = db_quote("?:product_features.feature_id, ?:product_features_descriptions.description");
    } else {
        $fields = db_quote("?:product_features.*, ?:product_features_descriptions.*");
    }

    $join = db_quote("LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id AND ?:product_features_descriptions.lang_code = ?s", $lang_code);

    $condition = db_quote("?:product_features.status IN (?a) AND ?:product_features.feature_type != ?s", $statuses, ProductFeatures::GROUP);

    /**
     * Change SQL parameters before fetching available product features
     *
     * @param string  $fields     String of comma-separated SQL fields to be selected in an SQL-query
     * @param string  $join       String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string  $condition  String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string  $lang_code  2-letters language code
     * @param boolean $simple     Flag determines if only feature names(true) or all properties(false) should be selected
     * @param boolean $get_hidden Flag determines if all feature fields should be selected
     */
    fn_set_hook('get_avail_product_features_before_select', $fields, $join, $condition, $lang_code,  $simple, $get_hidden);

    if ($simple == true) {
        $result = db_get_hash_single_array("SELECT $fields FROM ?:product_features $join WHERE $condition ORDER BY ?:product_features.position", array('feature_id', 'description'));
    } else {
        $result = db_get_hash_array("SELECT $fields FROM ?:product_features $join WHERE $condition ORDER BY ?:product_features.position", 'feature_id');
    }

    /**
     * Changes  available product features data
     *
     * @param array   $result     Product features
     * @param string  $lang_code  2-letters language code
     * @param boolean $simple     Flag determines if only feature names(true) or all properties(false) should be selected
     * @param boolean $get_hidden Flag determines if all feature fields should be selected
     */
    fn_set_hook('get_avail_product_features_post', $result, $lang_code,  $simple, $get_hidden);

    return $result;
}

/**
 * Gets product feature variants
 *
 * @param array $params array with search parameters
 * @param int $items_per_page Items per page
 * @param string $lang_code 2-letters language code
 * @return array Product feature variants
 */
function fn_get_product_feature_variants($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes parameters for getting product feature variants
     *
     * @param array  $params         array with search parameters
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_feature_variants_pre', $params, $items_per_page, $lang_code);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'product_id' => 0,
        'feature_id' => 0,
        'feature_type' => '',
        'get_images' => false,
        'items_per_page' => $items_per_page,
        'selected_only' => false,
        'fetch_total_count_only' => false,
        'search_query' => null,

        // An ID or list of IDs of variants that should be loaded.
        'variant_id' => null,
    );

    $params = array_merge($default_params, $params);

    if (is_array($params['feature_id'])) {
        $fields = array(
            '?:product_feature_variant_descriptions.variant',
            '?:product_feature_variants.variant_id',
            '?:product_feature_variants.feature_id',
        );
    } else {
        $fields = array(
            '?:product_feature_variant_descriptions.*',
            '?:product_feature_variants.*',
        );
    }

    $condition = $group_by = $sorting = '';
    $feature_id = is_array($params['feature_id']) ? $params['feature_id'] : array($params['feature_id']);

    $join = db_quote(" LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s", $lang_code);
    $condition .= db_quote(" AND ?:product_feature_variants.feature_id IN (?n)", $feature_id);
    $sorting = db_quote("?:product_feature_variants.position, ?:product_feature_variant_descriptions.variant");

    if (!empty($params['variant_id'])) {
        $condition .= db_quote(' AND ?:product_feature_variants.variant_id IN (?n)', (array)$params['variant_id']);
    }

    if (!empty($params['product_id'])) {
        $fields[] = '?:product_features_values.variant_id as selected';
        $fields[] = '?:product_features.feature_type';

        if (!empty($params['selected_only'])) {
            $join .= db_quote(" INNER JOIN ?:product_features_values ON ?:product_features_values.variant_id = ?:product_feature_variants.variant_id AND ?:product_features_values.lang_code = ?s AND ?:product_features_values.product_id = ?i", $lang_code, $params['product_id']);
        } else {
            $join .= db_quote(" LEFT JOIN ?:product_features_values ON ?:product_features_values.variant_id = ?:product_feature_variants.variant_id AND ?:product_features_values.lang_code = ?s AND ?:product_features_values.product_id = ?i", $lang_code, $params['product_id']);
        }

        $join .= db_quote(" LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_feature_variants.feature_id");
        $group_by = db_quote(" GROUP BY ?:product_feature_variants.variant_id");
    }

    if (!empty($params['search_query'])) {
        $condition .= db_quote(' AND ?:product_feature_variant_descriptions.variant LIKE ?l',
            '%' . trim($params['search_query']) . '%'
        );
    }

    $limit = '';

    if ($params['fetch_total_count_only'] || !empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_feature_variants $join WHERE 1 $condition");

        if ($params['fetch_total_count_only']) {
            return $params['total_items'];
        } elseif ($params['items_per_page']) {
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
    }

    /**
     * Changes  SQL parameters for getting product feature variants
     *
     * @param array  $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $lang_code 2-letters language code
     * @param string $limit     String containing the SQL-query LIMIT clause
     */
    fn_set_hook('get_product_feature_variants', $fields, $join, $condition, $group_by, $sorting, $lang_code, $limit);

    $vars = db_get_hash_array('SELECT ' . implode(', ', $fields) . " FROM ?:product_feature_variants $join WHERE 1 $condition $group_by ORDER BY $sorting $limit", 'variant_id');

    if ($params['get_images'] == true) {
        $image_pairs = fn_get_image_pairs(array_keys($vars), 'feature_variant', 'V', true, true, $lang_code);
        foreach ($image_pairs as $variant_id => $image_pair) {
            $vars[$variant_id]['image_pair'] = array_pop($image_pair);
        }
    }

    /**
     * Changes feature variants data
     *
     * @param array  $vars      Product feature variants
     * @param array  $params    array with search params
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_product_feature_variants_post', $vars, $params, $lang_code);

    return array($vars, $params);
}

/**
 * Gets product feature variant data
 *
 * @param int $variant_id Variant identifier
 * @param string $lang_code 2-letters language code
 * @return array Variant data
 */
function fn_get_product_feature_variant($variant_id, $lang_code = CART_LANGUAGE)
{
    $fields = "*";
    $join = db_quote("LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s", $lang_code);
    $condition = db_quote("?:product_feature_variants.variant_id = ?i", $variant_id);

    /**
     * Changes SQL parameters before select product feature variant data
     *
     * @param string $fields     String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join       String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition  String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param int    $variant_id Variant identifier
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_feature_variant_before_select', $fields, $join, $condition, $variant_id, $lang_code);

    $var = db_get_row("SELECT $fields FROM ?:product_feature_variants $join WHERE $condition");

    if (empty($var)) {
        return false;
    }

    $var['image_pair'] = fn_get_image_pairs($variant_id, 'feature_variant', 'V', true, true, $lang_code);

    if (empty($var['meta_description']) && defined('AUTO_META_DESCRIPTION') && AREA != 'A') {
        $var['meta_description'] = fn_generate_meta_description($var['description']);
    }

    /**
     * Changes product feature variant data
     *
     * @param array  $var        Variant data
     * @param int    $feature_id Feature identifier
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_feature_variant_post', $var, $variant_id, $lang_code);

    return $var;
}

/**
 * Filters feature group data, leaves only settings that should be upllied to feature
 *
 * @param array $group_data Group data
 * @return array Filtered group data
 */
function fn_filter_feature_group_data($group_data)
{
    $display_settings = array('display_on_product', 'display_on_catalog', 'display_on_header');
    foreach ($display_settings as $setting) {
        if ($group_data[$setting] != 'Y') {
            unset($group_data[$setting]);
        }
    }

    return $group_data;
}

/**
 * Updates product feature
 *
 * @param array $feature_data Feature data
 * @param int $feature_id Feature identifier
 * @param string $lang_code 2-letters language code
 * @return int/boolean Feature identifier if product feature was updated, false otherwise
 */
function fn_update_product_feature($feature_data, $feature_id, $lang_code = DESCR_SL)
{
    /**
     * Changes before product feature updating
     *
     * @param array  $feature_data Feature data
     * @param int    $feature_id   Feature identifier
     * @param string $lang_code    2-letters language code
     */
    fn_set_hook('update_product_feature_pre', $feature_data, $feature_id, $lang_code);

    SecurityHelper::sanitizeObjectData('product_feature', $feature_data);

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        if (!empty($feature_id) && $feature_id != NEW_FEATURE_GROUP_ID) {
            if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                fn_company_access_denied_notification();

                return false;
            }
            unset($feature_data['company_id']);
        }
    }

    $deleted_variants = array();
    $old_feature_data = array();

    // If this feature belongs to the group, get categories assignment from this group
    if (!empty($feature_data['parent_id'])) {
        $gdata = db_get_row("SELECT categories_path, display_on_product, display_on_catalog, display_on_header FROM ?:product_features WHERE feature_id = ?i", $feature_data['parent_id']);
        if (!empty($gdata)) {
            $gdata = fn_filter_feature_group_data($gdata);
            $feature_data = fn_array_merge($feature_data, $gdata);
        }
    }

    if (!intval($feature_id)) { // check for intval as we use "0G" for new group
        $feature_data['feature_id'] = $feature_id = db_query("INSERT INTO ?:product_features ?e", $feature_data);
        foreach (fn_get_translation_languages() as $feature_data['lang_code'] => $_d) {
            db_query("INSERT INTO ?:product_features_descriptions ?e", $feature_data);
        }
    } else {
        $old_feature_data = fn_get_product_feature_data($feature_id, false, false, DESCR_SL);

        if (!isset($feature_data['categories_path']) && empty($old_feature_data['categories_path'])) {
            $feature_data['categories_path'] = '';
        }

        $arow = db_query("UPDATE ?:product_features SET ?u WHERE feature_id = ?i", $feature_data, $feature_id);
        db_query('UPDATE ?:product_features_descriptions SET ?u WHERE feature_id = ?i AND lang_code = ?s', $feature_data, $feature_id, $lang_code);

        if (!$old_feature_data) {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('feature'))),'','404');
            $feature_id = false;
        }
    }

    if ($feature_id) {

        // If this feature is group, set its categories to all children
        if ($feature_data['feature_type'] == ProductFeatures::GROUP) {
            $u = array(
                'categories_path' => !empty($feature_data['categories_path']) ? $feature_data['categories_path'] : '',
                'display_on_product' => !empty($feature_data['display_on_product']) ? $feature_data['display_on_product'] : '',
                'display_on_catalog' => !empty($feature_data['display_on_catalog']) ? $feature_data['display_on_catalog'] : '',
                'display_on_header' => !empty($feature_data['display_on_header']) ? $feature_data['display_on_header'] : '',
            );
            $u = fn_filter_feature_group_data($u);
            db_query("UPDATE ?:product_features SET ?u WHERE parent_id = ?i", $u, $feature_id);
        }

        // Delete variants for simple features
        $old_categories = $old_feature_data ? fn_explode(',', $old_feature_data['categories_path']) : array();

        // Get sub-categories for OLD categories
        if (!empty($old_categories)) {
            $_condition = array();
            foreach ($old_categories as $category_id) {
                $_condition[] = db_quote('id_path LIKE ?l OR id_path LIKE ?l', $category_id . '/%', '%/' . $category_id . '/%');
            }

            $sub_cat_ids = db_get_fields('SELECT category_id FROM ?:categories WHERE ' . implode(' OR ', $_condition));
            $old_categories = array_merge($old_categories, $sub_cat_ids);
        }

        $new_categories = isset($feature_data['categories_path']) ? fn_explode(',', $feature_data['categories_path']) : array();

        // Get sub-categories for NEW categories
        if (!empty($new_categories)) {
            $_condition = array();
            foreach ($new_categories as $category_id) {
                $_condition[] = db_quote('id_path LIKE ?l OR id_path LIKE ?l', $category_id . '/%', '%/' . $category_id . '/%');
            }

            $sub_cat_ids = db_get_fields('SELECT category_id FROM ?:categories WHERE ' . implode(' OR ', $_condition));
            $new_categories = array_merge($new_categories, $sub_cat_ids);
        }

        $checked_types = ProductFeatures::getSelectable();

        if (($old_feature_data && $feature_data['feature_type'] != $old_feature_data['feature_type'])) {
            if (strpos($checked_types, $feature_data['feature_type']) === false) {
                fn_delete_product_feature_variants($feature_id);
            }
        }

        // Remove features values/variants if we changed categories list
        sort($old_categories);
        sort($new_categories);

        if (!fn_is_empty($new_categories)
            && (fn_is_empty($old_categories) || $old_categories != $new_categories)
        ) {
            db_query('DELETE FROM ?:product_features_values WHERE feature_id = ?i AND product_id NOT IN (SELECT product_id FROM ?:products_categories WHERE link_type = ?s AND category_id IN (?n))', $feature_id, 'M', $new_categories);
        }

        if (strpos($checked_types, $feature_data['feature_type']) !== false) {
            fn_update_product_feature_variants($feature_id, $feature_data, $lang_code);
        }

        // Disable related filters if feature status not active
        if (
            $feature_data['feature_type'] != ProductFeatures::GROUP
            && isset($feature_data['status'])
            && $feature_data['status'] != 'A'
        ) {
            fn_disable_product_feature_filters($feature_id);
        }

        /**
         * Adds additional actions after product feature updating
         *
         * @param array  $feature_data     Feature data
         * @param int    $feature_id       Feature identifier
         * @param array  $deleted_variants Deleted product feature variants identifiers
         * @param string $lang_code        2-letters language code
         */
        fn_set_hook('update_product_feature_post', $feature_data, $feature_id, $deleted_variants, $lang_code);
    }

    return $feature_id;
}

/**
 * Updates product feature variants
 *
 * @param int $feature_id Feature identifier
 * @param array $feature_data Feature data
 * @param string $lang_code 2-letters language code
 *
 * @return array $variant_ids Feature variants identifier
 */
function fn_update_product_feature_variants($feature_id, &$feature_data, $lang_code = DESCR_SL)
{
    $variant_ids = array();

    if (!empty($feature_data['variants'])) {

        foreach ($feature_data['variants'] as $key => $variant) {
            $variant_id = fn_update_product_feature_variant($feature_id, $feature_data['feature_type'], $variant, $lang_code);

            $variant_ids[$key] = $variant_id;
            $feature_data['variants'][$key]['variant_id'] = $variant_id; // for addons
        }

        if (!empty($variant_ids)) {
            fn_attach_image_pairs('variant_image', 'feature_variant', 0, $lang_code, $variant_ids);
        }

        if (!empty($feature_data['original_var_ids'])) {
            $original_variant_ids = explode(',', $feature_data['original_var_ids']);
            $deleted_variants = array_diff($original_variant_ids, $variant_ids);

            fn_delete_product_feature_variants(0, $deleted_variants);
        }
    }

}

/**
 * Updates product feature variant
 *
 * @param int       $feature_id     Feature identifier
 * @param array     $feature_type   Feature type
 * @param array     $variant        Feature variant data
 * @param string    $lang_code      2-letters language code
 *
 * @return int $variant_id Feature variant identifier
 */
function fn_update_product_feature_variant($feature_id, $feature_type, $variant, $lang_code = DESCR_SL)
{
    if (empty($variant['variant']) && (!isset($variant['variant']) || $variant['variant'] !== '0')) {
        return false;
    }

    $variant['feature_id'] = $feature_id;

    /**
     * Executes at the beginning of the function, allowing you to modify the arguments passed to the function.
     *
     * @param int       $feature_id     Feature identifier
     * @param array     $feature_type   Feature type
     * @param array     $variant        Feature variant data
     * @param string    $lang_code      2-letters language code
     */
    fn_set_hook('update_product_feature_variant_pre', $feature_id, $feature_type, $variant, $lang_code);

    if (isset($variant['variant_id'])) {
        $variant_id = db_get_field('SELECT variant_id FROM ?:product_feature_variants WHERE variant_id = ?i', $variant['variant_id']);
        unset($variant['variant_id']);
    }

    if (empty($variant_id)) {
        $join = db_quote('INNER JOIN ?:product_feature_variants fv ON fv.variant_id = fvd.variant_id');
        $variant_id = db_get_field("SELECT fvd.variant_id FROM ?:product_feature_variant_descriptions AS fvd $join WHERE variant = ?s AND feature_id = ?i", $variant['variant'], $feature_id);
    }

    /**
     * Executes after identifier of the variant was checked.
     *
     * @param int       $feature_id     Feature identifier
     * @param array     $feature_type   Feature type
     * @param array     $variant        Feature variant data
     * @param string    $lang_code      2-letters language code
     * @param int       $variant_id     Variant identifier
     */
    fn_set_hook('update_product_feature_variant', $feature_id, $feature_type, $variant, $lang_code, $variant_id);

    if (empty($variant_id)) {
        $variant_id = fn_add_feature_variant($feature_id, $variant);
    } else {
        db_query("UPDATE ?:product_feature_variants SET ?u WHERE variant_id = ?i", $variant, $variant_id);
        db_query("UPDATE ?:product_feature_variant_descriptions SET ?u WHERE variant_id = ?i AND lang_code = ?s", $variant, $variant_id, $lang_code);
    }

    if ($feature_type == ProductFeatures::NUMBER_SELECTBOX) {
        db_query('UPDATE ?:product_features_values SET ?u WHERE variant_id = ?i AND lang_code = ?s', array('value_int' => $variant['variant']), $variant_id, $lang_code);
    }

    /**
     * Executes after variant was updated/inserted.
     *
     * @param int       $feature_id     Feature identifier
     * @param array     $feature_type   Feature type
     * @param array     $variant        Feature variant data
     * @param string    $lang_code      2-letters language code
     * @param int       $variant_id     Variant identifier
     */
    fn_set_hook('update_product_feature_variant_post', $feature_id, $feature_type, $variant, $lang_code, $variant_id);

    return $variant_id;
}

/**
 * Add product feature variant
 *
 * @param int       $feature_id     Feature identifier
 * @param array     $variant        Feature variant data
 *
 * @return int $variant_id Feature variant identifier
 */
function fn_add_feature_variant($feature_id, $variant)
{
    /**
     * Changes variant data before adding
     *
     * @param int   $feature_id Feature identifier
     * @param array $variant    Variant data
     */
    fn_set_hook('add_feature_variant_pre', $feature_id, $variant);

    if (empty($variant['variant']) && (!isset($variant['variant']) || $variant['variant'] !== '0')) {
        return false;
    }

    $variant['feature_id'] = $feature_id;
    $variant['variant_id'] = db_query("INSERT INTO ?:product_feature_variants ?e", $variant);

    foreach (Languages::getAll() as $variant['lang_code'] => $_v) {
        db_query("INSERT INTO ?:product_feature_variant_descriptions ?e", $variant);
    }

    /**
     * Adds additional actions before category parent updating
     *
     * @param int   $feature_id Feature identifier
     * @param array $variant    Variant data
     */
    fn_set_hook('add_feature_variant_post', $feature_id, $variant);

    return $variant['variant_id'];
}

/**
 * Removes product feature
 *
 * @param int $feature_id Feature identifier
 * @return boolean Always true
 */
function fn_delete_feature($feature_id)
{
    $feature_deleted = true;

    if (fn_allowed_for('ULTIMATE')) {
        if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
            fn_company_access_denied_notification();

            return false;
        }
    }

    /**
     * Adds additional actions before product feature deleting
     *
     * @param int $feature_id Feature identifier
     */
    fn_set_hook('delete_feature_pre', $feature_id);

    $feature_type = db_get_field("SELECT feature_type FROM ?:product_features WHERE feature_id = ?i", $feature_id);

    fn_set_hook('delete_product_feature', $feature_id, $feature_type);

    if ($feature_type == ProductFeatures::GROUP) {
        $fids = db_get_fields("SELECT feature_id FROM ?:product_features WHERE parent_id = ?i", $feature_id);
        if (!empty($fids)) {
            foreach ($fids as $fid) {
                fn_delete_feature($fid);
            }
        }
    }

    $affected_rows = db_query("DELETE FROM ?:product_features WHERE feature_id = ?i", $feature_id);
    db_query("DELETE FROM ?:product_features_descriptions WHERE feature_id = ?i", $feature_id);

    if ($affected_rows == 0) {
        fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('feature'))),'','404');
        $feature_deleted = false;
    }

    $variant_ids = fn_delete_product_feature_variants($feature_id);

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        $filter_ids = db_get_fields("SELECT filter_id FROM ?:product_filters WHERE feature_id = ?i", $feature_id);
        foreach ($filter_ids as $_filter_id) {
            fn_delete_product_filter($_filter_id);
        }
    }

    /**
     * Adds additional actions after product feature deleting
     *
     * @param int   $feature_id  Deleted feature identifier
     * @param array $variant_ids Deleted feature variants
     */
    fn_set_hook('delete_feature_post', $feature_id, $variant_ids);

    return $feature_deleted;
}

/**
 * Removes feature variants
 *
 * @param int $feature_id Feature identifier
 * @param array $variant_ids Variants identifier
 * @return array $variant_ids Deleted feature variants
 */
function fn_delete_product_feature_variants($feature_id = 0, $variant_ids = array())
{
    if (!empty($feature_id)) {
        $variant_ids = db_get_fields("SELECT variant_id FROM ?:product_feature_variants WHERE feature_id = ?i", $feature_id);
        db_query("DELETE FROM ?:product_features_values WHERE feature_id = ?i", $feature_id);
    }

    if (!empty($variant_ids)) {
        db_query("DELETE FROM ?:product_features_values WHERE variant_id IN (?n)", $variant_ids);
        db_query("DELETE FROM ?:product_feature_variants WHERE variant_id IN (?n)", $variant_ids);
        db_query("DELETE FROM ?:product_feature_variant_descriptions WHERE variant_id IN (?n)", $variant_ids);
        foreach ($variant_ids as $variant_id) {
            fn_delete_image_pairs($variant_id, 'feature_variant');
        }
    }

    /**
     * Adds additional actions after product feature variants deleting
     *
     * @param int   $feature_id  Deleted feature identifier
     * @param array $variant_ids Deleted feature variants
     */
    fn_set_hook('delete_product_feature_variants_post', $feature_id, $variant_ids);

    return $variant_ids;
}

/**
 * Gets product filter name
 *
 * @param array $filter_id Filter identifier
 * @param string $lang_code 2 letters language code
 * @return string|bool Filter name on success, false otherwise
 */
function fn_get_product_filter_name($filter_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($filter_id)) {
        if (is_array($filter_id)) {
            return db_get_hash_single_array("SELECT filter_id, filter FROM ?:product_filter_descriptions WHERE filter_id IN (?n) AND lang_code = ?s", array('filter_id', 'filter'), $filter_id, $lang_code);
        } else {
            return db_get_field("SELECT filter FROM ?:product_filter_descriptions WHERE filter_id = ?i AND lang_code = ?s", $filter_id, $lang_code);
        }
    }

    return false;
}

/**
 * Gets product filters by search params
 *
 * @param array $params Products filter search params
 * @param int $items_per_page Items per page
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return array Product filters
 */
function fn_get_product_filters($params = array(), $items_per_page = 0, $lang_code = DESCR_SL)
{
    /**
     * Changes product filters search params
     *
     * @param array  $params         Products filter search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_filters_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('product_filters', $params);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $condition = $group = '';

    if (!empty($params['item_ids'])) {
        $params['filter_id'] = is_array($params['item_ids']) ? $params['item_ids'] : fn_explode(',', $params['item_ids']);
    }

    if (!empty($params['filter_id'])) {
        $condition .= db_quote(" AND ?:product_filters.filter_id IN (?n)", (array) $params['filter_id']);
    }

    if (isset($params['filter_name']) && fn_string_not_empty($params['filter_name'])) {
        $condition .= db_quote(" AND ?:product_filter_descriptions.filter LIKE ?l", "%".trim($params['filter_name'])."%");
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:product_filters.status = ?s", $params['status']);
    }

    if (!empty($params['feature_type'])) {
        $condition .= db_quote(" AND ?:product_features.feature_type IN (?a)", $params['feature_type']);
    }

    if (isset($params['feature_name']) && fn_string_not_empty($params['feature_name'])) {
        $condition .= db_quote(" AND ?:product_features_descriptions.description LIKE ?l", "%".trim($params['feature_name'])."%");
    }

    if (!empty($params['category_ids'])) {
        $c_ids = is_array($params['category_ids']) ? $params['category_ids'] : fn_explode(',', $params['category_ids']);
        $find_set = array(
            " ?:product_filters.categories_path = '' "
        );
        foreach ($c_ids as $k => $v) {
            $find_set[] = db_quote(" FIND_IN_SET(?i, ?:product_filters.categories_path) ", $v);
        }
        $find_in_set = db_quote(" AND (?p)", implode('OR', $find_set));
        $condition .= $find_in_set;
    }

    if (fn_allowed_for('ULTIMATE')) {
        $condition .= fn_get_company_condition('?:product_filters.company_id');
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_filters LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.lang_code = ?s AND ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_filters.feature_id AND ?:product_features_descriptions.lang_code = ?s LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id WHERE 1 ?p", $lang_code, $lang_code, $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $fields = "";
    if (!empty($params['short'])) {
        $fields .= db_quote("?:product_filters.filter_id, ?:product_filters.feature_id, ?:product_filters.field_type, ?:product_filters.status, ");
        if (fn_allowed_for('ULTIMATE')) {
            $fields .= db_quote("?:product_filters.company_id, ");
        }
    } else {
        $fields .= db_quote("?:product_filters.*, ?:product_features_descriptions.description as feature, ");
    }

    $fields .= db_quote("?:product_filter_descriptions.filter, ?:product_features.feature_type, ?:product_features.parent_id, ?:product_features_descriptions.prefix, ?:product_features_descriptions.suffix");
    $join = db_quote("LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.lang_code = ?s AND ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_filters.feature_id AND ?:product_features_descriptions.lang_code = ?s LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id", $lang_code, $lang_code);
    $sorting = db_quote("?:product_filters.position, ?:product_filter_descriptions.filter");
    $group_by = db_quote("GROUP BY ?:product_filters.filter_id");

    /**
     * Changes SQL parameters for product filters select
     *
     * @param string $fields    String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $limit     String containing the SQL-query LIMIT clause
     * @param array  $params    Products filter search params
     * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_filters_before_select', $fields, $join, $condition, $group_by, $sorting, $limit, $params, $lang_code);

    $filters = db_get_hash_array("SELECT $fields FROM ?:product_filters $join WHERE 1 ?p $group_by ORDER BY $sorting $limit", 'filter_id', $condition);

    if (!empty($filters)) {
        $fields = fn_get_product_filter_fields();

        // Get feature group if exist
        $parent_ids = array();
        foreach ($filters as $k => $v) {
            if (!empty($v['parent_id'])) {
                $parent_ids[] = $v['parent_id'];
            }
        }
        $groups = db_get_hash_array("SELECT feature_id, description FROM ?:product_features_descriptions WHERE feature_id IN (?n) AND lang_code = ?s", 'feature_id', $parent_ids, $lang_code);

        foreach ($filters as $k => $filter) {

            if (!empty($filter['parent_id']) && !empty($groups[$filter['parent_id']])) {
                $filters[$k]['feature_group'] = $groups[$filter['parent_id']]['description'];
            }

            if (!empty($filter['field_type'])) {
                $filters[$k]['feature'] = __($fields[$filter['field_type']]['description']);
            }
            if (empty($filter['feature_id'])) {
                $filters[$k]['condition_type'] = $fields[$filter['field_type']]['condition_type'];
            }

            if (!empty($params['get_descriptions'])) {
                $d = array();
                $filters[$k]['filter_description'] = __('filter_by') . ': <span>' . $filters[$k]['feature'] . (!empty($filters[$k]['feature_group']) ? ' (' . $filters[$k]['feature_group'] . ' )' : '') . '</span>';
                $d = fn_array_merge($d, fn_get_categories_list($filter['categories_path'], $lang_code), false);
                $filters[$k]['filter_description'] .= ' | ' . __('display_on') . ': <span>' . implode(', ', $d) . '</span>';
            }

            $_ids[$filter['filter_id']] = $filter['feature_type'] != ProductFeatures::NUMBER_SELECTBOX ? $filter['feature_id'] : null;
        }

        if (!empty($params['get_variants']) && !empty($_ids)) {

            list($variants) = fn_get_product_feature_variants(array(
                'feature_id' => array_values($_ids)
            ));

            $_ids_revert = array();
            foreach ($_ids as $filter_id => $feature_id) {
                if (!empty($feature_id)) {
                    $_ids_revert[$feature_id][] = $filter_id;
                }
            }

            foreach ($variants as $variant_id => $variant) {
                if (!empty($_ids_revert[$variant['feature_id']])) {
                    foreach ($_ids_revert[$variant['feature_id']] as $filter_id) {
                        if (!empty($params['short'])) {
                            $filters[$filter_id]['variants'][$variant_id] = array('variant_id' => $variant['variant_id'], 'variant' => $variant['variant']);
                        } else {
                            $filters[$filter_id]['variants'][$variant_id] = $variant;
                        }
                    }
                }
                unset($variants[$variant_id]);
            }

            unset($variants);
        }
    }

    /**
     * Changes product filters data
     *
     * @param array  $filters   Product filters
     * @param array  $params    Products filter search params
     * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_filters_post', $filters, $params, $lang_code);

    return array($filters, $params);
}

function fn_delete_product_filter($filter_id)
{
    /**
     * Adds additional actions before deleting product filter
     *
     * @param int $filter_id Filter identifier
     */
    fn_set_hook('delete_product_filter_pre', $filter_id);

    db_query("DELETE FROM ?:product_filters WHERE filter_id = ?i", $filter_id);
    db_query("DELETE FROM ?:product_filter_descriptions WHERE filter_id = ?i", $filter_id);

    /**
     * Adds additional actions after deleting product filter
     *
     * @param int $filter_id Filter identifier
     */
    fn_set_hook('delete_product_filter_post', $filter_id);

    return true;
}


//
//Gets all combinations of options stored in exceptions
//
function fn_get_product_exceptions($product_id, $short_list = false)
{
    if (fn_allowed_for('ULTIMATE:FREE')) {
        return array();
    }

    /**
     * Changes params before getting product exceptions
     *
     * @param int     $product_id Product identifier
     * @param boolean $short_list Flag determines if exceptions list should be returned in short format
     */
    fn_set_hook('get_product_exceptions_pre', $product_id, $short_list);

    $exceptions = db_get_array("SELECT * FROM ?:product_options_exceptions WHERE product_id = ?i ORDER BY exception_id", $product_id);

    foreach ($exceptions as $k => $v) {
        $exceptions[$k]['combination'] = unserialize($v['combination']);

        if ($short_list) {
            $exceptions[$k] = $exceptions[$k]['combination'];
        }
    }

    /**
     * Changes product exceptions data
     *
     * @param int     $product_id Product identifier
     * @param array   $exceptions Exceptions data
     * @param boolean $short_list Flag determines if exceptions list should be returned in short format
     */
    fn_set_hook('get_product_exceptions_post', $product_id, $exceptions, $short_list);

    return $exceptions;
}

//
// Updates options exceptions using product_id;
//
function fn_update_exceptions($product_id)
{
    $result = false;

    if ($product_id) {

        $exceptions = fn_get_product_exceptions($product_id);

        /**
         * Adds additional actions before product exceptions update
         *
         * @param int $product_id Product identifier
         * @param array $exceptions
         */
        fn_set_hook('update_exceptions_pre', $product_id, $exceptions);

        if (!empty($exceptions)) {
            db_query("DELETE FROM ?:product_options_exceptions WHERE product_id = ?i", $product_id);
            foreach ($exceptions as $k => $v) {
                $_options_order = db_get_fields("SELECT a.option_id FROM ?:product_options as a LEFT JOIN ?:product_global_option_links as b ON a.option_id = b.option_id WHERE a.product_id = ?i OR b.product_id = ?i ORDER BY position", $product_id, $product_id);

                if (empty($_options_order)) {
                    return false;
                }
                $combination  = array();

                foreach ($_options_order as $option) {
                    if (!empty($v['combination'][$option])) {
                        $combination[$option] = $v['combination'][$option];
                    } else {
                        $combination[$option] = OPTION_EXCEPTION_VARIANT_ANY;
                    }
                }

                $_data = array(
                    'product_id' => $product_id,
                    'exception_id' => $v['exception_id'],
                    'combination' => serialize($combination),
                );
                db_query("INSERT INTO ?:product_options_exceptions ?e", $_data);

            }

            $result = true;
        }

        /**
         * Adds additional actions after product exceptions update
         *
         * @param int $product_id Product identifier
         * @param array $exceptions
         */
        fn_set_hook('update_exceptions_post', $product_id, $exceptions);
    }

    return $result;
}

/**
 * Gets product options exception data
 * @param int $exception_id Exception ID
 * @return array Exception data
 */
function fn_get_product_exception_data($exception_id)
{
    if (fn_allowed_for('ULTIMATE:FREE')) {
        return array();
    }

    /**
     * Changes params before getting product exception data
     *
     * @param int $exception_id Exception ID
     */
    fn_set_hook('get_product_exception_data_pre', $product_id);

    $exception_data = db_get_row('SELECT * FROM ?:product_options_exceptions WHERE exception_id = ?i', $exception_id);
    $exception_data['combination'] = unserialize($exception_data['combination']);

    /**
     * Changes product exception data
     *
     * @param int   $exception_id   Exception ID
     * @param array $exception_data Exception data
     */
    fn_set_hook('get_product_exception_data_pre', $product_id, $exception_data);

    return $exception_data;
}

//
// Returns exception_id if such combination already exists
//
function fn_check_combination($combinations, $product_id)
{
    /**
     * Changes params before checking combination
     *
     * @param array $combinations Combinations data
     * @param int   $product_id   Product identifier
     */
    fn_set_hook('check_combination_pre', $combinations, $product_id);

    $exceptions = fn_get_product_exceptions($product_id);

    $exception_id = 0;

    if (!empty($exceptions)) {
        foreach ($exceptions as $k => $v) {
            $temp = $v['combination'];
            foreach ($combinations as $key => $value) {
                if ((in_array($value, $temp)) && ($temp[$key] == $value)) {
                    unset($temp[$key]);
                }
            }
            if (empty($temp)) {
                $exception_id = $v['exception_id'];
                break;
            }
        }
    }

    /**
     * Changes params after checking combination
     *
     * @param boolean $exception_id Flag determines if combination exists
     * @param array   $combinations Combinations data
     * @param int     $product_id   Product identifier
     */
    fn_set_hook('check_combination_post', $exception_id, $combinations, $product_id);

    return $exception_id;
}

//
// Updates options exceptions using product_id;
//
function fn_recalculate_exceptions($product_id)
{
    $result = false;
    if ($product_id) {
        $exceptions = fn_get_product_exceptions($product_id);
        /**
         * Adds additional actions before product exceptions update
         *
         * @param int $product_id Product identifier
         * @param array $exceptions
         */
        fn_set_hook('update_exceptions_pre', $product_id, $exceptions);
        if (!empty($exceptions)) {
            db_query("DELETE FROM ?:product_options_exceptions WHERE product_id = ?i", $product_id);
            foreach ($exceptions as $k => $v) {
                $_options_order = db_get_fields("SELECT a.option_id FROM ?:product_options as a LEFT JOIN ?:product_global_option_links as b ON a.option_id = b.option_id WHERE a.product_id = ?i OR b.product_id = ?i ORDER BY position", $product_id, $product_id);
                if (empty($_options_order)) {
                    return false;
                }
                $combination  = array();
                foreach ($_options_order as $option) {
                    if (!empty($v['combination'][$option])) {
                        $combination[$option] = $v['combination'][$option];
                    } else {
                        $combination[$option] = OPTION_EXCEPTION_VARIANT_ANY;
                    }
                }
                $_data = array(
                    'product_id' => $product_id,
                    'exception_id' => $v['exception_id'],
                    'combination' => serialize($combination),
                );
                db_query("INSERT INTO ?:product_options_exceptions ?e", $_data);
            }
            $result = true;
        }
        /**
         * Adds additional actions after product exceptions update
         *
         * @param int $product_id Product identifier
         * @param array $exceptions
         */
        fn_set_hook('update_exceptions_post', $product_id, $exceptions);
    }

    return $result;
}

/**
 * Updates exception data
 *
 * @param array $exception_data Exception data
 * @param int $exception_id Exception ID
 * @return bool true if updated
 */
function fn_update_exception($exception_data, $exception_id = 0)
{
    /**
     * Changes params before updating exception
     *
     * @param array $exception_data Exception data
     * @param int   $exception_id   Exception ID
     */
    fn_set_hook('update_exception_pre', $exception_data, $exception_id);

    if (empty($exception_id)) {
        $exception_id = fn_check_combination($exception_data['combination'], $exception_data['product_id']);

        if (empty($exception_id)) {
            $exception_id = db_query('INSERT INTO ?:product_options_exceptions ?e', array(
                'product_id' => $exception_data['product_id'],
                'combination' => serialize($exception_data['combination']),
            ));
        } else {
            fn_set_notification('W', __('warning'), __('exception_exist'), 'K', 'exception_exist');
        }
    } else {
        $exception_data['combination'] = serialize($exception_data['combination']);
        db_query("UPDATE ?:product_options_exceptions SET ?u WHERE exception_id = ?i", $exception_data, $exception_id);
    }

    /**
     * Adds additional actions afrer updating exception
     *
     * @param array $exception_data Exception data
     * @param int   $exception_id   Exception ID
     */
    fn_set_hook('update_exception_post', $exception_data, $exception_id);

    return $exception_id;
}

//
// Clone exceptions
//
function fn_clone_options_exceptions(&$exceptions, $old_opt_id, $old_var_id, $new_opt_id, $new_var_id)
{
    /**
     * Adds additional actions before options exceptions clone
     *
     * @param array $exceptions Exceptions array
     * @param int   $old_opt_id Old option identifier
     * @param int   $old_var_id Old variant identifier
     * @param int   $new_opt_id New option identifier
     * @param int   $new_var_id New variant identifier
     */
    fn_set_hook('clone_options_exceptions_pre', $exceptions, $old_opt_id, $old_var_id, $new_opt_id, $new_var_id);

    foreach ($exceptions as $key => $value) {
        foreach ($value['combination'] as $option => $variant) {
            if ($option == $old_opt_id) {
                $exceptions[$key]['combination'][$new_opt_id] = $variant;
                unset($exceptions[$key]['combination'][$option]);

                if ($variant == $old_var_id) {
                    $exceptions[$key]['combination'][$new_opt_id] = $new_var_id;
                }
            }
            if ($variant == $old_var_id) {
                $exceptions[$key]['combination'][$option] = $new_var_id;
            }
        }
    }

    /**
     * Adds additional actions after options exceptions clone
     *
     * @param array $exceptions Exceptions array
     * @param int   $old_opt_id Old option identifier
     * @param int   $old_var_id Old variant identifier
     * @param int   $new_opt_id New option identifier
     * @param int   $new_var_id New variant identifier
     */
    fn_set_hook('clone_options_exceptions_post', $exceptions, $old_opt_id, $old_var_id, $new_opt_id, $new_var_id);
}

/**
 * Deletes options exception
 *
 * @param int $exception_id Exception ID
 * @return bool true
 */
function fn_delete_exception($exception_id)
{
    /**
     * Makes additional actions before deleting exception
     *
     * @param int $exception_id Exception ID
     */
    fn_set_hook('delete_exception_pre', $combination_hash);

    db_query("DELETE FROM ?:product_options_exceptions WHERE exception_id = ?i", $exception_id);

    return true;
}

/**
 * This function clones options to product from a product or from a global option
 *
 * @param int $from_product_id Identifier of product from that options are copied
 * @param int $to_product_id Identifier of product to that options are copied
 * @param type $from_global_option_id Identifier of the global option or false (if options are copied from product)
 */
function fn_clone_product_options($from_product_id, $to_product_id, $from_global_option_id = false)
{
    /**
     * Adds additional actions before poduct options clone
     *
     * @param int         $from_product_id       Identifier of product from that options are copied
     * @param int         $to_product_id         Identifier of product to that options are copied
     * @param int/boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
     */
    fn_set_hook('clone_product_options_pre', $from_product_id, $to_product_id, $from_global_option_id);
    // Get all product options assigned to the product
    $id_req = (empty($from_global_option_id)) ? db_quote('product_id = ?i', $from_product_id) : db_quote('option_id = ?i', $from_global_option_id);
    $data = db_get_array("SELECT * FROM ?:product_options WHERE $id_req");
    $linked  = db_get_field("SELECT COUNT(option_id) FROM ?:product_global_option_links WHERE product_id = ?i", $from_product_id);
    if (!empty($data) || !empty($linked)) {
        // Get all exceptions for the product
        if (!empty($from_product_id)) {
            if (!fn_allowed_for('ULTIMATE:FREE')) {
                $exceptions = fn_get_product_exceptions($from_product_id);
            }
            $inventory = db_get_field("SELECT COUNT(*) FROM ?:product_options_inventory WHERE product_id = ?i", $from_product_id);
        }
        // Fill array of options for linked global options options
        $change_options = array();
        $change_variants = array();
        // If global option are linked then ids will be the same
        $change_options = db_get_hash_single_array("SELECT option_id FROM ?:product_global_option_links WHERE product_id = ?i", array('option_id', 'option_id'), $from_product_id);
        if (!empty($change_options)) {
            foreach ($change_options as $value) {
                $change_variants = fn_array_merge(db_get_hash_single_array("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i", array('variant_id', 'variant_id'), $value), $change_variants, true);
            }
        }
        foreach ($data as $option_data) {
            // Clone main data
            $option_id = $option_data['option_id'];
            $option_data['product_id'] = $to_product_id;
            if (fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR')) {
                $product_company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $to_product_id);
                $option_data['company_id'] = Registry::ifGet('runtime.company_id', $product_company_id);
            } else {
                $option_data['company_id'] = Registry::get('runtime.company_id');
            }
            unset($option_data['option_id']);
            $new_option_id = db_query("INSERT INTO ?:product_options ?e", $option_data);
            if (fn_allowed_for('ULTIMATE')) {
                fn_ult_share_product_option($new_option_id, $to_product_id);
            }
            // Clone descriptions
            $_data = db_get_array("SELECT * FROM ?:product_options_descriptions WHERE option_id = ?i", $option_id);
            foreach ($_data as $option_description) {
                $option_description['option_id'] = $new_option_id;
                db_query("INSERT INTO ?:product_options_descriptions ?e", $option_description);
            }
            $change_options[$option_id] = $new_option_id;
            // Clone variants if exists
            if ($option_data['option_type'] == 'S' || $option_data['option_type'] == 'R' || $option_data['option_type'] == 'C') {
                $_data = db_get_array("SELECT * FROM ?:product_option_variants WHERE option_id = ?i", $option_id);
                foreach ($_data as $option_description) {
                    $variant_id = $option_description['variant_id'];
                    $option_description['option_id'] = $new_option_id;
                    unset($option_description['variant_id']);
                    $new_variant_id = db_query("INSERT INTO ?:product_option_variants ?e", $option_description);
                    if (!fn_allowed_for('ULTIMATE:FREE')) {
                        // Clone Exceptions
                        if (!empty($exceptions)) {
                            fn_clone_options_exceptions($exceptions, $option_id, $variant_id, $new_option_id, $new_variant_id);
                        }
                    }
                    $change_variants[$variant_id] = $new_variant_id;
                    // Clone descriptions
                    $__data = db_get_array("SELECT * FROM ?:product_option_variants_descriptions WHERE variant_id = ?i", $variant_id);
                    foreach ($__data as $option_variant_description) {
                        $option_variant_description['variant_id'] = $new_variant_id;
                        db_query("INSERT INTO ?:product_option_variants_descriptions ?e", $option_variant_description);
                    }
                    // Clone variant images
                    fn_clone_image_pairs($new_variant_id, $variant_id, 'variant_image');
                }
                unset($_data, $__data);
            }
            /**
             * Adds additional actions after cloning each product option
             *
             * @param int $from_product_id Identifier of product from that options are copied
             * @param int $to_product_id Identifier of product to that options are copied
             * @param int/boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
             * @param array $option_data Product option data
             * @param array $change_options Links old options to the new ones via ids
             * @param array $change_variant Links old variants to the new ones via ids
             */
            fn_set_hook('clone_product_option_post', $from_product_id, $to_product_id, $from_global_option_id, $option_data, $change_options, $change_variants);
        }
        // Clone Inventory
        if (!empty($inventory)) {
            fn_clone_options_inventory($from_product_id, $to_product_id, $change_options, $change_variants);
        }
        if (!fn_allowed_for('ULTIMATE:FREE')) {
            if (!empty($exceptions)) {
                foreach ($exceptions as $k => $option_data) {
                    $_data = array(
                        'product_id' => $to_product_id,
                        'combination' => serialize($option_data['combination']),
                    );
                    db_query("INSERT INTO ?:product_options_exceptions ?e", $_data);
                }
            }
        }
    }
    /**
     * Adds additional actions after product options clone
     *
     * @param int         $from_product_id       Identifier of product from that options are copied
     * @param int         $to_product_id         Identifier of product to that options are copied
     * @param int/boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
     */
    fn_set_hook('clone_product_options_post', $from_product_id, $to_product_id, $from_global_option_id);
}

//
// Clone Inventory
//
function fn_clone_options_inventory($from_product_id, $to_product_id, $options, $variants)
{
    /**
     * Adds additional actions before options inventory clone
     *
     * @param int   $from_product_id Identifier of product from that options are copied
     * @param int   $to_product_id   Identifier of product to that options are copied
     * @param array $options         Array with options identifiers where old identifiers points to new identifier
     * @param array $variants        Array with variant identifiers where old identifiers points to new identifier
     */
    fn_set_hook('clone_options_inventory_pre', $from_product_id, $to_product_id, $options, $variants);

    $inventory = db_get_array("SELECT * FROM ?:product_options_inventory WHERE product_id = ?i", $from_product_id);

    foreach ($inventory as $key => $value) {
        $_variants = explode('_', $value['combination']);
        $inventory[$key]['combination'] = '';
        foreach ($_variants as $kk => $vv) {
            if (($kk % 2) == 0 && !empty($_variants[$kk + 1])) {
                $_comb[0] = $options[$vv];
                $_comb[1] = $variants[$_variants[$kk + 1]];

                $new_variants[$kk] = $_comb[1];
                $inventory[$key]['combination'] .= implode('_', $_comb) . (!empty($_variants[$kk + 2]) ? '_' : '');
            }
        }

        $_data['product_id'] = $to_product_id;
        $_data['combination_hash'] = fn_generate_cart_id($to_product_id, array('product_options' => $new_variants));
        $_data['combination'] = rtrim($inventory[$key]['combination'], "|");
        $_data['amount'] = $value['amount'];
        $_data['product_code'] = $value['product_code'];
        $_data['position'] = $value['position'];
        db_query("INSERT INTO ?:product_options_inventory ?e", $_data);

        // Clone option images
        fn_clone_image_pairs($_data['combination_hash'], $value['combination_hash'], 'product_option');
    }

    /**
     * Adds additional actions after options inventory clone
     *
     * @param int   $from_product_id Identifier of product from that options are copied
     * @param int   $to_product_id   Identifier of product to that options are copied
     * @param array $options         Array with options identifiers where old identifier points to new identifier
     * @param array $variants        Array with variant identifiers where old identifier points to new identifier
     */
    fn_set_hook('clone_options_inventory_post', $from_product_id, $to_product_id, $options, $variants);
}

/**
 * Generate url-safe name for the object
 * Example:
 *  Hello, World! => hello-world
 *  Русский код => russky-kod
 *
 * @param string $str String to be checked and converted
 * @param string $object_type Extra string, object type (e.g.: 'products', 'categories'). Result: some-string-products
 * @param int $object_id Extra string, Object identifier. Result: some-string-products-34
 * @param boolean $is_multi_lang Support multi-language names
 * @return string Url-safe name
 */
function fn_generate_name($str, $object_type = '', $object_id = 0, $is_multi_lang = false)
{
    /**
     * Change parameters for generating file name
     *
     * @param string $str         Basic file name
     * @param string $object_type Object type
     * @param int    $object_id   Object identifier
     */
    fn_set_hook('generate_name_pre', $str, $object_type, $object_id);

    $delimiter = SEO_DELIMITER;
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8'); // convert html special chars back to original chars

    $result = '';

    if (!empty($str)) {
        if ($is_multi_lang) {
            $literals = "/[^a-z\p{Ll}\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{Nd}\p{Pc}\p{Mn}0-9-\.]/u";
            $convert_letters = fn_get_schema('literal_converter', 'general');
        } else {
            $literals = "/[^a-z0-9-\.]/";
            $convert_letters = fn_get_schema('literal_converter', 'schema');
        }
        $str = strtr($str, $convert_letters);

        if (!empty($object_type)) {
            $str .= $delimiter . $object_type . $object_id;
        }

        $str = fn_strtolower($str); // only lower letters
        $str = preg_replace($literals, '', $str); // URL can contain latin letters, numbers, dashes and points only
        $str = preg_replace("/($delimiter){2,}/", $delimiter, $str); // replace double (and more) dashes with one dash

        $result = trim($str, '-'); // remove trailing dash if exist
    }

    /**
     * Change generated file name
     *
     * @param string $result      Generated file name
     * @param string $str         Basic file name
     * @param string $object_type Object type
     * @param int    $object_id   Object identifier
     */
    fn_set_hook('generate_name_post', $result, $str, $object_type, $object_id);

    return $result;
}

/**
 * FConstructs a string in format option1_variant1_option2_variant2...
 *
 * @param array $product_options
 * @return string
 */
function fn_get_options_combination($product_options)
{
    /**
     * Changes params for generating options combination
     *
     * @param array $product_options Array with selected options values
     */
    fn_set_hook('get_options_combination_pre', $product_options);

    if (empty($product_options) && !is_array($product_options)) {
        return '';
    }

    $combination = '';
    foreach ($product_options as $option => $variant) {
        $combination .= $option . '_' . $variant . '_';
    }
    $combination = trim($combination, '_');

    /**
     * Changes options combination
     *
     * @param array  $product_options Array with selected options values
     * @param string $combination     Generated combination
     */
    fn_set_hook('get_options_combination_post', $product_options, $combination);

    return $combination;
}

/**
 * Gets products list by search params
 *
 * @param array  $params         Product search params
 * @param int    $items_per_page Items per page
 * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return array Products list and Search params
 */
function fn_get_products($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params for selecting products
     *
     * @param array  $params         Product search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_products_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('products', $params);

    // Set default values to input params
    $default_params = array (
        'area' => AREA,
        'use_caching' => true,
        'extend' => array('product_name', 'prices', 'categories'),
        'custom_extend' => array(),
        'pname' => '',
        'pshort' => '',
        'pfull' => '',
        'pkeywords' => '',
        'feature' => array(),
        'type' => 'simple',
        'page' => 1,
        'action' => '',
        'filter_variants' => array(),
        'features_hash' => '',
        'limit' => 0,
        'bid' => 0,
        'match' => '',
        'tracking' => array(),
        'get_frontend_urls' => false,
        'items_per_page' => $items_per_page,
        'apply_disabled_filters' => ''
    );
    if (empty($params['custom_extend'])) {
        $params['extend'] = !empty($params['extend']) ? array_merge($default_params['extend'], $params['extend']) : $default_params['extend'];
    } else {
        $params['extend'] = $params['custom_extend'];
    }

    $params = array_merge($default_params, $params);

    if ((empty($params['pname']) || $params['pname'] != 'Y')
        && (empty($params['pshort']) || $params['pshort'] != 'Y')
        && (empty($params['pfull']) || $params['pfull'] != 'Y')
        && (empty($params['pkeywords']) || $params['pkeywords'] != 'Y')
        && !empty($params['q'])
    ) {
        $params['pname'] = 'Y';
    }

    $total = !empty($params['total']) ? intval($params['total']) : 0;
    $auth = & Tygh::$app['session']['auth'];

    $fields = array(
        'product_id' => 'products.product_id',
    );

    // Define sort fields
    // @TODO move to separate function with hook or merge with fn_get_products_sorting()
    $sortings = array (
        'code' => 'products.product_code',
        'status' => 'products.status',
        'product' => 'product',
        'position' => 'products_categories.position',
        'price' => 'price',
        'list_price' => 'products.list_price',
        'weight' => 'products.weight',
        'amount' => 'products.amount',
        'timestamp' => 'products.timestamp',
        'updated_timestamp' => 'products.updated_timestamp',
        'popularity' => 'popularity.total',
        'company' => 'company_name',
        'null' => 'NULL'
    );

    if (!empty($params['get_subscribers'])) {
        $sortings['num_subscr'] = 'num_subscr';
        $fields['num_subscr'] = 'COUNT(DISTINCT product_subscriptions.subscription_id) as num_subscr';
    }

    if (!empty($params['order_ids'])) {
        $sortings['p_qty'] = 'purchased_qty';
        $sortings['p_subtotal'] = 'purchased_subtotal';
        $fields['purchased_qty'] = "SUM(?:order_details.amount) as purchased_qty";
        $fields['purchased_subtotal'] = "SUM(?:order_details.price * ?:order_details.amount) as purchased_subtotal";
    }

    // Fallback to default sorting field
    if (empty($params['sort_by'])) {
        $params = array_merge($params, fn_get_default_products_sorting());
    }

    // Fallback to default sorting order
    $sortings_list = fn_get_products_sorting();
    if (empty($params['sort_order'])) {
        if (!empty($sortings_list[$params['sort_by']]['default_order'])) {
            $params['sort_order'] = $sortings_list[$params['sort_by']]['default_order'];
        } else {
            $params['sort_order'] = 'asc';
        }
    }

    if (isset($params['compact']) && $params['compact'] == 'Y') {
        $union_condition = ' OR ';
    } else {
        $union_condition = ' AND ';
    }

    $join = $condition = $u_condition = $inventory_join_cond = '';
    $having = array();

    // Search string condition for SQL query
    if (isset($params['q']) && fn_string_not_empty($params['q'])) {
        $params['q'] = trim($params['q']);
        if ($params['match'] == 'any') {
            $query_pieces = fn_explode(' ', $params['q']);
            $search_type = ' OR ';
        } elseif ($params['match'] == 'all') {
            $query_pieces = fn_explode(' ', $params['q']);
            $search_type = ' AND ';
        } else {
            $query_pieces = array($params['q']);
            $search_type = '';
        }

        $inventory_code_conditions = array();
        $search_conditions = array();
        foreach ($query_pieces as $piece) {
            if (strlen($piece) == 0) {
                continue;
            }

            $tmp = db_quote("(descr1.search_words LIKE ?l)", '%' . $piece . '%'); // check search words

            if ($params['pname'] == 'Y') {
                $tmp .= db_quote(" OR descr1.product LIKE ?l", '%' . $piece . '%');
            }
            if ($params['pshort'] == 'Y') {
                $tmp .= db_quote(" OR descr1.short_description LIKE ?l", '%' . $piece . '%');
                $tmp .= db_quote(" OR descr1.short_description LIKE ?l", '%' . htmlentities($piece, ENT_QUOTES, 'UTF-8') . '%');
            }
            if ($params['pfull'] == 'Y') {
                $tmp .= db_quote(" OR descr1.full_description LIKE ?l", '%' . $piece . '%');
                $tmp .= db_quote(" OR descr1.full_description LIKE ?l", '%' . htmlentities($piece, ENT_QUOTES, 'UTF-8') . '%');
            }
            if ($params['pkeywords'] == 'Y') {
                $tmp .= db_quote(" OR (descr1.meta_keywords LIKE ?l OR descr1.meta_description LIKE ?l)", '%' . $piece . '%', '%' . $piece . '%');
            }
            if (!empty($params['feature_variants'])) {
                $tmp .= db_quote(" OR ?:product_features_values.value LIKE ?l", '%' . $piece . '%');
                $params['extend'][] = 'feature_values';
            }

            if (isset($params['pcode_from_q']) && $params['pcode_from_q'] == 'Y') {
                $tmp .= db_quote(" OR inventory.product_code LIKE ?l OR products.product_code LIKE ?l",
                    "%{$piece}%", "%{$piece}%"
                );

                $inventory_code_conditions[] = db_quote("inventory.product_code LIKE ?l", "%{$piece}%");
            }

            /**
             * Executed for each part of a search query; it allows to modify the SQL conditions of the search.
             *
             * @param array  $params        List of parameters passed to fn_get_products functions
             * @param array  $fields        List of fields for retrieving
             * @param array  $sortings      Sorting fields
             * @param string $condition     String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
             * @param string $join          String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
             * @param string $sorting       String containing the SQL-query ORDER BY clause. This variable isn't used; it remains only for backward compatibility.
             * @param string $group_by      String containing the SQL-query GROUP BY field. This variable isn't used; it remains only for backward compatibility.
             * @param string $tmp           String containing SQL-query search condition by piece
             * @param string $piece         Part of the search query
             * @param array  $having        HAVING condition
             */
            fn_set_hook('additional_fields_in_search', $params, $fields, $sortings, $condition, $join, $sorting, $group_by, $tmp, $piece, $having);

            $search_conditions[] = '(' . $tmp . ')';
        }

        if (!empty($inventory_code_conditions)) {
            $fields['combination'] = "IF(" . implode(' OR ', $inventory_code_conditions) . ', inventory.combination, null) AS combination';
        }

        $_cond = implode($search_type, $search_conditions);

        if (!empty($search_conditions)) {
            $condition .= ' AND (' . $_cond . ') ';
        }

        //if perform search we also get additional fields
        if ($params['pname'] == 'Y') {
            $params['extend'][] = 'product_name';
        }

        if ($params['pshort'] == 'Y' || $params['pfull'] == 'Y' || $params['pkeywords'] == 'Y') {
            $params['extend'][] = 'description';
        }

        unset($search_conditions);
    }

    //
    // [Advanced and feature filters]
    //
    if (!empty($params['apply_limit']) && $params['apply_limit'] && !empty($params['pid'])) {
        $pids = array();

        foreach ($params['pid'] as $pid) {
            if ($pid != $params['exclude_pid']) {
                if (count($pids) == $params['limit']) {
                    break;
                } else {
                    $pids[] = $pid;
                }
            }
        }
        $params['pid'] = $pids;
    }

    if (!empty($params['pcode'])) {
        $pcode = trim($params['pcode']);
        $condition .= db_quote(" AND (inventory.product_code LIKE ?l OR products.product_code LIKE ?l)",
            "%{$pcode}%", "%{$pcode}%"
        );
        $fields['combination'] = 'inventory.combination';
    }

    // Feature code
    if (!empty($params['feature_code'])) {
        $condition .= db_quote(" AND ?:product_features.feature_code = ?s", $params['feature_code']);
        $params['extend'][] = 'features';
        $params['extend'][] = 'feature_values';
    }

    // find with certain variant
    if (!empty($params['variant_id'])) {
        $join .= db_quote(" INNER JOIN ?:product_features_values as c_var ON c_var.product_id = products.product_id AND c_var.lang_code = ?s AND c_var.variant_id = ?i", $lang_code, $params['variant_id']);
    }

    if (!empty($params['features_hash']) || !empty($params['filter_variants'])) {

        $selected_filters = !empty($params['filter_variants']) ? $params['filter_variants'] : fn_parse_filters_hash($params['features_hash']);
        $filter_request = db_quote("SELECT ?:product_filters.*, ?:product_features.feature_type FROM ?:product_filters LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id WHERE ?:product_filters.filter_id IN (?n)", array_keys($selected_filters));

        if (empty($params['apply_disabled_filters'])) {
            $filter_request .= " AND ?:product_filters.status = 'A'";
        }

        $filters = db_get_hash_array($filter_request, 'filter_id');
        list($join, $condition) = fn_generate_feature_conditions($filters, $selected_filters, $join, $condition, $lang_code);

        $params = fn_generate_filter_field_params($params, $filters, $selected_filters);
    }

    fn_set_hook('get_products_before_select', $params, $join, $condition, $u_condition, $inventory_join_cond, $sortings, $total, $items_per_page, $lang_code, $having);

    //
    // [/Advanced filters]
    //

    $feature_search_condition = '';
    if (!empty($params['feature_variants'])) {

        $feature_params = array(
            'plain' => true,
            'variants' => false,
            'exclude_group' => true,
            'feature_id' => array_keys($params['feature_variants'])

        );
        list($features, ) = fn_get_product_features($feature_params, PRODUCT_FEATURES_THRESHOLD);
        list($join, $condition) = fn_generate_feature_conditions($features, $params['feature_variants'], $join, $condition, $lang_code);
    }

    // Filter by category ID
    if (!empty($params['cid'])) {
        $cids = is_array($params['cid']) ? $params['cid'] : explode(',', $params['cid']);

        if (isset($params['subcats']) && $params['subcats'] == 'Y') {
            $_ids = db_get_fields(
                "SELECT a.category_id"."
                 FROM ?:categories as a"."
                 LEFT JOIN ?:categories as b"."
                 ON b.category_id IN (?n)"."
                 WHERE a.id_path LIKE CONCAT(b.id_path, '/%')",
                $cids
            );

            $cids = fn_array_merge($cids, $_ids, false);
        }

        $condition .= db_quote(" AND ?:categories.category_id IN (?n)", $cids);
    }

    // If we need to get the products by IDs and no IDs passed, don't search anything
    if (!empty($params['force_get_by_ids'])
        && empty($params['pid'])
        && empty($params['product_id'])
        && empty($params['get_conditions'])
    ) {
        return array(array(), $params, 0);
    }

    // Product ID search condition for SQL query
    if (!empty($params['pid'])) {
        $pid = $params['pid'];
        if (!is_array($pid) && strpos($pid, ',') !== false) {
            $pid = explode(',', $pid);
        }
        $u_condition .= db_quote($union_condition . ' products.product_id IN (?n)', $pid);
    }

    // Exclude products from search results
    if (!empty($params['exclude_pid'])) {
        $condition .= db_quote(' AND products.product_id NOT IN (?n)', $params['exclude_pid']);
    }

    // Search products by localization
    $condition .= fn_get_localizations_condition('products.localization', true);

    $company_condition = '';

    if (fn_allowed_for('MULTIVENDOR')) {
        if ($params['area'] == 'C') {
            $company_condition .= " AND companies.status = 'A' ";
            $params['extend'][] = 'companies';
        } else {
            $company_condition .= fn_get_company_condition('products.company_id');
        }
    } else {
        $cat_company_condition = '';
        if (Registry::get('runtime.company_id')) {
            $params['extend'][] = 'categories';
            $cat_company_condition .= fn_get_company_condition('?:categories.company_id');
        } elseif (!empty($params['company_ids'])) {
            $params['extend'][] = 'categories';
            $cat_company_condition .= db_quote(' AND ?:categories.company_id IN (?n)', explode(',', $params['company_ids']));
        }
        $company_condition .= $cat_company_condition;
    }

    $condition .= $company_condition;

    if (!fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id') && isset($params['company_id'])) {
        $params['company_id'] = Registry::get('runtime.company_id');
    }
    if (isset($params['company_id']) && $params['company_id'] != '') {
        $condition .= db_quote(' AND products.company_id = ?i ', $params['company_id']);
    }

    if (!empty($params['filter_params'])) {
        foreach ($params['filter_params'] as $field => $f_vals) {
            $condition .= db_quote(' AND products.' . $field . ' IN (?a) ', $f_vals);
        }
    }

    if (isset($params['price_from']) && fn_is_numeric($params['price_from'])) {
        $condition .= db_quote(' AND prices.price >= ?d', fn_convert_price(trim($params['price_from'])));
        $params['extend'][] = 'prices2';
        $params['extend'][] = 'prices';
    }

    if (isset($params['price_to']) && fn_is_numeric($params['price_to'])) {
        $condition .= db_quote(' AND prices.price <= ?d', fn_convert_price(trim($params['price_to'])));
        $params['extend'][] = 'prices2';
        $params['extend'][] = 'prices';
    }

    if (isset($params['weight_from']) && fn_is_numeric($params['weight_from'])) {
        $condition .= db_quote(' AND products.weight >= ?d', fn_convert_weight(trim($params['weight_from'])));
    }

    if (isset($params['weight_to']) && fn_is_numeric($params['weight_to'])) {
        $condition .= db_quote(' AND products.weight <= ?d', fn_convert_weight(trim($params['weight_to'])));
    }

    // search specific inventory status
    if (!empty($params['tracking'])) {
        $condition .= db_quote(' AND products.tracking IN(?a)', $params['tracking']);
    }

    if (isset($params['amount_from']) && fn_is_numeric($params['amount_from'])) {
        $condition .= db_quote(
            " AND IF(products.tracking = ?s, inventory.amount >= ?i, products.amount >= ?i)",
            ProductTracking::TRACK_WITH_OPTIONS,
            $params['amount_from'],
            $params['amount_from']
        );
        $inventory_join_cond .= db_quote(' AND inventory.amount >= ?i', $params['amount_from']);
    }

    if (isset($params['amount_to']) && fn_is_numeric($params['amount_to'])) {
        $condition .= db_quote(
            " AND IF(products.tracking = ?s, inventory.amount <= ?i, products.amount <= ?i)",
            ProductTracking::TRACK_WITH_OPTIONS,
            $params['amount_to'],
            $params['amount_to']
        );
        $inventory_join_cond .= db_quote(' AND inventory.amount <= ?i', $params['amount_to']);
    }

    // Cut off out of stock products
    if (Registry::get('settings.General.inventory_tracking') == 'Y' && // FIXME? Registry in model
        Registry::get('settings.General.show_out_of_stock_products') == 'N' &&
        $params['area'] == 'C'
    ) {
        $condition .= db_quote(
            ' AND (CASE products.tracking' .
            '   WHEN ?s THEN inventory.amount > 0' .
            '   WHEN ?s THEN products.amount > 0' .
            '   ELSE 1' .
            ' END)',
            ProductTracking::TRACK_WITH_OPTIONS,
            ProductTracking::TRACK_WITHOUT_OPTIONS
        );
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND products.status IN (?a)', $params['status']);
    }

    if (!empty($params['shipping_freight_from'])) {
        $condition .= db_quote(' AND products.shipping_freight >= ?d', $params['shipping_freight_from']);
    }

    if (!empty($params['shipping_freight_to'])) {
        $condition .= db_quote(' AND products.shipping_freight <= ?d', $params['shipping_freight_to']);
    }

    if (!empty($params['free_shipping'])) {
        $condition .= db_quote(' AND products.free_shipping = ?s', $params['free_shipping']);
    }

    if (!empty($params['downloadable'])) {
        $condition .= db_quote(' AND products.is_edp = ?s', $params['downloadable']);
    }

    // Join inventory table
    if (
        (isset($params['amount_to']) && fn_is_numeric($params['amount_to']))
        || (isset($params['amount_from']) && fn_is_numeric($params['amount_from']))
        || !empty($params['pcode'])
        || (isset($params['pcode_from_q']) && $params['pcode_from_q'] == 'Y')
        || (
            Registry::get('settings.General.inventory_tracking') == 'Y'
            && Registry::get('settings.General.show_out_of_stock_products') == 'N'
            && $params['area'] == 'C'
        )
    ) {
        $join .= " LEFT JOIN ?:product_options_inventory as inventory ON inventory.product_id = products.product_id $inventory_join_cond";
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (products.timestamp >= ?i AND products.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(" AND products.product_id IN (?n)", explode(',', $params['item_ids']));
    }

    if (isset($params['popularity_from']) && fn_is_numeric($params['popularity_from'])) {
        $params['extend'][] = 'popularity';
        $condition .= db_quote(' AND popularity.total >= ?i', $params['popularity_from']);
    }

    if (isset($params['popularity_to']) && fn_is_numeric($params['popularity_to'])) {
        $params['extend'][] = 'popularity';
        $condition .= db_quote(' AND popularity.total <= ?i', $params['popularity_to']);
    }

    if (!empty($params['order_ids'])) {
        $arr = (strpos($params['order_ids'], ',') !== false || !is_array($params['order_ids'])) ? explode(',', $params['order_ids']) : $params['order_ids'];

        $condition .= db_quote(" AND ?:order_details.order_id IN (?n)", $arr);

        $join .= " LEFT JOIN ?:order_details ON ?:order_details.product_id = products.product_id";
    }

    $limit = '';
    $group_by = 'products.product_id';
    // Show enabled products
    $_p_statuses = array('A');
    $condition .= ($params['area'] == 'C') ? ' AND (' . fn_find_array_in_set($auth['usergroup_ids'], 'products.usergroup_ids', true) . ')' . db_quote(' AND products.status IN (?a)', $_p_statuses) : '';

    // -- JOINS --

    // Feature values and features
    if (in_array('feature_values', $params['extend'])) {
        $join .= db_quote(" LEFT JOIN ?:product_features_values ON ?:product_features_values.product_id = products.product_id AND ?:product_features_values.lang_code = ?s", $lang_code);
        if (in_array('features', $params['extend'])) {
            $join .= db_quote(" LEFT JOIN ?:product_features ON ?:product_features_values.feature_id = ?:product_features.feature_id");
        }
    }

    if (in_array('product_name', $params['extend'])) {
        $fields['product'] = 'descr1.product as product';
        $join .= db_quote(" LEFT JOIN ?:product_descriptions as descr1 ON descr1.product_id = products.product_id AND descr1.lang_code = ?s ", $lang_code);
    }

    // get prices
    $price_condition = '';
    if (in_array('prices', $params['extend'])) {
        $join .= " LEFT JOIN ?:product_prices as prices ON prices.product_id = products.product_id AND prices.lower_limit = 1";
        $price_condition = db_quote(' AND prices.usergroup_id IN (?n)', (($params['area'] == 'A') ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $auth['usergroup_ids'])));
        $condition .= $price_condition;
    }

    // get prices for search by price
    if (in_array('prices2', $params['extend'])) {
        $price_usergroup_cond_2 = db_quote(' AND prices_2.usergroup_id IN (?n)', (($params['area'] == 'A') ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $auth['usergroup_ids'])));
        $join .= " LEFT JOIN ?:product_prices as prices_2 ON prices.product_id = prices_2.product_id AND prices_2.lower_limit = 1 AND prices_2.price < prices.price " . $price_usergroup_cond_2;
        $condition .= ' AND prices_2.price IS NULL';
        $price_condition .= ' AND prices_2.price IS NULL';
    }

    // get companies
    $companies_join = db_quote(" LEFT JOIN ?:companies AS companies ON companies.company_id = products.company_id ");
    if (in_array('companies', $params['extend'])) {
        $fields['company_name'] = 'companies.company as company_name';
        $join .= $companies_join;
    }

    // for compatibility
    if (in_array('category_ids', $params['extend'])) {
        $params['extend'][] = 'categories';
    }

    // get categories
    $_c_statuses = array('A' , 'H');// Show enabled categories
    $skip_checking_usergroup_permissions = fn_is_preview_action($auth, $params);

    if ($skip_checking_usergroup_permissions) {
        $category_avail_cond = '';
    } else {
        $category_avail_cond = ($params['area'] == 'C') ? ' AND (' . fn_find_array_in_set($auth['usergroup_ids'], '?:categories.usergroup_ids', true) . ')' : '';
    }
    $category_avail_cond .= ($params['area'] == 'C') ? db_quote(" AND ?:categories.status IN (?a) ", $_c_statuses) : '';
    $categories_join = " INNER JOIN ?:products_categories as products_categories ON products_categories.product_id = products.product_id INNER JOIN ?:categories ON ?:categories.category_id = products_categories.category_id $category_avail_cond $feature_search_condition";

    if (!empty($params['order_ids'])) {
        // Avoid duplicating by sub-categories
        $condition .= db_quote(' AND products_categories.link_type = ?s', 'M');
    }

    if (in_array('categories', $params['extend'])) {
        $join .= $categories_join;
        $condition .= fn_get_localizations_condition('?:categories.localization', true);
    }

    // get popularity
    $popularity_join = db_quote(" LEFT JOIN ?:product_popularity as popularity ON popularity.product_id = products.product_id");
    if (in_array('popularity', $params['extend'])) {
        $fields['popularity'] = 'popularity.total as popularity';
        $join .= $popularity_join;
    }

    if (!empty($params['get_subscribers'])) {
        $join .= " LEFT JOIN ?:product_subscriptions as product_subscriptions ON product_subscriptions.product_id = products.product_id";
    }

    //  -- \JOINs --

    if (!empty($u_condition)) {
        $condition .= " $union_condition ((" . ($union_condition == ' OR ' ? '0 ' : '1 ') . $u_condition . ')' . $company_condition . $price_condition . ')';
    }

    // Load prices in main SQL-query when they are needed and sorting or filtering by price is applied
    if (
        in_array('prices', $params['extend'])
        && (
            (isset($params['sort_by']) && $params['sort_by'] == 'price')
            || in_array('prices2', $params['extend'])
        )
    ) {
        $fields['price'] = 'MIN(IF(prices.percentage_discount = 0, prices.price, prices.price - (prices.price * prices.percentage_discount)/100)) as price';
    }

    /**
     * Changes additional params for selecting products
     *
     * @param array  $params    Product search params
     * @param array  $fields    List of fields for retrieving
     * @param array  $sortings  Sorting fields
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $having    HAVING condition
     */
    fn_set_hook('get_products', $params, $fields, $sortings, $condition, $join, $sorting, $group_by, $lang_code, $having);

    // -- SORTINGS --
    if ($params['sort_by'] == 'popularity' && !in_array('popularity', $params['extend'])) {
        $join .= $popularity_join;
    }

    if ($params['sort_by'] == 'company' && !in_array('companies', $params['extend'])) {
        $join .= $companies_join;
    }

    // Fallback to any other sorting field in case of $sortings doesn't contain desired sorting field
    if (empty($sortings[$params['sort_by']])) {

        foreach (array_keys($sortings_list) as $sortings_list_sort_by) {

            if (isset($sortings[$sortings_list_sort_by])) {
                $params['sort_by'] = $sortings_list_sort_by;
                break;
            }

        }
    }

    $sorting = db_sort($params, $sortings);

    if (!empty($sorting) && $params['sort_by'] !== 'null') {
        $sorting .= ', product_id ASC'; // workaround for bug https://bugs.mysql.com/bug.php?id=69732
    }

    if (fn_allowed_for('ULTIMATE')) {
        if (in_array('sharing', $params['extend'])) {
            $fields['is_shared_product'] = "IF(COUNT(IF(?:categories.company_id = products.company_id, NULL, ?:categories.company_id)), 'Y', 'N') as is_shared_product";
            if (strpos($join, $categories_join) === false) {
                $join .= $categories_join;
            }
        }
    }
    // -- \SORTINGS --

    // Used for View cascading
    if (!empty($params['get_query'])) {
        return "SELECT products.product_id FROM ?:products as products $join WHERE 1 $condition GROUP BY products.product_id";
    }

    // Used for Extended search
    if (!empty($params['get_conditions'])) {
        return array($fields, $join, $condition);
    }

    if (!empty($params['limit'])) {
        $limit = db_quote(" LIMIT 0, ?i", $params['limit']);
    } elseif (!empty($params['items_per_page'])) {
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $calc_found_rows = '';
    if (empty($total)) {
        $calc_found_rows = 'SQL_CALC_FOUND_ROWS';
    }

    if (!empty($having)) {
        $having = ' HAVING ' . implode(' AND ', $having);
    } else {
        $having = '';
    }

    $sql_query_body = "SELECT $calc_found_rows " . implode(', ', $fields)
        . " FROM ?:products as products $join WHERE 1 $condition GROUP BY $group_by $having $sorting $limit";

    $fn_load_products = function ($query, $params) use ($total) {
        $products = db_get_array($query);
        $total_found_rows = empty($params['items_per_page'])
            ? count($products)
            : (empty($total) ? db_get_found_rows() : $total);

        return array($products, $total_found_rows);
    };

    // Caching conditions
    if (
        $params['use_caching']

        // We're on category products page
        && isset($params['dispatch'])
        && $params['dispatch'] == 'categories.view'
        && $params['area'] == 'C'

        // Context user is guest
        && $auth['usergroup_ids'] == array(0, 1)

        // We filter by category
        && !empty($params['cid'])

        // No search query
        && empty($params['q'])

        // No filters
        && empty($params['pid'])
        && empty($params['exclude_pid'])
        && empty($params['features_hash'])
        && empty($params['feature_code'])
        && empty($params['multiple_variants'])
        && empty($params['custom_range'])
        && empty($params['field_range'])
        && empty($params['fields_ids'])
        && empty($params['slider_vals'])
        && empty($params['ch_filters'])
        && empty($params['tx_features'])
        && empty($params['feature_variants'])
        && empty($params['filter_params'])
        && !isset($params['price_from'])
        && !isset($params['price_to'])
        && !isset($params['weight_from'])
        && !isset($params['weight_to'])
        && empty($params['tracking'])
        && !isset($params['amount_from'])
        && !isset($params['amount_to'])
        && empty($params['status'])
        && empty($params['shipping_freight_from'])
        && empty($params['shipping_freight_to'])
        && empty($params['free_shipping'])
        && empty($params['downloadable'])
        && !isset($params['pcode'])
        && empty($params['period'])
        && empty($params['item_ids'])
        && !isset($params['popularity_from'])
        && !isset($params['popularity_to'])
        && empty($params['order_ids'])
    ) {
        $cache_prefix = __FUNCTION__;
        $cache_key = md5($sql_query_body);

        Registry::registerCache(
            array($cache_prefix, $cache_key),
            array('products', 'categories', 'products_categories'),
            Registry::cacheLevel('static'),
            true
        );

        if ($cache = Registry::get($cache_key)) {
            list($products, $params['total_items']) = $cache;
        } else {
            list ($products, $params['total_items']) = $fn_load_products($sql_query_body, $params);

            if ($params['total_items'] > Registry::get('config.tweaks.products_found_rows_no_cache_limit')) {
                Registry::set($cache_key, array($products, $params['total_items']));
            }
        }
    } else {
        list ($products, $params['total_items']) = $fn_load_products($sql_query_body, $params);
    }

    if (!empty($params['get_frontend_urls'])) {
        foreach ($products as &$product) {
            $product['url'] = fn_url('products.view?product_id=' . $product['product_id'], 'C');
        }
    }

    if (!empty($params['item_ids'])) {
        $products = fn_sort_by_ids($products, explode(',', $params['item_ids']));
    }
    if (!empty($params['pid']) && !empty($params['apply_limit']) && $params['apply_limit']) {
        $products = fn_sort_by_ids($products, $params['pid']);
    }

    $products = fn_load_products_extra_data($products, $params, $lang_code);

    /**
     * Changes selected products
     *
     * @param array  $products  Array of products
     * @param array  $params    Product search params
     * @param string $lang_code Language code
     */
    fn_set_hook('get_products_post', $products, $params, $lang_code);

    LastView::instance()->processResults('products', $products, $params);

    return array($products, $params);
}

function fn_sort_by_ids($items, $ids, $field = 'product_id')
{
    $tmp = array();

    foreach ($items as $k => $item) {
        foreach ($ids as $key => $item_id) {
            if ($item_id == $item[$field]) {
                $tmp[$key] = $item;
                break;
            }
        }
    }

    ksort($tmp);

    return $tmp;
}

/**
 * Lazily loads additional data related to products after they have been fetched from DB.
 * Used to ease main product loading SQL-query.
 *
 * @param array $products List of products
 * @param array $params Parameters passed to fn_get_products()
 * @param string $lang_code Language code passed to fn_get_products()
 *
 * @return array List of products with additional data merged into.
 */
function fn_load_products_extra_data($products, $params, $lang_code)
{
    if (empty($products)) {
        return $products;
    }

    $products = fn_array_elements_to_keys($products, 'product_id');
    $product_ids = array_keys($products);
    $extra_fields = array();

    // Fields from "products" table
    $extra_fields['?:products'] = array(
        'primary_key' => 'product_id',
        'fields' => empty($params['only_short_fields'])
            ? array('*')
            : array(
                'product_id',
                'product_code',
                'product_type',
                'status',
                'company_id',
                'list_price',
                'amount',
                'weight',
                'tracking',
                'is_edp',
            )
    );

    // Load prices lazily when they are needed and no sorting or filtering by price is applied
    if (
        in_array('prices', $params['extend'])
        && $params['sort_by'] != 'price'
        && !in_array('prices2', $params['extend'])
    ) {
        $extra_fields['?:product_prices'] = array(
            'primary_key' => 'product_id',
            'fields' => array(
                'price' =>
                    'MIN(IF(' .
                    '?:product_prices.percentage_discount = 0,' .
                    '?:product_prices.price,' .
                    '?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100' .
                    '))'
            ),
            'condition'   => db_quote(
                ' AND ?:product_prices.lower_limit = 1 AND ?:product_prices.usergroup_id IN (?n)',
                ($params['area'] == 'A')
                    ? USERGROUP_ALL
                    : array_unique(array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids']))
            ),
            'group_by' => ' GROUP BY ?:product_prices.product_id'
        );
    }

    // Descriptions
    $extra_fields['?:product_descriptions']['primary_key'] = 'product_id';
    $extra_fields['?:product_descriptions']['condition'] = db_quote(
        " AND ?:product_descriptions.lang_code = ?s", $lang_code
    );

    if (in_array('search_words', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'search_words';
    }
    if (in_array('description', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'short_description';

        if (in_array('full_description', $params['extend'])) {
            $extra_fields['?:product_descriptions']['fields'][] = 'full_description';
        } else {
            $extra_fields['?:product_descriptions']['fields']['full_description'] =
                "IF(?:product_descriptions.short_description = '', ?:product_descriptions.full_description, '')";
        }
    }

    // Categories
    if (in_array('categories', $params['extend'])) {
        $categories_join = ' INNER JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id';
        if ($params['area'] == 'C') {
            if (fn_allowed_for('ULTIMATE')) {
                if (Registry::get('runtime.company_id')) {
                    $categories_join .= fn_get_company_condition('?:categories.company_id');
                } elseif (!empty($params['company_ids'])) {
                    $categories_join .= db_quote(' AND ?:categories.company_id IN (?n)', explode(',', $params['company_ids']));
                }
            }

            if (!fn_is_preview_action(Tygh::$app['session']['auth'], $params)) {
                $categories_join .= ' AND ('
                    . fn_find_array_in_set(Tygh::$app['session']['auth']['usergroup_ids'], '?:categories.usergroup_ids', true)
                    . ')';
            }
            $categories_join .= db_quote(' AND ?:categories.status IN (?a) ', array('A', 'H'));
        }

        $extra_fields['?:products_categories'] = array(
            'primary_key' => 'product_id',
            'fields'    => array(
                'category_ids'        => 'GROUP_CONCAT('
                    . 'IF(?:products_categories.link_type = "M",'
                    . ' CONCAT(?:products_categories.category_id, "M"),'
                    . ' ?:products_categories.category_id)'
                    . ')',
            ),
            'condition' => fn_get_localizations_condition('?:categories.localization', true),
            'join'      => $categories_join,
            'group_by' => ' GROUP BY ?:products_categories.product_id'
        );

        if (!empty($params['cid'])) {
            $category_ids = is_array($params['cid']) ? $params['cid'] : explode(',', $params['cid']);

            // Fetch position of product at given category.
            // This is only possible when only one category is given at "cid" parameter, because it's impossible to
            // determine which category to choose as "position" field source when selecting products from several categories.
            if (sizeof($category_ids) === 1) {
                $extra_fields['?:products_categories']['fields']['position'] = 'product_position_source.position';
                $extra_fields['?:products_categories']['join'] .= db_quote(
                    ' LEFT JOIN ?:products_categories AS product_position_source'
                    . ' ON ?:products_categories.product_id = product_position_source.product_id'
                    . ' AND product_position_source.category_id = ?i',
                    reset($category_ids)
                );
            }
        }
    }

    /**
     * Allows you to extend configuration of extra fields that should be lazily loaded for products.
     *
     * @see fn_load_extra_data_by_item_ids()
     * @param array  $extra_fields
     * @param array  $products     List of products
     * @param array  $product_ids  List of product identifiers
     * @param array  $params       Parameters passed to fn_get_products()
     * @param string $lang_code    Language code passed to fn_get_products()
     */
    fn_set_hook('load_products_extra_data', $extra_fields, $products, $product_ids, $params, $lang_code);

    // Execute extra data loading SQL-queries and merge results into $products array
    fn_merge_extra_data_to_entity_list(
        fn_load_extra_data_by_entity_ids($extra_fields, $product_ids),
        $products
    );

    // Categories post-processing
    if (in_array('categories', $params['extend'])) {
        foreach ($products as $k => $v) {
            list($products[$k]['category_ids'], $products[$k]['main_category']) = fn_convert_categories($v['category_ids']);
        }
    }

    /**
     * Allows you lazily load extra data for products after they were fetched from DB or post-process lazy-loaded
     * additional data related to products.
     *
     * @param array  $products    List of products
     * @param array  $product_ids List of product identifiers
     * @param array  $params      Parameters passed to fn_get_products()
     * @param string $lang_code   Language code passed to fn_get_products()
     */
    fn_set_hook('load_products_extra_data_post', $products, $product_ids, $params, $lang_code);

    return $products;
}

/**
 * Loads additional data related to given enities from corresponding tables.
 * Each specified table triggers single SQL-query.
 *
 * @param array $params Configuration of which fields should be loaded in the following format:
 * array(
 *      table_name => array(
 *          // Name of primary key used by items' table. You may specify SQL expression to use in "fields" array.
 *          'primary_key' => 'product_id',
 *          'fields' => array( // list of fields names from this table to be loaded
 *              'product_id' => 'table_name.object_id',
 *              'short_description',
 *              // you may specify SQL expression that would be used instead field name in the SQL query
 *              'full_description' => 'IF(table_name.full_description = '', table_name.short_description, '')
 *          ),
 *          // optional parameter, allows to specify additional conditions used by SQL query
 *          'condition' => db_query(' AND table_name.company_id = ?i', $company_id),
 *          // optional parameter, allows to specify additional joins used by SQL query
 *          'join' => '',
 *          // optional parameter, allows to specify grouping used by SQL query
 *          'group_by' => '',
 *      )
 * )
 * @param array $item_ids List of entity identifiers
 *
 * @return array List of extra data in format: array(table_name => array(item_id => extra_data, ...), ...)
 */
function fn_load_extra_data_by_entity_ids($params, $item_ids)
{
    $extra_data = array();
    foreach ($params as $table_name => $table_config) {
        if (empty($table_config) || empty($table_config['primary_key']) || empty($table_config['fields'])) {
            continue;
        }
        $primary_key_field = $table_config['primary_key'];
        if (isset($table_config['fields'][$primary_key_field])) {
            $primary_key_expr = $table_config['fields'][$primary_key_field];
        } else {
            $primary_key_expr = $table_name . '.' . $primary_key_field;
        }

        $select_fields = in_array('*', $table_config['fields'])
            ? array()
            : array($primary_key_field => $primary_key_expr);

        foreach ($table_config['fields'] as $k => $v) {
            if (is_integer($k)) {
                $select_fields[$v] = $table_name . '.' . $v;
            } else {
                $select_fields[$k] = $v . ' AS ' . $k;
            }
        }
        $select_fields = implode(', ', $select_fields);

        $condition = empty($table_config['condition']) ? '' : $table_config['condition'];
        $join = empty($table_config['join']) ? '' : $table_config['join'];
        $group_by = empty($table_config['group_by']) ? '' : $table_config['group_by'];

        $extra_data[$table_name] = db_get_hash_array(
            "SELECT $select_fields FROM $table_name $join WHERE $primary_key_expr IN (?n) $condition $group_by",
            $primary_key_field,
            $item_ids
        );
    }

    return $extra_data;
}

/**
 * Merges extra data loaded by fn_load_extra_data to given entity list.
 *
 * @param array $extra_data Data in the following format: array(entity_id => additional_data, ...)
 * @param array $entities   Entity list passed by reference in format: array(entity_id => entity_data, ...)
 */
function fn_merge_extra_data_to_entity_list($extra_data, &$entities)
{
    foreach ($extra_data as $table_name => $table_extra_data) {
        foreach ($table_extra_data as $entity_id => $data) {
            if (isset($entities[$entity_id])) {
                $entities[$entity_id] = array_merge($entities[$entity_id], $data);
            }
        }
    }
}

function fn_convert_categories($category_ids)
{
    $c_ids = explode(',', $category_ids);
    $categories = array();
    $main_category = 0;
    foreach ($c_ids as $v) {
        if (strpos($v, 'M') !== false) {
            $main_category = intval($v);
        }
        if (!in_array(intval($v), $categories)) {
            $categories[] = intval($v);
        }
    }

    if (empty($main_category)) {
        $main_category = reset($categories);
    }

    return array($categories, $main_category);
}

/**
 * Updates product option
 *
 * @param array $option_data option data array
 * @param int $option_id option ID (empty if we're adding the option)
 * @param string $lang_code language code to add/update option for
 * @return int ID of the added/updated option
 */
function fn_update_product_option($option_data, $option_id = 0, $lang_code = DESCR_SL)
{
    /**
     * Changes parameters before update option data
     *
     * @param array  $option_data Option data
     * @param int    $option_id   Option identifier
     * @param string $lang_code   Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_product_option_pre', $option_data, $option_id, $lang_code);

    SecurityHelper::sanitizeObjectData('product_option', $option_data);

    // Add option
    if (empty($option_id)) {
        $action = 'create';
        if (empty($option_data['product_id'])) {
            $option_data['product_id'] = 0;
        }

        $option_data['option_id'] = $option_id = db_query('INSERT INTO ?:product_options ?e', $option_data);

        foreach (fn_get_translation_languages() as $option_data['lang_code'] => $_v) {
            db_query("INSERT INTO ?:product_options_descriptions ?e", $option_data);
        }

        $create = true;
    // Update option
    } else {
        $action = 'update';
        // if option inventory changed from Y to N, we should clear option combinations
        if (!empty($option_data['product_id']) && !empty($option_data['inventory']) && $option_data['inventory'] == 'N') {
            $condition = fn_get_company_condition('?:product_options.company_id');
            $old_option_inventory = db_get_field("SELECT inventory FROM ?:product_options WHERE option_id = ?i $condition", $option_id);
            if ($old_option_inventory == 'Y') {
                $inventory_filled = db_get_field('SELECT COUNT(*) FROM ?:product_options_inventory WHERE product_id = ?i', $option_data['product_id']);
                if ($inventory_filled) {
                    fn_delete_product_option_combinations($option_data['product_id']);
                }
            }
        }

        if (fn_allowed_for('ULTIMATE') && !empty($option_data['product_id']) && fn_ult_is_shared_product($option_data['product_id']) == 'Y') {
            $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $option_data['product_id']);
            $option_id = fn_ult_update_shared_product_option($option_data, $option_id, Registry::ifGet('runtime.company_id', $product_company_id), $lang_code);

            if (Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $product_company_id) {
                $deleted_variants = array();
                fn_set_hook('update_product_option_post', $option_data, $option_id, $deleted_variants, $lang_code);

                return $option_id;
            }
        }

        db_query("UPDATE ?:product_options SET ?u WHERE option_id = ?i", $option_data, $option_id);
        db_query("UPDATE ?:product_options_descriptions SET ?u WHERE option_id = ?i AND lang_code = ?s", $option_data, $option_id, $lang_code);
    }

    if (fn_allowed_for('ULTIMATE')) {
        // options of shared product under the shared store hasn't a company_id. No necessary for updating.
        if (!empty($option_data['company_id'])) {
            fn_ult_update_share_object($option_id, 'product_options', $option_data['company_id']);
        }

        if (!empty($option_data['product_id'])) {
            fn_ult_share_product_option($option_id, $option_data['product_id']);
        }
    }

    if (!empty($option_data['variants'])) {
        $var_ids = array();

        // Generate special variants structure for checkbox (2 variants, 1 hidden)
        if ($option_data['option_type'] == 'C') {
            $option_data['variants'] = array_slice($option_data['variants'], 0, 1); // only 1 variant should be here
            reset($option_data['variants']);
            $_k = key($option_data['variants']);
            $option_data['variants'][$_k]['position'] = 1; // checked variant
            $v_id = db_get_field("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i AND position = 0", $option_id);
            $option_data['variants'][] = array ( // unchecked variant
                'position' => 0,
                'variant_id' => $v_id
            );
        }

        $variant_images = array();
        foreach ($option_data['variants'] as $k => $v) {
            if ((!isset($v['variant_name']) || $v['variant_name'] == '') && $option_data['option_type'] != 'C') {
                continue;
            }

            if ($action == 'create') {
                unset($v['variant_id']);
            }

            // Update product options variants
            if (isset($v['modifier'])) {
                $v['modifier'] = floatval($v['modifier']);
                if (floatval($v['modifier']) > 0) {
                    $v['modifier'] = '+' . $v['modifier'];
                }
            }

            if (isset($v['weight_modifier'])) {
                $v['weight_modifier'] = floatval($v['weight_modifier']);
                if (floatval($v['weight_modifier']) > 0) {
                    $v['weight_modifier'] = '+' . $v['weight_modifier'];
                }
            }

            $v['option_id'] = $option_id;

            if (empty($v['variant_id']) || (!empty($v['variant_id']) && !db_get_field("SELECT variant_id FROM ?:product_option_variants WHERE variant_id = ?i", $v['variant_id']))) {
                $v['variant_id'] = db_query("INSERT INTO ?:product_option_variants ?e", $v);
                foreach (fn_get_translation_languages() as $v['lang_code'] => $_v) {
                    db_query("INSERT INTO ?:product_option_variants_descriptions ?e", $v);
                }
            } else {
                db_query("UPDATE ?:product_option_variants SET ?u WHERE variant_id = ?i", $v, $v['variant_id']);
                db_query("UPDATE ?:product_option_variants_descriptions SET ?u WHERE variant_id = ?i AND lang_code = ?s", $v, $v['variant_id'], $lang_code);
            }

            $var_ids[] = $v['variant_id'];

            if ($option_data['option_type'] == 'C') {
                fn_delete_image_pairs($v['variant_id'], 'variant_image'); // force deletion of variant image for "checkbox" option
            } else {
                $variant_images[$k] = $v['variant_id'];
            }
        }

        if ($option_data['option_type'] != 'C' && !empty($variant_images)) {
            fn_attach_image_pairs('variant_image', 'variant_image', 0, $lang_code, $variant_images);
        }

        // Delete obsolete variants
        $condition = !empty($var_ids) ? db_quote('AND variant_id NOT IN (?n)', $var_ids) : '';
        $deleted_variants = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i $condition", $option_id, $var_ids);
        if (!empty($deleted_variants)) {
            db_query("DELETE FROM ?:product_option_variants WHERE variant_id IN (?n)", $deleted_variants);
            db_query("DELETE FROM ?:product_option_variants_descriptions WHERE variant_id IN (?n)", $deleted_variants);
            foreach ($deleted_variants as $v_id) {
                fn_delete_image_pairs($v_id, 'variant_image');
            }
        }
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        // Rebuild exceptions
        if (!empty($create) && !empty($option_data['product_id'])) {
            fn_recalculate_exceptions($option_data['product_id']);
        }
    }

    /**
     * Update product option (running after fn_update_product_option() function)
     *
     * @param array  $option_data      Array with option data
     * @param int    $option_id        Option identifier
     * @param array  $deleted_variants Array with deleted variants ids
     * @param string $lang_code        Language code to add/update option for
     */
    fn_set_hook('update_product_option_post', $option_data, $option_id, $deleted_variants, $lang_code);

    return $option_id;
}

function fn_convert_weight($weight)
{
    /**
     * Change weight before converting
     *
     * @param float $weight Weight for converting
     */
    fn_set_hook('convert_weight_pre', $weight);

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if (Registry::get('config.localization.weight_unit')) {
            $g = Registry::get('settings.General.weight_symbol_grams');
            $weight = $weight * Registry::get('config.localization.weight_unit') / $g;
        }
    }
    $result = sprintf('%01.2f', $weight);

    /**
     * Change the converted weight
     *
     * @param float $result Converted weight
     * @param float $weight Weight for converting
     */
    fn_set_hook('convert_weight_post', $result, $weight);

    return $result;
}

/**
 * Convert price from particular currency to base currency
 *
 * @param  float $price         Currency
 * @param  str   $currency_code Currency code
 * @return float Converted currencty
 */
function fn_convert_price($price, $currency_code = CART_PRIMARY_CURRENCY)
{
    /**
     * Change price before converting
     *
     * @param float $price         Price for converting
     * @param str   $currency_code Price currency code
     */
    fn_set_hook('convert_price_pre', $price, $currency_code);

    $currencies = Registry::get('currencies');
    $result = $price * $currencies[$currency_code]['coefficient'];

    /**
     * Change the converted price
     *
     * @param float $result        Converted price
     * @param float $price         Price for converting
     * @param str   $currency_code Price currency code
     */
    fn_set_hook('convert_price_post', $result, $price, $currency_code);

    return $result;
}

function fn_get_products_sorting()
{
    $sorting = array(
        'null' => array('description' => __('none'), 'default_order' => 'asc', 'desc' => false),
        'timestamp' => array('description' => __('date'), 'default_order' => 'desc'),
        'position' => array('description' => __('default'), 'default_order' => 'asc'),
        'product' => array('description' => __('name'), 'default_order' => 'asc'),
        'price' => array('description' => __('price'), 'default_order' => 'asc'),
        'popularity' => array('description' => __('popularity'), 'default_order' => 'desc')
    );

    /**
     * Change products sortings
     *
     * @param array   $sorting     Sortings
     * @param boolean $simple_mode Flag that defines if products sortings should be returned as simple titles list
     */
    fn_set_hook('products_sorting', $sorting, $simple_mode);

    return $sorting;
}

function fn_get_products_sorting_orders()
{
    $result = array('asc', 'desc');

    /**
     * Change products sorting orders
     *
     * @param array $result Sorting orders
     */
    fn_set_hook('get_products_sorting_orders', $result);

    return $result;
}

function fn_get_products_views($simple_mode = true, $active = false)
{
    /**
     * Change params for getting product views
     *
     * @param boolean $simple_mode Flag that defines is product views should be returned in simple mode
     * @param boolean $active      Flag that defines if only active views should be returned
     */
    fn_set_hook('get_products_views_pre', $simple_mode, $active);

    $active_views = Registry::get('settings.Appearance.default_products_view_templates');
    if (!is_array($active_views)) {
        parse_str($active_views, $active_views);
    }

    if (!array_key_exists(Registry::get('settings.Appearance.default_products_view'), $active_views)) {
        $active_views[Registry::get('settings.Appearance.default_products_view')] = 'Y';
    }

    /*if (Registry::isExist('products_views') == true && AREA != 'A') {
        $products_views = Registry::get('products_views');

        foreach ($products_views as &$view) {
            $view['title'] = __($view['title']);
        }

        if ($simple_mode) {
            $products_views = Registry::get('products_views');

            foreach ($products_views as $key => $value) {
                $products_views[$key] = $value['title'];
            }
        }

        if ($active) {
            $products_views = array_intersect_key($products_views, $active_layouts);
        }

        return $products_views;
    }*/

    $products_views = array();

    $theme = Themes::areaFactory('C');

    // Get all available product_list_templates dirs
    $dir_params = array(
        'dir' => 'templates/blocks/product_list_templates',
        'get_dirs' => false,
        'get_files' => true,
        'extension' => '.tpl'
    );
    $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE);

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if ($data['status'] == 'A') {
            $dir_params['dir'] = "templates/addons/{$addon_name}/blocks/product_list_templates";
            $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);
        }
    }

    // Scan received directories and fill the "views" array
    foreach ($view_templates as $dir => $templates) {
        foreach ($templates as $file_name => $file_info) {
            $template_description = fn_get_file_description($file_info[Themes::PATH_ABSOLUTE], 'template-description', true);
            $_title = fn_basename($file_name, '.tpl');
            $template_path = str_replace(
                Themes::factory($file_info['theme'])->getThemePath() . '/templates/',
                '',
                $file_info[Themes::PATH_ABSOLUTE]
            );
            $products_views[$_title] = array(
                'template' => $template_path,
                'title' => empty($template_description) ? $_title : $template_description,
                'active' => array_key_exists($_title, $active_views)
            );
        }
    }

    //Registry::set('products_views',  $products_views);

    foreach ($products_views as &$view) {
        $view['title'] = __($view['title']);
    }

    if ($simple_mode) {
        foreach ($products_views as $key => $value) {
            $products_views[$key] = $value['title'];
        }
    }

    if ($active) {
        $products_views = array_intersect_key($products_views, $active_views);
    }

    /**
     * Change product views
     *
     * @param array   $products_views Array of products views
     * @param boolean $simple_mode    Flag that defines is product views should be returned in simple mode
     * @param boolean $active         Flag that defines if only active views should be returned
     */
    fn_set_hook('get_products_views_post', $products_views, $simple_mode, $active);

    return $products_views;
}

function fn_get_products_layout($params)
{
    static $result = null;

    // Function returns incorrect value when called more than once, this is a simple workaround.
    if ($result !== null) {
        return $result;
    }

    /**
     * Change params for getting products layout
     *
     * @param array $params Params for getting products layout
     */
    fn_set_hook('get_products_layout_pre', $params);

    if (!isset(Tygh::$app['session']['products_layout'])) {
        Tygh::$app['session']['products_layout'] = Registry::get('settings.Appearance.save_selected_view') == 'Y' ? array() : '';
    }

    $active_views = fn_get_products_views(false, true);
    $default_view = Registry::get('settings.Appearance.default_products_view');

    if (!empty($params['category_id'])) {
        $_layout = db_get_row(
            "SELECT default_view, selected_views FROM ?:categories WHERE category_id = ?i",
            $params['category_id']
        );
        $category_default_view = $_layout['default_view'];
        $category_views = unserialize($_layout['selected_views']);
        if (!empty($category_views)) {
            if (!empty($category_default_view)) {
                $default_view = $category_default_view;
            }
            $active_views = $category_views;
        }
        $ext_id = $params['category_id'];
    } else {
        $ext_id = 'search';
    }

    if (!empty($params['layout'])) {
        $layout = $params['layout'];
    } elseif (Registry::get('settings.Appearance.save_selected_view') == 'Y' && !empty(Tygh::$app['session']['products_layout'][$ext_id])) {
        $layout = Tygh::$app['session']['products_layout'][$ext_id];
    } elseif (Registry::get('settings.Appearance.save_selected_view') == 'N' && !empty(Tygh::$app['session']['products_layout'])) {
        $layout = Tygh::$app['session']['products_layout'];
    }

    $selected_view = (!empty($layout) && !empty($active_views[$layout])) ? $layout : $default_view;

    /**
     * Change selected layout
     *
     * @param array $selected_view Selected layout
     * @param array $params        Params for getting products layout
     */
    fn_set_hook('get_products_layout_post', $selected_view, $params);

    if (!empty($params['layout']) && $params['layout'] == $selected_view) {
        if (Registry::get('settings.Appearance.save_selected_view') == 'Y') {
            if (!is_array(Tygh::$app['session']['products_layout'])) {
                Tygh::$app['session']['products_layout'] = array();
            }
            Tygh::$app['session']['products_layout'][$ext_id] = $selected_view;
        } else {
            Tygh::$app['session']['products_layout'] = $selected_view;
        }
    }

    $result = $selected_view;

    return $selected_view;
}

function fn_get_categories_list($category_ids, $lang_code = CART_LANGUAGE)
{
    /**
     * Change params for getting categories list
     *
     * @param array  $category_ids Category identifier
     * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_categories_list_pre', $category_ids, $lang_code);

    static $max_categories = 10;
    $c_names = array();
    if (!empty($category_ids)) {
        $c_ids = fn_explode(',', $category_ids);
        $tr_c_ids = array_slice($c_ids, 0, $max_categories);
        $c_names = fn_get_category_name($tr_c_ids, $lang_code);
        if (sizeof($tr_c_ids) < sizeof($c_ids)) {
            $c_names[] = '... (' . sizeof($c_ids) . ')';
        }
    } else {
        $c_names[] = __('all_categories');
    }

    /**
     * Change categories list
     *
     * @param array  $c_names      Categories names list
     * @param array  $category_ids Category identifier
     * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_categories_list_post', $c_names, $category_ids, $lang_code);

    return $c_names;
}

function fn_get_allowed_options_combination($options, $variants, $string, $iteration, $exceptions, $inventory_combinations)
{
    /**
     * Changes parameters for getting allowed options combination
     *
     * @param array $options                Product options
     * @param array $variants               Product variants
     * @param array $string                 Array of combinations values
     * @param int   $iteration              Iteration level
     * @param array $exceptions             Options exceptions
     * @param array $inventory_combinations Inventory combinations
     */
    fn_set_hook('get_allowed_options_combination_pre', $options, $variants, $string, $iteration, $exceptions, $inventory_combinations);

    static $result = array();
    $combinations = array();
    foreach ($variants[$iteration] as $variant_id) {
        if (count($options) - 1 > $iteration) {
            $string[$iteration][$options[$iteration]] = $variant_id;
            list($_c, $is_result) = fn_get_allowed_options_combination($options, $variants, $string, $iteration + 1, $exceptions, $inventory_combinations);
            if ($is_result) {
                return array($_c, $is_result);
            }

            $combinations = array_merge($combinations, $_c);
            unset($string[$iteration]);
        } else {
            $_combination = array();
            if (!empty($string)) {
                foreach ($string as $val) {
                    foreach ($val as $opt => $var) {
                        $_combination[$opt] = $var;
                    }
                }
            }
            $_combination[$options[$iteration]] = $variant_id;
            $combinations[] = $_combination;

            foreach ($combinations as $combination) {
                $allowed = true;
                foreach ($exceptions as $exception) {
                    $res = array_diff($exception, $combination);

                    if (empty($res)) {
                        $allowed = false;
                        break;

                    } else {
                        foreach ($res as $option_id => $variant_id) {
                            if ($variant_id == OPTION_EXCEPTION_VARIANT_ANY || $variant_id == OPTION_EXCEPTION_VARIANT_NOTHING) {
                                unset($res[$option_id]);
                            }
                        }

                        if (empty($res)) {
                            $allowed = false;
                            break;
                        }
                    }
                }

                if ($allowed) {
                    $result = $combination;

                    if (empty($inventory_combinations)) {
                        return array($result, true);
                    } else {
                        foreach ($inventory_combinations as $_icombination) {
                            $_res = array_diff($_icombination, $combination);
                            if (empty($_res)) {
                                return array($result, true);
                            }
                        }
                    }
                }
            }

            $combinations = array();
        }
    }

    if ($iteration == 0) {
        return array($result, true);
    } else {
        return array($combinations, false);
    }
}

function fn_apply_options_rules($product)
{
    /**
     * Changes product data before applying product options rules
     *
     * @param array $product Product data
     */
    fn_set_hook('apply_options_rules_pre', $product);

    /*  Options type:
            P - simultaneous/parallel
            S - sequential
    */
    // Check for the options and exceptions types
    if (!isset($product['options_type']) || !isset($product['exceptions_type'])) {
        $product = array_merge($product, db_get_row('SELECT options_type, exceptions_type FROM ?:products WHERE product_id = ?i', $product['product_id']));
    }

    // Get the selected options or get the default options
    $product['selected_options'] = empty($product['selected_options']) ? array() : $product['selected_options'];
    $product['options_update'] = ($product['options_type'] == 'S') ? true : false;

    // Conver the selected options text to the utf8 format
    if (!empty($product['product_options'])) {
        foreach ($product['product_options'] as $id => $option) {
            if (!empty($option['value'])) {
                $product['product_options'][$id]['value'] = fn_unicode_to_utf8($option['value']);
            }
            if (!empty($product['selected_options'][$option['option_id']])) {
                $product['selected_options'][$option['option_id']] = fn_unicode_to_utf8($product['selected_options'][$option['option_id']]);
            }
        }
    }

    $selected_options = &$product['selected_options'];
    $changed_option = empty($product['changed_option']) ? true : false;

    $simultaneous = array();
    $next = 0;

    foreach ($product['product_options'] as $_id => $option) {
        if (!in_array($option['option_type'], array('I', 'T', 'F'))) {
            $simultaneous[$next] = $option['option_id'];
            $next = $option['option_id'];
        }

        if (!empty($option['value'])) {
            $selected_options[$option['option_id']] = $option['value'];
        }

        if (!$changed_option && $product['changed_option'] == $option['option_id']) {
            $changed_option = true;
        }

        if (!empty($selected_options[$option['option_id']]) && ($selected_options[$option['option_id']] == 'checked' || $selected_options[$option['option_id']] == 'unchecked') && $option['option_type'] == 'C') {
            foreach ($option['variants'] as $variant) {
                if (($variant['position'] == 0 && $selected_options[$option['option_id']] == 'unchecked') || ($variant['position'] == 1 && $selected_options[$option['option_id']] == 'checked')) {
                    $selected_options[$option['option_id']] = $variant['variant_id'];
                    if ($changed_option) {
                        $product['changed_option'] = $option['option_id'];
                    }
                }
            }
        }

        // Check, if the product has any options modifiers
        if (!empty($product['product_options'][$_id]['variants'])) {
            foreach ($product['product_options'][$_id]['variants'] as $variant) {
                if (!empty($variant['modifier']) && floatval($variant['modifier'])) {
                    $product['options_update'] = true;
                }
            }
        }
    }

    if (!empty($product['changed_option']) && empty($selected_options[$product['changed_option']]) && $product['options_type'] == 'S') {
        $product['changed_option'] = array_search($product['changed_option'], $simultaneous);
        if ($product['changed_option'] == 0) {
            unset($product['changed_option']);
            $reset = true;
            if (!empty($selected_options)) {
                foreach ($selected_options as $option_id => $variant_id) {
                    if (!isset($product['product_options'][$option_id]) || !in_array($product['product_options'][$option_id]['option_type'], array('I', 'T', 'F'))) {
                        unset($selected_options[$option_id]);
                    }
                }
            }
        }
    }

    if (empty($selected_options) && $product['options_type'] == 'P') {
        $selected_options = fn_get_default_product_options($product['product_id'], true, $product);
    }

    if (empty($product['changed_option']) && isset($reset)) {
        $product['changed_option'] = '';

    } elseif (empty($product['changed_option'])) {
        end($selected_options);
        $product['changed_option'] = key($selected_options);
    }

    if ($product['options_type'] == 'S') {
        empty($product['changed_option']) ? $allow = 1 : $allow = 0;

        foreach ($product['product_options'] as $_id => $option) {
            $product['product_options'][$_id]['disabled'] = false;

            if (in_array($option['option_type'], array('I', 'T', 'F'))) {
                continue;
            }

            $option_id = $option['option_id'];

            if ($allow >= 1) {
                unset($selected_options[$option_id]);
                $product['product_options'][$_id]['value'] = '';
            }

            if ($allow >= 2) {
                $product['product_options'][$_id]['disabled'] = true;
                continue;
            }

            if (empty($product['changed_option']) || (!empty($product['changed_option']) && $product['changed_option'] == $option_id) || $allow > 0) {
                $allow++;
            }
        }

        $product['simultaneous'] = $simultaneous;
    }

    // Restore selected values
    if (!empty($selected_options)) {
        foreach ($product['product_options'] as $_id => $option) {
            if (isset($selected_options[$option['option_id']])) {
                $product['product_options'][$_id]['value'] = $selected_options[$option['option_id']];
            }
        }
    }

    // Generate combination hash to get images. (Also, if the tracking with options, get amount and product code)
    $combination_hash = fn_generate_cart_id($product['product_id'], array('product_options' => $selected_options), true);
    $product['combination_hash'] = $combination_hash;

    // Change product code and amount
    if (!empty($product['tracking']) && $product['tracking'] == ProductTracking::TRACK_WITH_OPTIONS) {
        $product['hide_stock_info'] = false;
        if ($product['options_type'] == 'S') {
            foreach ($product['product_options'] as $option) {
                $option_id = $option['option_id'];
                if ($option['inventory'] == 'Y' && empty($product['selected_options'][$option_id])) {
                    $product['hide_stock_info'] = true;

                    break;
                }
            }
        }

        if (!$product['hide_stock_info']) {
            $combination = db_get_row("SELECT product_code, amount FROM ?:product_options_inventory WHERE combination_hash = ?i", $combination_hash);

            if (!empty($combination['product_code'])) {
                    $product['product_code'] = $combination['product_code'];
            }

            if (Registry::get('settings.General.inventory_tracking') == 'Y') {
                if (isset($combination['amount'])) {
                        $product['inventory_amount'] = $combination['amount'];
                } else {
                        $product['inventory_amount'] = $product['amount'] = 0;
                }
            }
        }
    }

    if (!$product['options_update']) {
        $product['options_update'] = db_get_field('SELECT COUNT(*) FROM ?:product_options_inventory WHERE product_id = ?i', $product['product_id']);
    }

    /**
     * Changes product data after applying product options rules
     *
     * @param array $product Product data
     */
    fn_set_hook('apply_options_rules_post', $product);

    return $product;
}

/**
 * Applying options exceptions rules for product
 *
 * @param array $product Product data
 * @param array $exceptions Options exceptions rules
 * @return array Product data with the corrected exceptions rules
 */
function fn_apply_exceptions_rules($product, $exceptions = array())
{
    /**
     * Exceptions type:
     *   A - Allowed
     *   F - Forbidden
     */

    /**
     * Changes product data before applying options exceptions rules
     *
     * @param array $product Product data
     */
    fn_set_hook('apply_exceptions_rules_pre', $product);

    if (empty($product['selected_options']) && $product['options_type'] == 'S') {
        return $product;
    }

    if (empty($exceptions)) {
        // Deprecated, but preserved for BC
        $exceptions = fn_get_product_exceptions($product['product_id'], true);

        if (empty($exceptions)) {
            return $product;
        }
    }

    $product['options_update'] = true;

    if (Registry::get('settings.General.exception_style') == 'warning') {
        $result = fn_is_allowed_options_exceptions($exceptions, $product['selected_options'], $product['options_type'], $product['exceptions_type']);

        if (!$result) {
            $product['show_exception_warning'] = 'Y';
        }

        return $product;
    }

    $options = array();
    $disabled = array();

    foreach ($exceptions as $exception_id => $exception) {

        if ($product['options_type'] == 'S') {
            if ($product['exceptions_type'] == 'A') {
                // Allowed sequential exceptions type
                $_selected = array();
                $selected_options = $product['selected_options'];
                array_pop($selected_options);

                // Add selected product option to current $exception[] on any exception variant
                foreach ($exception as $opt_id => $var_id) {
                    if ($var_id == OPTION_EXCEPTION_VARIANT_ANY && isset($selected_options[$opt_id])) {
                        $exception[$opt_id] = $product['selected_options'][$opt_id];
                    }
                }

                // Selection of the correct selected options variants
                foreach ($product['selected_options'] as $option_id => $variant_id) {
                    $_selected[$option_id] = $variant_id;

                    // Current options in $exception[] must intersect with selected
                    $intersect_elems = array_intersect_assoc($exception, $_selected);
                    // Options that have been selected by the user at this stage
                    $different_elems = array_diff($exception, $_selected);

                    if ($intersect_elems == $_selected && $different_elems) {
                        // Selecting the suitable variants, which are available for the option
                        foreach ($different_elems as $opt_id => $var_id) {
                            if ($var_id == OPTION_EXCEPTION_VARIANT_ANY) {
                                $options[$opt_id]['any'] = true;
                            } elseif ($var_id == OPTION_EXCEPTION_VARIANT_NOTHING) {
                                unset($options[$opt_id]);
                            } else {
                                // Correct option variant
                                $options[$opt_id][$var_id] = true;
                            }
                        }
                    }
                }
            } else {
                // Forbidden sequential exceptions type
                $_selected = array();

                foreach ($product['selected_options'] as $option_id => $variant_id) {
                    $disable = true;
                    $full = array();

                    $_selected[$option_id] = $variant_id;
                    $elms = array_diff($exception, $_selected);
                    $_exception = $exception;

                    if (!empty($elms)) {
                        foreach ($elms as $opt_id => $var_id) {
                            if ($var_id == OPTION_EXCEPTION_VARIANT_ANY) { // Any
                                $full[$opt_id] = $var_id;
                                if ($product['exceptions_type'] != 'A' || isset($_selected[$opt_id])) {
                                    unset($elms[$opt_id]);
                                    if ($product['exceptions_type'] != 'A') {
                                        unset($_exception[$opt_id]);
                                    }
                                }
                            } if ($var_id == OPTION_EXCEPTION_VARIANT_NOTHING) { // No
                                if ($product['exceptions_type'] == 'A' && count($elms) > 1) {
                                    unset($elms[$opt_id]);
                                }
                            } else {
                                $disable = false;
                            }
                        }
                    }

                    if ($disable && !empty($elms) && count($elms) != count($full)) {
                        $vars = array_diff($elms, $full);
                        $disable = false;
                        foreach ($vars as $var) {
                            if ($var != OPTION_EXCEPTION_VARIANT_ANY) {
                                $disable = true;
                            }
                        }
                    }

                    if ($disable && !empty($elms) && count($elms) != count($full)) {
                        foreach ($elms as $opt_id => $var_id) {
                            $disabled[$opt_id] = true;
                        }
                    } elseif ($disable && !empty($full)) {
                        foreach ($full as $opt_id => $var_id) {
                            $options[$opt_id]['any'] = true;
                        }
                    } elseif (count($elms) == 1 && reset($elms) == OPTION_EXCEPTION_VARIANT_NOTHING) {
                        $disabled[key($elms)] = true;
                    } elseif (($product['exceptions_type'] == 'A' && count($elms) + count($_selected) != count($_exception)) || ($product['exceptions_type'] == 'F' && count($elms) != 1)) {
                        continue;
                    }

                    if (
                        !isset($product['simultaneous'][$option_id]) || !isset($elms[$product['simultaneous'][$option_id]])
                    ) {
                        continue;
                    }

                    $elms[$product['simultaneous'][$option_id]] = ($elms[$product['simultaneous'][$option_id]] == OPTION_EXCEPTION_VARIANT_ANY) ? 'any' : $elms[$product['simultaneous'][$option_id]];
                    if (isset($product['simultaneous'][$option_id]) && !empty($elms) && isset($elms[$product['simultaneous'][$option_id]])) {
                        $options[$product['simultaneous'][$option_id]][$elms[$product['simultaneous'][$option_id]]] = true;
                    }
                }
            }
        } else {
            // Parallel exceptions type
            $disable = true;
            $full = array();

            $elms = array_diff($exception, $product['selected_options']);

            if (!empty($elms)) {
                $elms_no_variants = array();
                foreach ($elms as $opt_id => $var_id) {
                    if ($var_id == OPTION_EXCEPTION_VARIANT_ANY) { // Any
                        $full[$opt_id] = $var_id;
                        unset($elms[$opt_id]);
                    } elseif ($var_id == OPTION_EXCEPTION_VARIANT_NOTHING) { // No
                        if ($product['exceptions_type'] == 'A') {
                            $elms_no_variants[] = $opt_id;
                        }
                    } else {
                        $disable = false;
                    }
                }
                if (count(array_unique($elms)) > 1) {
                    foreach ($elms_no_variants as $opt_id) {
                        unset($elms[$opt_id]);
                    }
                }
            }

            if ($disable) {
                if ($elms) {
                    foreach ($elms as $opt_id => $var_id) {
                        $disabled[$opt_id] = true;
                    }
                }
                if ($full && (!$elms || $product['exceptions_type'] == 'A')) {
                    foreach ($full as $opt_id => $var_id) {
                        $options[$opt_id]['any'] = true;
                    }
                }
            } elseif (count($elms) == 1) {
                $variant_id = reset($elms);
                $option_id = key($elms);
                if ($variant_id == OPTION_EXCEPTION_VARIANT_NOTHING) {
                    $disabled[$option_id] = true;
                } else {
                    $options[$option_id][$variant_id] = true;
                }
            }
        }
    }

    if ($product['exceptions_type'] == 'A' && $product['options_type'] == 'P') {
        foreach ($product['selected_options'] as $option_id => $variant_id) {
            $options[$option_id][$variant_id] = true;
        }
    }

    $first_elm = array();

    foreach ($product['product_options'] as $_id => &$option) {
        $option_id = $option['option_id'];
        $clear_variants = ($option['missing_variants_handling'] == 'H');

        if (!in_array($option['option_type'], array('I', 'T', 'F')) && empty($first_elm)) {
            $first_elm = $product['product_options'][$_id];
        }

        if (isset($disabled[$option_id])) {
            $option['disabled'] = true;
            $option['not_required'] = true;
        }

        if (($product['options_type'] == 'S' && $option['option_id'] == $first_elm['option_id']) || (in_array($option['option_type'], array('I', 'T', 'F')))) {
            continue;
        }

        if ($product['options_type'] == 'S' && $option['disabled']) {
            if ($clear_variants) {
                $option['variants'] = array();
            }

            continue;
        }

        // Exclude checkboxes
        if (!empty($option['variants']) && $option['option_type'] != 'C') {
            foreach ($option['variants'] as $variant_id => $variant) {
                if ($product['exceptions_type'] == 'A') {
                    // Allowed combinations
                    if (empty($options[$option_id][$variant_id]) && !isset($options[$option_id]['any'])) {
                        unset($option['variants'][$variant_id]);
                    }
                } else {
                    // Forbidden combinations
                    if (!empty($options[$option_id][$variant_id]) || isset($options[$option_id]['any'])) {
                        unset($option['variants'][$variant_id]);
                    }
                }
            }

            if (!in_array($option['value'], array_keys($option['variants']))) {
                $option['value'] = '';
            }
        }
    }

    // Correct selected options
    foreach ($product['product_options'] as $_id => &$option) {
        if (
            $product['options_type'] == 'P'
            && !in_array($option['option_type'], array('I', 'T', 'F'))
            && empty($option['value'])
            && empty($option['disabled'])
            && !empty($option['variants'])
        ) {
            $variant = reset($option['variants']);
            $option['value'] = $variant['variant_id'];
            $product['selected_options'][$option['option_id']] = $variant['variant_id'];
        }
    }

    /**
     * Changes product data after applying options exceptions rules
     *
     * @param array $product    Product data
     * @param array $exceptions Options exceptions
     */
    fn_set_hook('apply_exceptions_post', $product, $exceptions);

    return $product;
}

function fn_is_allowed_options_exceptions($exceptions, $options, $o_type = 'P', $e_type = 'F')
{
    /**
     * Changes parameters before checking allowed options exceptions
     *
     * @param array  $exceptions Options exceptions
     * @param array  $options    Product options
     * @param string $o_type     Option type
     * @param string $e_type     Exception type
     */
    fn_set_hook('is_allowed_options_exceptions_pre', $exceptions, $options, $o_type, $e_type);

    $result = null;

    foreach ($options as $option_id => $variant_id) {
        if (empty($variant_id)) {
            unset($options[$option_id]);
        }
    }

    if ($e_type != 'A' || !empty($options)) {
        $in_exception = false;
        foreach ($exceptions as $exception) {
            foreach ($options as $option_id => $variant_id) {
                if (!isset($exception[$option_id])) {
                    unset($options[$option_id]);
                }
            }

            if (count($exception) != count($options)) {
                continue;
            }

            $in_exception = true;
            $diff = array_diff($exception, $options);

            if (!empty($diff)) {
                foreach ($diff as $option_id => $variant_id) {
                    if ($variant_id == OPTION_EXCEPTION_VARIANT_ANY || ($e_type != 'A' && $variant_id == OPTION_EXCEPTION_VARIANT_NOTHING)) {
                        unset($diff[$option_id]);
                    }
                }
            }

            if (empty($diff) && $e_type == 'A') {
                $result = true;
                break;
            } elseif (empty($diff)) {
                $result = false;
                break;
            }
        }

        if (is_null($result) && $in_exception && $e_type == 'A') {
            $result = false;
        }
    }

    if (is_null($result)) {
        $result = true;
    }

    /**
     * Changes result of checking allowed options exceptions
     *
     * @param boolean $result     Result of checking options exceptions
     * @param array   $exceptions Options exceptions
     * @param array   $options    Product options
     * @param string  $o_type     Option type
     * @param string  $e_type     Exception type
     */
    fn_set_hook('is_allowed_options_exceptions_post', $result, $exceptions, $options, $o_type, $e_type);

    return $result;
}

/**
 * Checks if all selected product options are available now
 *
 * @param array $product Product data
 * @return bool true if all options are available, false otherwise
 */
function fn_is_allowed_options($product)
{
    if (empty($product['product_options'])) {
        return true;
    }

    $options = fn_get_product_options($product['product_id']);
    foreach ($product['product_options'] as $option_id => $variant_id) {
        if (empty($variant_id)) {
            // Forbidden combination in action
            continue;
        }

        if (!isset($options[$option_id]) || (!empty($options[$option_id]['variants']) && !isset($options[$option_id]['variants'][$variant_id]))) {
            return false;
        }
    }

    return true;
}

function fn_get_product_details_views($get_default = 'default')
{
    $product_details_views = array();

    /**
     * Changes params for getting product details views or adds additional views
     *
     * @param array  $product_details_views Array for product details views templates
     * @param string $get_default           Type of default layout
     */
    fn_set_hook('get_product_details_views_pre', $product_details_views, $get_default);

    if ($get_default == 'category') {

        $parent_layout = Registry::get('settings.Appearance.default_product_details_view');
        $product_details_views['default'] = __('default_product_details_view', array(
            '[default]' => __($parent_layout)
        ));

    } elseif ($get_default != 'default') {

        $parent_layout = db_get_field("SELECT c.product_details_view FROM ?:products_categories as pc LEFT JOIN ?:categories as c ON pc.category_id = c.category_id WHERE pc.product_id = ?i AND pc.link_type = 'M'", $get_default);
        if (empty($parent_layout) || $parent_layout == 'default') {
            $parent_layout = Registry::get('settings.Appearance.default_product_details_view');
        }

        $product_details_views['default'] = __('default_product_details_view', array(
            '[default]' => __($parent_layout)
        ));
    }

    $theme = Themes::areaFactory('C');

    // Get all available product_templates dirs
    $dir_params = array(
        'dir' => 'templates/blocks/product_templates',
        'get_dirs' => false,
        'get_files' => true,
        'extension' => '.tpl'
    );
    $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE);

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if ($data['status'] == 'A') {
            $dir_params['dir'] = "templates/addons/{$addon_name}/blocks/product_templates";
            $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);
        }
    }

    // Scan received directories and fill the "views" array
    foreach ($view_templates as $dir => $templates) {
        foreach ($templates as $file_name => $file_info) {
            $template_description = fn_get_file_description($file_info[Themes::PATH_ABSOLUTE], 'template-description', true);
            $_title = fn_basename($file_name, '.tpl');
            $product_details_views[$_title] = empty($template_description) ? __($_title) : $template_description;
        }
    }

    /**
     * Changes product details views
     *
     * @param array  $product_details_views Product details views
     * @param string $get_default           Type of default layout
     */
    fn_set_hook('get_product_details_views_post', $product_details_views, $get_default);

    return $product_details_views;
}

function fn_get_customer_layout_theme_path()
{
    $company_id = null;
    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        $company_id = db_get_field("SELECT MIN(company_id) FROM ?:companies");
    }

    $theme_name = fn_get_theme_path('[theme]', 'C', $company_id);
    $theme_path = fn_get_theme_path('[themes]/[theme]', 'C', $company_id);

    return array($theme_path, $theme_name);
}

/**
 * @deprecated In favour of use fn_get_product_details_view function.
 * @since 4.3.1
 * @TODO: remove in 4.3.2
 */
function fn_get_product_details_layout($product_id)
{
    return fn_get_product_details_view($product_id);
}

function fn_get_product_details_view($product_id)
{
    /**
     * Changes params for getting product details layout
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('get_product_details_view_pre', $product_id);
    fn_set_hook('get_product_details_layout_pre', $product_id);
    $selected_view = Registry::get('settings.Appearance.default_product_details_view');
    if (!empty($product_id)) {
        $selected_view = db_get_field("SELECT details_layout FROM ?:products WHERE product_id = ?i", $product_id);
        if (empty($selected_view) || $selected_view == 'default') {
            $selected_view = db_get_field(
                "SELECT c.product_details_view" .
                " FROM ?:products_categories as pc" .
                " LEFT JOIN ?:categories as c" .
                " ON pc.category_id = c.category_id" .
                " WHERE pc.product_id = ?i AND pc.link_type = 'M'",
                $product_id
            );
        }
        if (empty($selected_view) || $selected_view == 'default') {
            $selected_view = Registry::get('settings.Appearance.default_product_details_view');
        }
    }

    $theme = Themes::areaFactory('C');

    // Search all available product_templates dirs
    if ($theme->getContentPath("templates/blocks/product_templates/{$selected_view}.tpl")) {
        $result = "blocks/product_templates/{$selected_view}.tpl";
    } else {
        foreach ((array) Registry::get('addons') as $addon_name => $data) {
            if ($data['status'] == 'A') {
                if ($theme->getContentPath(
                    "templates/addons/{$addon_name}/blocks/product_templates/{$selected_view}.tpl",
                    Themes::CONTENT_FILE, Themes::PATH_ABSOLUTE, Themes::USE_BASE
                )) {
                    $result = "addons/{$addon_name}/blocks/product_templates/{$selected_view}.tpl";
                    break;
                }
            }
        }
    }

    if (empty($result)) {
        $result = 'blocks/product_templates/default_template.tpl';
    }

    /**
     * Changes product details layout template
     *
     * @param string $result     Product layout template
     * @param int    $product_id Product identifier
     */
    fn_set_hook('get_product_details_view_post', $result, $product_id);
    fn_set_hook('get_product_details_layout_post', $result, $product_id);

    return $result;
}

function fn_clone_product($product_id)
{
    /**
     * Adds additional actions before product cloning
     *
     * @param int $product_id Original product identifier
     */
    fn_set_hook('clone_product_pre', $product_id);

    // Clone main data
    $data = db_get_row("SELECT * FROM ?:products WHERE product_id = ?i", $product_id);
    unset($data['product_id']);
    $data['status'] = 'D';
    $data['timestamp'] = $data['updated_timestamp'] = time();
    $pid = db_query("INSERT INTO ?:products ?e", $data);

    // Clone descriptions
    $data = db_get_array("SELECT * FROM ?:product_descriptions WHERE product_id = ?i", $product_id);
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        if ($v['lang_code'] == CART_LANGUAGE) {
            $orig_name = $v['product'];
            $new_name = $v['product'].' [CLONE]';
        }

        $v['product'] .= ' [CLONE]';
        db_query("INSERT INTO ?:product_descriptions ?e", $v);
    }

    // Clone prices
    $data = db_get_array("SELECT * FROM ?:product_prices WHERE product_id = ?i", $product_id);
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        unset($v['price_id']);
        db_query("INSERT INTO ?:product_prices ?e", $v);
    }

    // Clone categories links
    $data = db_get_array("SELECT * FROM ?:products_categories WHERE product_id = ?i", $product_id);
    $_cids = array();
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        db_query("INSERT INTO ?:products_categories ?e", $v);
        $_cids[] = $v['category_id'];
    }
    fn_update_product_count($_cids);

    // Clone product options
    fn_clone_product_options($product_id, $pid);

    // Clone global linked options
    $gl_options = db_get_fields("SELECT option_id FROM ?:product_global_option_links WHERE product_id = ?i", $product_id);
    if (!empty($gl_options)) {
        foreach ($gl_options as $v) {
            db_query("INSERT INTO ?:product_global_option_links (option_id, product_id) VALUES (?i, ?i)", $v, $pid);
        }
    }

    // Clone product features
    $data = db_get_array("SELECT * FROM ?:product_features_values WHERE product_id = ?i", $product_id);
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        db_query("INSERT INTO ?:product_features_values ?e", $v);
    }

    // Clone blocks
    Block::instance()->cloneDynamicObjectData('products', $product_id, $pid);

    // Clone tabs info
    ProductTabs::instance()->cloneStatuses($pid, $product_id);

    // Clone addons
    fn_set_hook('clone_product', $product_id, $pid);

    // Clone images
    fn_clone_image_pairs($pid, $product_id, 'product');

    // Clone product files
    fn_clone_product_files($product_id, $pid);

    /**
     * Adds additional actions after product cloning
     *
     * @param int    $product_id Original product identifier
     * @param int    $pid        Cloned product identifier
     * @param string $orig_name  Original product name
     * @param string $new_name   Cloned product name
     */
    fn_set_hook('clone_product_post', $product_id, $pid, $orig_name, $new_name);

    return array('product_id' => $pid, 'orig_name' => $orig_name, 'product' => $new_name);
}

/**
 * Updates product prices.
 *
 * @param int $product_id Product identifier.
 * @param array $product_data Array of product data.
 * @param int $company_id Company identifier.
 * @return array Modified <i>$product_data</i> array.
 */
function fn_update_product_prices($product_id, $product_data, $company_id = 0)
{
    $_product_data = $product_data;
    $skip_price_delete = false;
    // Update product prices
    if (isset($_product_data['price'])) {
        $_price = array (
            'price' => abs($_product_data['price']),
            'lower_limit' => 1,
        );

        if (!isset($_product_data['prices'])) {
            $_product_data['prices'][0] = $_price;
            $skip_price_delete = true;

        } else {
            unset($_product_data['prices'][0]);
            array_unshift($_product_data['prices'], $_price);
        }
    }

    if (!empty($_product_data['prices'])) {
        if (fn_allowed_for('ULTIMATE') && $company_id) {
            $table_name = '?:ult_product_prices';
            $condition = db_quote(' AND company_id = ?i', $company_id);
        } else {
            $table_name = '?:product_prices';
            $condition = '';
        }

        if (!$skip_price_delete) {
            db_query("DELETE FROM $table_name WHERE product_id = ?i $condition", $product_id);
        }

        foreach ($_product_data['prices'] as $v) {
            $v['type'] = !empty($v['type']) ? $v['type'] : 'A';
            $v['usergroup_id'] = !empty($v['usergroup_id']) ? $v['usergroup_id'] : 0;
            if ($v['lower_limit'] == 1 && $v['type'] == 'P' && $v['usergroup_id'] == 0) {
                fn_set_notification('W', __('warning'), __('cant_save_percentage_price'));
                continue;
            }
            if (!empty($v['lower_limit'])) {
                $v['product_id'] = $product_id;
                if (!empty($company_id)) {
                    $v['company_id'] = $company_id;
                }
                if ($v['type'] == 'P') {
                    $v['percentage_discount'] = ($v['price'] > 100) ? 100 : $v['price'];
                    $v['price'] = $_product_data['price'];
                }
                unset($v['type']);

                if (count($_product_data['prices']) == 1 && $skip_price_delete && empty($_product_data['create'])) {
                    $data = array(
                        'price' => $v['price']
                    );

                    db_query("UPDATE $table_name SET ?u WHERE product_id = ?i AND ((lower_limit = ?i AND usergroup_id = ?i) OR percentage_discount > ?i) ?p", $data, $v['product_id'], 1, 0, 0, $condition);
                } else {
                    db_query("REPLACE INTO $table_name ?e", $v);
                }
            }
        }
    }

    return $_product_data;
}

/**
 * Gets product prices.
 *
 * @param int $product_id Product identifier
 * @param array $product_data Array of product data. Result data will be saved in this variable.
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param int $company_id Company identifier.
 */
function fn_get_product_prices($product_id, &$product_data, $auth, $company_id = 0)
{
    if (fn_allowed_for('ULTIMATE') && $company_id) {
        $table_name = '?:ult_product_prices';
        $condition = db_quote(' AND prices.company_id = ?i', $company_id);
    } else {
        $table_name = '?:product_prices';
        $condition = '';
    }

    // For customer
    if (AREA == 'C') {
        $_prices = db_get_hash_multi_array("SELECT prices.product_id, prices.lower_limit, usergroup_id, prices.percentage_discount, IF(prices.percentage_discount = 0, prices.price, prices.price - (prices.price * prices.percentage_discount)/100) as price FROM $table_name prices WHERE prices.product_id = ?i $condition AND lower_limit > 1 AND prices.usergroup_id IN (?n) ORDER BY lower_limit", array('usergroup_id'), $product_id, array_merge(array(USERGROUP_ALL), $auth['usergroup_ids']));
        if (!fn_allowed_for('ULTIMATE:FREE')) {
            // If customer has usergroup and prices defined for this usergroup, get them
            if (!empty($auth['usergroup_ids'])) {
                foreach ($auth['usergroup_ids'] as $ug_id) {
                    if (!empty($_prices[$ug_id]) && sizeof($_prices[$ug_id]) > 0) {
                        if (empty($product_data['prices'])) {
                            $product_data['prices'] = $_prices[$ug_id];
                        } else {
                            foreach ($_prices[$ug_id] as $comp_data) {
                                $add_elm = true;
                                foreach ($product_data['prices'] as $price_id => $price_data) {
                                    if ($price_data['lower_limit'] == $comp_data['lower_limit']) {
                                        $add_elm = false;
                                        if ($price_data['price'] > $comp_data['price']) {
                                            $product_data['prices'][$price_id] = $comp_data;
                                        }
                                        break;
                                    }
                                }
                                if ($add_elm) {
                                    $product_data['prices'][] = $comp_data;
                                }
                            }
                        }
                    }
                }
                if (!empty($product_data['prices'])) {
                    $tmp = array();
                    foreach ($product_data['prices'] as $price_id => $price_data) {
                        $tmp[$price_id] = $price_data['lower_limit'];
                    }
                    array_multisort($tmp, SORT_ASC, $product_data['prices']);
                }
            }
        }

        // else, get prices for not members
        if (empty($product_data['prices']) && !empty($_prices[0]) && sizeof($_prices[0]) > 0) {
            $product_data['prices'] = $_prices[0];
        }
    // Other - get all
    } else {
        $product_data['prices'] = db_get_array("SELECT prices.product_id, prices.lower_limit, usergroup_id, prices.percentage_discount, IF(prices.percentage_discount = 0, prices.price, prices.price - (prices.price * prices.percentage_discount)/100) as price FROM $table_name prices WHERE product_id = ?i $condition ORDER BY usergroup_id, lower_limit", $product_id);
    }
}

//
// Copy product files
//
function fn_copy_product_files($file_id, $file, $product_id, $var_prefix = 'file')
{
    /**
     * Changes params before copying product files
     *
     * @param int    $file_id    File identifier
     * @param array  $file       File data
     * @param int    $product_id Product identifier
     * @param string $var_prefix Prefix of file variables
     */
    fn_set_hook('copy_product_files_pre', $file_id, $file, $product_id, $var_prefix);

    $filename = $product_id . '/' . $file['name'];

    $_data = array();

    list($_data[$var_prefix . '_size'], $_data[$var_prefix . '_path']) = Storage::instance('downloads')->put($filename, array(
        'file' => $file['path'],
        'overwrite' => true
    ));

    $_data[$var_prefix . '_path'] = fn_basename($_data[$var_prefix . '_path']);
    db_query('UPDATE ?:product_files SET ?u WHERE file_id = ?i', $_data, $file_id);

    /**
     * Adds additional actions after product files were copied
     *
     * @param int    $file_id    File identifier
     * @param array  $file       File data
     * @param int    $product_id Product identifier
     * @param string $var_prefix Prefix of file variables
     */
    fn_set_hook('copy_product_files_post', $file_id, $file, $product_id, $var_prefix);

    return true;
}

//
// Get products subscribers
//
function fn_get_product_subscribers($params, $items_per_page = 0)
{
    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'email' => '',
        'product_id' => 0,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    /**
     * Changes params for getting product subscribers
     *
     * @param array $params Search subscribers params
     */
    fn_set_hook('get_product_subscribers_pre', $params);

    // Init filter
    $params = LastView::instance()->update('subscribers', $params);

    $condition = '';
    $limit = '';

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $condition .= db_quote(" AND email LIKE ?l", "%" . trim($params['email']) . "%");
     }

    $sorting = db_sort($params, array('email' => 'email'), 'email', 'asc');

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_subscriptions WHERE product_id = ?i $condition", $params['product_id']);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $subscribers = db_get_hash_array("SELECT subscription_id as subscriber_id, email FROM ?:product_subscriptions WHERE product_id = ?i $condition $sorting $limit", 'subscriber_id', $params['product_id']);

    /**
     * Changes product subscribers
     *
     * @param int   $product_id  Product identifier
     * @param array $params      Search subscribers params
     * @param array $subscribers Array of subscribers
     */
    fn_set_hook('get_product_subscribers_post', $params, $subscribers);

    return array($subscribers, $params);
}

/**
 * Gets default products sorting params
 *
 * @return array Sorting params
 */
function fn_get_default_products_sorting()
{
    $params  = explode('-', Registry::get('settings.Appearance.default_products_sorting'));
    if (is_array($params) && count($params) == 2) {
        $sorting = array (
            'sort_by' => array_shift($params),
            'sort_order' => array_shift($params),
        );
    } else {
        $default_sorting = fn_get_products_sorting();
        $sort_by = current(array_keys($default_sorting));
        $sorting = array (
            'sort_by' => $sort_by,
            'sort_order' => $default_sorting[$sort_by]['default_order'],
        );
    }

    return $sorting;
}

/**
 * Gets products from feature comparison list
 *
 * @return array List of compared products
 */
function fn_get_comparison_products()
{
    $compared_products = array();

    if (!empty(Tygh::$app['session']['comparison_list'])) {
        $_products = db_get_hash_array("SELECT product_id, product FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code = ?s", 'product_id', Tygh::$app['session']['comparison_list'], CART_LANGUAGE);

        $params = array(
            'pid' => Tygh::$app['session']['comparison_list'],
        );

        list($products, $search) = fn_get_products($params);
        fn_gather_additional_products_data($products, array('get_icon' => true, 'get_detailed' => true, 'get_additional' => false, 'get_options'=> false));

        $_products = array();

        foreach ($products as $product) {
            $_products[$product['product_id']] = $product;
        }
        $products = $_products;
        unset($_products);

        foreach (Tygh::$app['session']['comparison_list'] as $k => $p_id) {
            if (empty($products[$p_id])) {
                unset(Tygh::$app['session']['comparison_list'][$k]);
                continue;
            }
            $compared_products[] = $products[$p_id];
        }
    }

    /**
     * Changes compared products
     *
     * @param array $compared_products List of compared products
     */
    fn_set_hook('get_comparison_products_post', $compared_products);

    return $compared_products;
}

/**
 * Physically deletes product files on disk
 *
 * @param int $file_id file ID to delete
 * @return boolean true on success, false - otherwise
 */
function fn_delete_product_files_path($file_ids)
{
    if (!empty($file_ids) && is_array($file_ids)) {
        $files_data = db_get_array("SELECT file_path, preview_path, product_id FROM ?:product_files WHERE file_id IN (?n)", $file_ids);

        foreach ($files_data as $file_data) {
            if (!empty($file_data['file_path'])) {
                Storage::instance('downloads')->delete($file_data['product_id'] . '/' . $file_data['file_path']);
            }
            if (!empty($file_data['preview_path'])) {
                Storage::instance('downloads')->delete($file_data['product_id'] . '/' . $file_data['preview_path']);
            }

            // delete empty directory
            $files = Storage::instance('downloads')->getList($file_data['product_id']);
            if (empty($files)) {
                Storage::instance('downloads')->deleteDir($file_data['product_id']);
            }

        }

        return true;
    }

    return false;
}

/**
 * Delete product files in folder
 *
 * @param int $folder_id folder ID to delete
 * @param int $product_id product ID to delete all files from it. Ignored if $folder_id is passed
 * @return boolean true on success, false - otherwise
 */
function fn_delete_product_file_folders($folder_id, $product_id = 0)
{
    if (empty($product_id) && !empty($folder_id)) {
        $product_id = db_get_field("SELECT product_id FROM ?:product_file_folders WHERE folder_id = ?i", $folder_id);
    } elseif (empty($folder_id) && empty($product_id)) {
        return false;
    }

    if (!fn_company_products_check($product_id, true)) {
        return false;
    }

    if (!empty($folder_id)) {
        $folder_ids = array($folder_id);
        $file_ids = db_get_fields("SELECT file_id FROM ?:product_files WHERE product_id = ?i AND folder_id = ?i", $product_id, $folder_id);
    } else {
        $folder_ids = db_get_fields("SELECT folder_id FROM ?:product_file_folders WHERE product_id = ?i", $product_id);
        $file_ids = db_get_fields("SELECT file_id FROM ?:product_files WHERE product_id = ?i AND folder_id IN (?n)", $product_id, $folder_ids);
    }

    if (!empty($file_ids) && fn_delete_product_files_path($file_ids) == false) {
        return false;
    }

    db_query("DELETE FROM ?:product_file_folders WHERE folder_id IN (?n)", $folder_ids);
    db_query("DELETE FROM ?:product_file_folder_descriptions WHERE folder_id IN (?n)", $folder_ids);

    db_query("DELETE FROM ?:product_files WHERE file_id IN (?n)", $file_ids);
    db_query("DELETE FROM ?:product_file_descriptions WHERE file_id IN (?n)", $file_ids);

    return true;
}

/**
 * Delete product files
 *
 * @param int $file_id file ID to delete
 * @param int $product_id product ID to delete all files from it. Ignored if $file_id is passed
 * @return boolean true on success, false - otherwise
 */
function fn_delete_product_files($file_id, $product_id = 0)
{
    if (empty($product_id) && !empty($file_id)) {
        $product_id = db_get_field("SELECT product_id FROM ?:product_files WHERE file_id = ?i", $file_id);
    } elseif (empty($folder_id) && empty($product_id)) {
        return false;
    }

    if (!fn_company_products_check($product_id, true)) {
        return false;
    }

    if (!empty($file_id)) {
        $file_ids = array($file_id);
    } else {
        $file_ids = db_get_fields("SELECT file_id FROM ?:product_files WHERE product_id = ?i", $product_id);
    }

    if (fn_delete_product_files_path($file_ids) == false) {
        return false;
    }

    db_query("DELETE FROM ?:product_files WHERE file_id IN (?n)", $file_ids);
    db_query("DELETE FROM ?:product_file_descriptions WHERE file_id IN (?n)", $file_ids);

    return true;
}

/**
 * Update product folder
 *
 * @param array $product_file_fodler folder data
 * @param int $folder_id folder ID for update, if empty - new folder will be created
 * @param string $lang_code language code to update folder description
 * @return int folder ID
 */

function fn_update_product_file_folder($product_file_folder, $folder_id, $lang_code = DESCR_SL)
{
    if (!fn_company_products_check($product_file_folder['product_id'], true)) {
        return false;
    }

    if (empty($folder_id)) {

        $product_file_folder['folder_id'] = $folder_id = db_query('INSERT INTO ?:product_file_folders ?e', $product_file_folder);

        foreach (fn_get_translation_languages() as $product_file_folder['lang_code'] => $v) {
            db_query('INSERT INTO ?:product_file_folder_descriptions ?e', $product_file_folder);
        }

    } else {
        db_query('UPDATE ?:product_file_folders SET ?u WHERE folder_id = ?i', $product_file_folder, $folder_id);
        db_query('UPDATE ?:product_file_folder_descriptions SET ?u WHERE folder_id = ?i AND lang_code = ?s', $product_file_folder, $folder_id, $lang_code);
    }

    return $folder_id;
}

/**
 * Update product file
 *
 * @param array $product_file file data
 * @param int $file_id file ID for update, if empty - new file will be created
 * @param string $lang_code language code to update file description
 * @return boolean true on success, false - otherwise
 */
function fn_update_product_file($product_file, $file_id, $lang_code = DESCR_SL)
{
    if (!fn_company_products_check($product_file['product_id'], true)) {
        return false;
    }

    $uploaded_data = fn_filter_uploaded_data('base_file');
    $uploaded_preview_data = fn_filter_uploaded_data('file_preview');

    $delete_preview = (isset($product_file['delete_preview']) && $product_file['delete_preview'] == 'Y');

    if (!empty($file_id) || !empty($uploaded_data[$file_id])) {

        db_query("UPDATE ?:products SET is_edp = 'Y' WHERE product_id = ?i", $product_file['product_id']);

        if (!empty($uploaded_data[$file_id])) {
            $product_file['file_name'] = empty($product_file['file_name'])
                ? $uploaded_data[$file_id]['name']
                : $product_file['file_name'];
        }

        // Remove old file before uploading a new one
        if (!empty($file_id)) {
            $dir = $product_file['product_id'];
            $old_file = db_get_row(
                'SELECT file_path, preview_path FROM ?:product_files WHERE product_id = ?i AND file_id = ?i',
                $product_file['product_id'], $file_id
            );

            if (!empty($uploaded_data) && !empty($old_file['file_path'])) {
                Storage::instance('downloads')->delete($dir . '/' . $old_file['file_path']);
            }

            // Delete preview file if deletion is forced or new preview is uploaded
            if ($delete_preview
                ||
                (!empty($uploaded_preview_data) && !empty($old_file['preview_path']))
            ) {
                Storage::instance('downloads')->delete($dir . '/' . $old_file['preview_path']);
            }
        }

        if ($delete_preview) {
            $product_file['preview_path'] = '';
        }

        // Update file data
        if (empty($file_id)) {
            $product_file['file_id'] = $file_id = db_query('INSERT INTO ?:product_files ?e', $product_file);

            foreach (fn_get_translation_languages() as $product_file['lang_code'] => $v) {
                db_query('INSERT INTO ?:product_file_descriptions ?e', $product_file);
            }

            $uploaded_id = 0;
        } else {

            db_query('UPDATE ?:product_files SET ?u WHERE file_id = ?i', $product_file, $file_id);
            db_query('UPDATE ?:product_file_descriptions SET ?u WHERE file_id = ?i AND lang_code = ?s', $product_file, $file_id, $lang_code);

            $uploaded_id = $file_id;
        }

        // Copy base file
        if (!empty($uploaded_data[$uploaded_id])) {
            fn_copy_product_files($file_id, $uploaded_data[$uploaded_id], $product_file['product_id']);
        }

        // Copy preview file
        if (!$delete_preview && !empty($uploaded_preview_data[$uploaded_id])) {
            fn_copy_product_files($file_id, $uploaded_preview_data[$uploaded_id], $product_file['product_id'], 'preview');
        }
    }

    return $file_id;
}

/**
 * Clone product folders
 *
 * @param int $source_id source product ID
 * @param int $target_id target product ID
 * @return associative array with the old folder IDs as keys and the new folder IDs as values
 */
function fn_clone_product_file_folders($source_id, $target_id)
{
    $data = db_get_array("SELECT * FROM ?:product_file_folders WHERE product_id = ?i", $source_id);
    $new_folder_ids = array();
    if (!empty($data)) {
        foreach ($data as $v) {
            $folder_descr = db_get_array("SELECT * FROM ?:product_file_folder_descriptions WHERE folder_id = ?i", $v['folder_id']);

            $v['product_id'] = $target_id;
            $old_folder_id = $v['folder_id'];
            unset($v['folder_id']);

            $new_folder_ids[$old_folder_id] = $new_folder_id = db_query("INSERT INTO ?:product_file_folders ?e", $v);

            foreach ($folder_descr as $key => $descr) {
                $descr['folder_id'] = $new_folder_id;
                db_query("INSERT INTO ?:product_file_folder_descriptions ?e", $descr);
            }
        }
    }

    return $new_folder_ids;
}

/**
 * Clone product files
 *
 * @param int $source_id source product ID
 * @param int $target_id target product ID
 * @return boolean true on success, false - otherwise
 */
function fn_clone_product_files($source_id, $target_id)
{
    $data = db_get_array("SELECT * FROM ?:product_files WHERE product_id = ?i", $source_id);

    $new_folder_ids = fn_clone_product_file_folders($source_id, $target_id);

    if (!empty($data)) {
        foreach ($data as $v) {
            $file_descr = db_get_array("SELECT * FROM ?:product_file_descriptions WHERE file_id = ?i", $v['file_id']);

            $v['product_id'] = $target_id;
            unset($v['file_id']);

            // set new folder id
            if (!empty($v['folder_id'])) {
                $v['folder_id'] = $new_folder_ids[$v['folder_id']];
            }

            $new_file_id = db_query("INSERT INTO ?:product_files ?e", $v);

            foreach ($file_descr as $key => $descr) {
                $descr['file_id'] = $new_file_id;
                db_query("INSERT INTO ?:product_file_descriptions ?e", $descr);
            }

        }

        Storage::instance('downloads')->copy($source_id, $target_id);

        return true;
    }

    return false;
}

/**
 * Download product file
 *
 * @param int $file_id file ID
 * @param boolean $is_preview flag indicates that we download file itself or just preview
 * @param string $ekey temporary key to download file from customer area
 * @param string $area current working area
 * @return file starts to download on success, boolean false in case of fail
 */
function fn_get_product_file($file_id, $is_preview = false, $ekey = '', $area = AREA)
{
    if (!empty($file_id)) {
        $column = $is_preview ? 'preview_path' : 'file_path';
        $file_data = db_get_row("SELECT $column, product_id FROM ?:product_files WHERE file_id = ?i", $file_id);

        if (fn_allowed_for('MULTIVENDOR') && $area == 'A' && !fn_company_products_check($file_data['product_id'], true)) {
            return false;
        }

        if (!empty($ekey)) {

            $ekey_info = fn_get_product_edp_info($file_data['product_id'], $ekey);

            if (empty($ekey_info) || $ekey_info['file_id'] != $file_id) {
                return false;
            }

            // Increase downloads for this file
            $max_downloads = db_get_field("SELECT max_downloads FROM ?:product_files WHERE file_id = ?i", $file_id);
            $file_downloads = db_get_field("SELECT downloads FROM ?:product_file_ekeys WHERE ekey = ?s AND file_id = ?i", $ekey, $file_id);

            if (!empty($max_downloads)) {
                if ($file_downloads >= $max_downloads) {
                    return false;
                }
            }

            db_query('UPDATE ?:product_file_ekeys SET ?u WHERE file_id = ?i AND product_id = ?i AND order_id = ?i', array('downloads' => $file_downloads + 1), $file_id, $file_data['product_id'], $ekey_info['order_id']);
        }

        Storage::instance('downloads')->get($file_data['product_id'] . '/' . $file_data[$column]);
    }

    return false;
}

/**
 * Prepares product quick view data
 *
 * @param array $params Parameteres for gathering additional quick view data
 * @return boolean Always true
 */
 function fn_prepare_product_quick_view($params)
 {
    if (!empty($params['prev_url'])) {
        Tygh::$app['view']->assign('redirect_url', $params['prev_url']);
    }

    /**
     * Additional actions for product quick view
     *
     * @param array $_REQUEST Request parameters
     */
    fn_set_hook('prepare_product_quick_view', $_REQUEST);

    return true;
}

function fn_get_product_pagination_steps($cols, $products_per_page)
{
    $min_range = $cols * 4;
    $max_ranges = 4;
    $steps = array();

    for ($i = 0; $i < $max_ranges; $i++) {
        $steps[] = $min_range;
        $min_range = $min_range * 2;
    }

    $steps[] = (int) $products_per_page;

    $steps = array_unique($steps);

    sort($steps, SORT_NUMERIC);

    return $steps;
}

function fn_get_product_option_data($option_id, $product_id, $lang_code = DESCR_SL)
{
    $extra_variant_fields = '';

    $fields = "a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment";
    $join = db_quote(" LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s"
        . " LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id", $lang_code);
    $condition = db_quote("a.option_id = ?i AND a.product_id = ?i", $option_id, $product_id);

    /**
     * Changes params before option data selecting
     *
     * @param int    $option_id            Option identifier
     * @param int    $product_id           Product identifier
     * @param string $fields               Fields to be selected
     * @param string $condition            String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join                 String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $extra_variant_fields Additional variant fields to be selected
     * @param string $lang_code            2-letters language code
     */
    fn_set_hook('get_product_option_data_pre', $option_id, $product_id, $fields, $condition, $join, $extra_variant_fields, $lang_code);

    $opt = db_get_row(
        "SELECT " . $fields
        . " FROM ?:product_options as a" . $join
        . " WHERE " . $condition
        . " ORDER BY a.position"
    );

    if (!empty($opt)) {
        $_cond = ($opt['option_type'] == 'C') ? ' AND a.position = 1' : '';

        $join = '';
        if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
            $extra_variant_fields .= 'IF(shared_option_variants.variant_id IS NOT NULL, shared_option_variants.modifier, a.modifier) as modifier, ';
            $extra_variant_fields .= 'IF(shared_option_variants.variant_id IS NOT NULL, shared_option_variants.modifier_type, a.modifier_type) as modifier_type, ';
            $join .= db_quote(' LEFT JOIN ?:ult_product_option_variants shared_option_variants ON shared_option_variants.variant_id = a.variant_id AND shared_option_variants.company_id = ?i', Registry::get('runtime.company_id'));
        }

        $opt['variants'] = db_get_hash_array("SELECT a.variant_id, a.position, a.modifier, a.modifier_type, a.weight_modifier, a.weight_modifier_type, a.status, $extra_variant_fields b.variant_name FROM ?:product_option_variants as a LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s $join WHERE a.option_id = ?i $_cond ORDER BY a.position", 'variant_id', $lang_code, $option_id);

        if (!empty($opt['variants'])) {
            foreach ($opt['variants'] as $k => $v) {
                $opt['variants'][$k]['image_pair'] = fn_get_image_pairs($v['variant_id'], 'variant_image', 'V', true, true, $lang_code);
            }
        }
    }

    /**
     * Changes option data
     *
     * @param array  $opt        Option data
     * @param int    $product_id Product identifier
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_option_data_post', $opt, $product_id, $lang_code);

    return $opt;
}

/**
 * Product fields for multi update
 *
 * @return array Product fields
 */
function fn_get_product_fields()
{
    $fields = array(
        array(
            'name' => '[data][popularity]',
            'text' => __('popularity')
        ),
        array(
            'name' => '[data][status]',
            'text' => __('status'),
            'disabled' => 'Y'
        ),
        array(
            'name' => '[data][product]',
            'text' => __('product_name'),
            'disabled' => 'Y'
        ),
        array(
            'name' => '[data][price]',
            'text' => __('price')
        ),
        array(
            'name' => '[data][list_price]',
            'text' => __('list_price')
        ),
        array(
            'name' => '[data][short_description]',
            'text' => __('short_description')
        ),
        array(
            'name' => '[data][promo_text]',
            'text' => __('promo_text')
        ),
        array(
            'name' => '[categories]',
            'text' => __('categories')
        ),
        array(
            'name' => '[data][full_description]',
            'text' => __('full_description')
        ),
        array(
            'name' => '[data][search_words]',
            'text' => __('search_words')
        ),
        array(
            'name' => '[data][meta_keywords]',
            'text' => __('meta_keywords')
        ),
        array(
            'name' => '[data][meta_description]',
            'text' => __('meta_description')
        ),
        array(
            'name' => '[main_pair]',
            'text' => __('image_pair')
        ),
        array(
            'name' => '[data][min_qty]',
            'text' => __('min_order_qty')
        ),
        array(
            'name' => '[data][max_qty]',
            'text' => __('max_order_qty')
        ),
        array(
            'name' => '[data][qty_step]',
            'text' => __('quantity_step')
        ),
        array(
            'name' => '[data][list_qty_count]',
            'text' => __('list_quantity_count')
        ),
        array(
            'name' => '[data][product_code]',
            'text' => __('sku')
        ),
        array(
            'name' => '[data][weight]',
            'text' => __('weight')
        ),
        array(
            'name' => '[data][shipping_freight]',
            'text' => __('shipping_freight')
        ),
        array(
            'name' => '[data][free_shipping]',
            'text' => __('free_shipping')
        ),
        array(
            'name' => '[data][zero_price_action]',
            'text' => __('zero_price_action')
        ),
        array(
            'name' => '[data][taxes]',
            'text' => __('taxes')
        ),
        array(
            'name' => '[data][features]',
            'text' => __('features')
        ),
        array(
            'name' => '[data][page_title]',
            'text' => __('page_title')
        ),
        array(
            'name' => '[data][timestamp]',
            'text' => __('creation_date')
        ),
        array(
            'name' => '[data][amount]',
            'text' => __('quantity')
        ),
        array(
            'name' => '[data][avail_since]',
            'text' => __('available_since')
        ),
        array(
            'name' => '[data][out_of_stock_actions]',
            'text' => __('out_of_stock_actions')
        ),
        array(
            'name' => '[data][details_layout]',
            'text' => __('product_details_view')
        ),
        array(
            'name' => '[data][min_items_in_box]',
            'text' => __('minimum_items_in_box')
        ),
        array(
            'name' => '[data][max_items_in_box]',
            'text' => __('maximum_items_in_box')
        ),
        array(
            'name' => '[data][box_length]',
            'text' => __('box_length')
        ),
        array(
            'name' => '[data][box_width]',
            'text' => __('box_width')
        ),
        array(
            'name' => '[data][box_height]',
            'text' => __('box_height')
        ),
    );

    if (Registry::get('settings.General.enable_edp') == 'Y') {
        $fields[] = array(
            'name' => '[data][is_edp]',
            'text' => __('downloadable')
        );
        $fields[] = array(
            'name' => '[data][edp_shipping]',
            'text' => __('edp_enable_shipping')
        );
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if (Registry::get('config.tweaks.disable_localizations') == false) {
            $fields[] =  array(
                'name' => '[data][localization]',
                'text' => __('localization')
            );
        }

        $fields[] =  array(
            'name' => '[data][usergroup_ids]',
            'text' => __('usergroups')
        );
    }

    if (Registry::get('settings.General.inventory_tracking') == "Y") {
        $fields[] = array(
            'name' => '[data][tracking]',
            'text' => __('inventory')
        );
    }

    if (fn_allowed_for('ULTIMATE,MULTIVENDOR') && !Registry::get('runtime.company_id')) {
        $fields[] = array(
            'name' => '[data][company_id]',
            'text' => fn_allowed_for('MULTIVENDOR') ? __('vendor') : __('store')
        );
    }

    /**
     * Hook for change fields array
     *
     * @param array $fields Product fields
     */
    fn_set_hook('get_product_fields', $fields);

    return $fields;
}

/**
 * Get product code by product_id
 *
 * @param int $product_id
 * @param array $product_options
 * @return (string) product code
 */
function fn_get_product_code($product_id, $product_options = array())
{
    if (!empty($product_id)) {
        $tracking = db_get_field("SELECT tracking FROM ?:products WHERE product_id = ?i", $product_id);
        $data['extra']['product_options'] = (array) $product_options;
        $combination_hash = fn_generate_cart_id($product_id, $data['extra']);
        $product_code = db_get_field("SELECT product_code FROM ?:product_options_inventory WHERE combination_hash = ?i AND product_id = ?i", $combination_hash, $product_id);

        if (empty($product_code) || $tracking != ProductTracking::TRACK_WITH_OPTIONS) {
            $product_code = db_get_field("SELECT product_code FROM ?:products WHERE product_id = ?i", $product_id);
        }

        return $product_code;
    }

    return '';
}

function fn_get_product_counts_by_category($params, $lang_code = CART_LANGUAGE)
{
    $default_params = array(
        'company_id' => 0,
        'sort_by' => 'position',
        'sort_order' => 'asc',
    );

    $params = array_merge($default_params, $params);

    $sort_fields = array(
        'position' => '?:categories.position',
        'category' => '?:category_descriptions.category',
        'count' => 'count',
    );

    $sort = db_sort($params, $sort_fields, $default_params['sort_by'], $default_params['sort_order']);

    $condition = $join = '';
    if (!empty($params['company_id'])) {
        if (is_array($params['company_id'])) {
            $condition .= db_quote(" AND ?:products.company_id IN (?n) ", $params['company_id']);
        } else {
            $condition .= db_quote(" AND ?:products.company_id = ?i ", $params['company_id']);
        }
    }
    $condition .= db_quote(" AND ?:category_descriptions.lang_code = ?s ", $lang_code);

    $join .= 'JOIN ?:products ON ?:products_categories.product_id = ?:products.product_id ';
    $join .= 'JOIN ?:categories ON ?:products_categories.category_id = ?:categories.category_id ';
    $join .= 'JOIN ?:category_descriptions ON ?:products_categories.category_id = ?:category_descriptions.category_id ';

    $result = db_get_array("SELECT COUNT(*) as count, ?:category_descriptions.category, ?:category_descriptions.category_id FROM ?:products_categories ?p WHERE 1 ?p GROUP BY ?:products_categories.category_id ?p", $join, $condition, $sort);

    return $result;
}

/**
 * Gets categefories and products totals data
 *
 * @return array Array with categories and products totals
 */
function fn_get_categories_stats()
{
    $stats = array();
    $params = array(
        'only_short_fields' => true, // NOT NEEDED AT ALL BECAUSE WE DONT USE RESULTING $FIELDS
        'extend' => array('companies', 'sharing'),
        'get_conditions' => true,
    );

    list($fields, $join, $condition) = fn_get_products($params);

    db_query('SELECT SQL_CALC_FOUND_ROWS 1 FROM ?:products AS products' . $join . ' WHERE 1 ' . $condition . 'GROUP BY products.product_id');
    $stats['products_total'] = db_get_found_rows();

    $params = array(
        'get_conditions' => true
    );
    list($fields, $join, $condition, $group_by, $sorting, $limit) = fn_get_categories($params);
    $stats['categories_total'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    $params = array(
        'get_conditions' => true,
        'status' => 'A'
    );
    list($fields, $join, $condition, $group_by, $sorting, $limit) = fn_get_categories($params);
    $stats['categories_active'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    $params = array(
        'get_conditions' => true,
        'status' => 'H'
    );
    list($fields, $join, $condition, $group_by, $sorting, $limit) = fn_get_categories($params);
    $stats['categories_hidden'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    $params = array(
        'get_conditions' => true,
        'status' => 'D'
    );
    list($fields, $join, $condition, $group_by, $sorting, $limit) = fn_get_categories($params);
    $stats['categories_disabled'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    return $stats;
}

/**
 * Gets all available brands.
 *
 * @return array Found brands
 */
function fn_get_all_brands()
{
    $params = array(
        'exclude_group' => true,
        'get_descriptions' => true,
        'feature_types' => array(ProductFeatures::EXTENDED),
        'variants' => true,
        'plain' => true,
    );

    list($features) = fn_get_product_features($params, 0);

    $variants = array();

    foreach ($features as $feature) {
        if (!empty($feature['variants'])) {
            $variants = array_merge($variants, $feature['variants']);
        }
    }

    return $variants;
}

function fn_update_product_categories($product_id, $product_data)
{
    /**
     * Change parameters before updating product categories
     *
     * @param integer $product_id   product ID
     * @param array   $product_data product data
     */
    fn_set_hook('update_product_categories_pre', $product_id, $product_data);

    $existing_categories = db_get_hash_array("SELECT category_id, link_type, position FROM ?:products_categories WHERE product_id = ?i", 'category_id', $product_id);

    $rebuild = false;
    if (!empty($product_data['category_ids'])) {
        $product_data['category_ids'] = array_unique($product_data['category_ids']);

        if (empty($product_data['main_category'])) {
            $product_data['main_category'] = reset($product_data['category_ids']);
        }

        if (sizeof($product_data['category_ids']) == sizeof($existing_categories)) {
            if (isset($existing_categories[$product_data['main_category']]) && $existing_categories[$product_data['main_category']]['link_type'] != 'M') {
                $rebuild = true;
            }

            foreach ($product_data['category_ids'] as $cid) {
                if (!isset($existing_categories[$cid])) {
                    $rebuild = true;
                }
            }
        } else {
            $rebuild = true;
        }
    }

    if ($rebuild == true) {
        db_query("DELETE FROM ?:products_categories WHERE product_id = ?i", $product_id);
        foreach ($product_data['category_ids'] as $cid) {
            $_data = array(
                'product_id' => $product_id,
                'category_id' => $cid,
                'position' => isset($existing_categories[$cid])
                    ? $existing_categories[$cid]['position']
                    : (isset($product_data['position'])  // Available on bulk product addition
                        ? (int) $product_data['position']
                        : 0
                    ),
                'link_type' => $product_data['main_category'] == $cid ? 'M' : 'A'
            );
            db_query("INSERT INTO ?:products_categories ?e", $_data);
        }

        fn_update_product_count(fn_array_merge($product_data['category_ids'], array_keys($existing_categories), false));
    }

    /**
     * Post processing after updating product categories
     *
     * @param integer $product_id          product ID
     * @param array   $product_data        product data
     * @param array   $existing_categories original product categories
     * @param boolean $rebuild             flag that indicates that product categories were changed
     */
    fn_set_hook('update_product_categories_post', $product_id, $product_data, $existing_categories, $rebuild);
}

/**
 * Checks if product linked to any category from the owner company
 *
 * @param int $product_id Product ID
 * @param array $category_ids List of category ids
 * @return bool True if linked
 */
function fn_check_owner_categories($company_id, $category_ids)
{
    $linked_to_categories =  db_get_field('SELECT COUNT(*) FROM ?:categories WHERE company_id = ?i AND category_id IN (?n)', $company_id, $category_ids);

    return !empty($linked_to_categories);
}

/*
 *
 * Filters
 *
 */

/**
 * Filters: gets available filters according to current products set
 *
 * @param array $params request params
 * @param string $lang_code language code
 * @return array available filters list
 */
function fn_get_filters_products_count($params = array(), $lang_code = CART_LANGUAGE)
{
    /**
     * Change parameters for getting product filters count
     *
     * @param array $params Products filter search params
     */
    fn_set_hook('get_filters_products_count_pre', $params);

    $cache_params = array(
        'category_id',
        'company_id',
        'dispatch',
        'search_performed',
        'q',
        'filter_id',
        'item_ids',
        'variant_id',
    );

    $key = array();
    foreach ($cache_params as $prop) {
        if (isset($params[$prop])) {
            $key[] = $params[$prop];
        }
    }

    $key = 'pfilters_' . md5(implode('|', $key));
    Registry::registerCache(array('pfilters', $key), array(
        'products',
        'product_descriptions',
        'product_features',
        'product_filters',
        'product_features_values',
        'products_categories',
        'categories',
        'product_filter_descriptions',
        'product_features_descriptions',
        'product_feature_variant_descriptions',
        'ult_objects_sharing' // FIXME: this should not be here
    ), Registry::cacheLevel('user'));

    $selected_filters = array();
    $available_variants = array();

    if (!empty($params['item_ids'])) {
        $params['filter_item_ids'] = $params['item_ids'];
        unset($params['item_ids']); // unset item_ids because $params array is passed to fn_get_products, etc later
    }

    if (!empty($params['check_location'])) { // FIXME: this is bad style, should be refactored
        $valid_locations = array(
            'categories.view',
            'product_features.view',
            'companies.products',
            'products.search'
        );

        if (!in_array($params['dispatch'], $valid_locations)) {
            return array();
        }
    }

    if (!Registry::isExist($key)) {

        $condition = $join = '';

        if (!empty($params['category_id'])) {
            if (Registry::get('settings.General.show_products_from_subcategories') == 'Y') {
                $id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $params['category_id']);
                $category_ids = db_get_fields("SELECT category_id FROM ?:categories WHERE id_path LIKE ?l", $id_path . '/%');
            } else {
                $category_ids = array();
            }
            $category_ids[] = $params['category_id'];

            $condition .= db_quote(" AND (?:product_filters.categories_path = '' OR FIND_IN_SET(?i, ?:product_filters.categories_path))", $params['category_id']);
        }

        if (!empty($params['filter_id'])) {
            $condition .= db_quote(" AND ?:product_filters.filter_id = ?i", $params['filter_id']);
        }

        if (!empty($params['filter_item_ids'])) {
            $condition .= db_quote(" AND ?:product_filters.filter_id IN (?n)", explode(',', $params['filter_item_ids']));
        }

        if (!empty($params['variant_id'])) {
            $exclude_feature_id = db_get_field("SELECT feature_id FROM ?:product_features_values WHERE variant_id = ?i", $params['variant_id']);
            $condition .= db_quote(" AND ?:product_filters.feature_id NOT IN (?n)", $exclude_feature_id);
        }

        if (fn_allowed_for('ULTIMATE')) {
            $condition .= fn_get_company_condition('?:product_filters.company_id');
        }

        $sf_fields = db_quote(
            "?:product_filters.feature_id, " .
            "?:product_filters.filter_id," .
            "?:product_filters.field_type," .
            "?:product_filters.round_to," .
            "?:product_filters.display," .
            "?:product_filters.display_count," .
            "?:product_filter_descriptions.filter," .
            "?:product_features.feature_type," .
            "?:product_features_descriptions.prefix," .
            "?:product_features_descriptions.suffix"
        );
        $sf_join =  db_quote(
            " LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id AND ?:product_filter_descriptions.lang_code = ?s" .
            " LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id" .
            " LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_filters.feature_id AND ?:product_features_descriptions.lang_code = ?s",
        $lang_code, $lang_code);

        $sf_sorting = db_quote("?:product_filters.position, ?:product_filter_descriptions.filter");

        /**
         * Change SQL parameters before select product filters
         *
         * @param array $sf_fields String of comma-separated SQL fields to be selected in an SQL-query
         * @param string $sf_join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
         * @param string $sf_sorting String containing the SQL-query ORDER BY clause
         * @param array $params Products filter search params
         */
        fn_set_hook('get_filters_products_count_before_select_filters', $sf_fields, $sf_join, $condition, $sf_sorting, $params);

        $filters = db_get_hash_array("SELECT $sf_fields FROM ?:product_filters ?p WHERE ?:product_filters.status = 'A' ?p ORDER BY $sf_sorting", 'filter_id', $sf_join, $condition);

        if (empty($filters)) {
            return array(array());
        }

        list($variant_values, $range_values, $field_variant_values, $field_range_values) = fn_get_current_filters($params, $filters, array(), AREA, $lang_code);

        Registry::set($key, array($filters, $variant_values, $range_values, $field_variant_values, $field_range_values));
    } else {

        list($filters, $variant_values, $range_values, $field_variant_values, $field_range_values) = Registry::get($key);
    }

    $range_values = fn_filter_process_ranges($range_values, $filters, $selected_filters);
    $field_range_values = fn_filter_process_ranges($field_range_values, $filters, $selected_filters);
    $merged = fn_array_merge($variant_values, $range_values, $field_variant_values, $field_range_values);

    $available_variants = $merged;

    if (!empty($params['features_hash']) && empty($params['skip_advanced_variants'])) {
        $selected_filters = fn_parse_filters_hash($params['features_hash']);

        // Get available variants for current selection
        $_params = $params;
        $_params['split_filters'] = true;
        list($available_variants, $available_ranges, $available_field_values, $available_field_ranges) = fn_get_current_filters($_params, $filters, $selected_filters, AREA, $lang_code);

        list($variant_filter_ids) = fn_split_selected_feature_variants($filters, $selected_filters, false);
        if (sizeof($variant_filter_ids) == 1 && sizeof($selected_filters) == 1) {
            $filter_id = key($variant_filter_ids);
            $available_variants[$filter_id] = $variant_values[$filter_id];
        }

        $available_ranges = fn_filter_process_ranges($available_ranges, $filters, $selected_filters);
        $available_field_ranges = fn_filter_process_ranges($available_field_ranges, $filters, $selected_filters);

        $available_variants = fn_array_merge($available_variants, $available_ranges, $available_field_values, $available_field_ranges);
        $merged = fn_array_merge($merged, $available_variants);
    }

    foreach ($filters as $filter_id => $filter) {
        if (empty($merged[$filter_id]) || (
            !empty($filter['feature_type']) &&
            empty($available_variants[$filter_id]))
        ) {
            unset($filters[$filter_id]);
            continue;
        }

        $filters[$filter_id] = fn_array_merge($filters[$filter_id], $merged[$filter_id]);

        if (!empty($filters[$filter_id]['variants'])) {
            // Move selected variants to selected_variants key
            if (!empty($selected_filters[$filter_id])) {
                foreach ($selected_filters[$filter_id] as $variant_id) {
                    if (!empty($filters[$filter_id]['variants'][$variant_id])) {
                        $filters[$filter_id]['selected_variants'][$variant_id] = $filters[$filter_id]['variants'][$variant_id];
                        unset($filters[$filter_id]['variants'][$variant_id]);
                    }
                }
            }

            // If we selected any variants in filter, disabled unavailable variants
            if (!empty($available_variants) && !empty($available_variants[$filter_id])) {
                foreach ($filters[$filter_id]['variants'] as $variant_id => $variant_data) {
                    if (empty($available_variants[$filter_id]['variants'][$variant_id])) {
                        // disable variant and move it to the end of list
                        unset($filters[$filter_id]['variants'][$variant_id]);
                        $variant_data['disabled'] = true;
                        $filters[$filter_id]['variants'][$variant_id] = $variant_data;
                    }
                }
            }
        }

        // If range is selected, mark this filter
        if (!empty($filters[$filter_id]['slider']) && !empty($selected_filters[$filter_id])) {
            if (!empty($filters[$filter_id]['slider']['left']) || !empty($filters[$filter_id]['right'])) {
                $filters[$filter_id]['selected_range'] = true;
            }
        }
    }

    /**
     * Modifies filters
     *
     * @param array  $params            Parameters of filters selection
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $filters           Filters array
     */
    fn_set_hook('get_filters_products_count_post', $params, $lang_code, $filters);

    return array($filters);
}

/**
 * Filters: removes variant or filter from selected filters list
 *
 * @param string $features_hash selected filters list
 * @param integer $filter_id filter ID
 * @param mixed $variant filter variant
 * @return string updated filters list
 */
function fn_delete_filter_from_hash($features_hash, $filter_id, $variant = '')
{
    $filters = fn_parse_filters_hash($features_hash);

    if (!empty($filters[$filter_id])) {
        if (!empty($variant) && in_array($variant, $filters[$filter_id])) {
            $values = array_flip($filters[$filter_id]);
            unset($values[$variant]);
            if (!empty($values)) {
                $filters[$filter_id] = array_keys($values);
            } else {
                unset($filters[$filter_id]);
            }
        } elseif (empty($variant)) {
            unset($filters[$filter_id]);
        }
    }

    return fn_generate_filter_hash($filters);
}

/**
 * Filters: adds variant to selected filters list
 *
 * @param string $features_hash selected filters list
 * @param integer $filter_id filter ID
 * @param mixed $variant filter variant
 * @return string updated filters list
 */
function fn_add_filter_to_hash($features_hash, $filter_id, $variant = '')
{
    $filters = fn_parse_filters_hash($features_hash);

    if (!isset($filters[$filter_id]) || !in_array($variant, $filters[$filter_id])) {
        $filters[$filter_id][] = $variant;
    }

    return fn_generate_filter_hash($filters);
}

/**
 * Filters: generates filter hash
 * @param array $filters selected filters list
 * @return string filter hash
 */
function fn_generate_filter_hash($filters)
{
    $res = array();
    foreach ($filters as $filter_id => $variants) {
        if (is_array($variants)) {
            $res[] = $filter_id . FILTERS_HASH_FEATURE_SEPARATOR . implode(FILTERS_HASH_FEATURE_SEPARATOR, $variants);
        } else {
            $res[] = $filter_id . FILTERS_HASH_FEATURE_SEPARATOR . $variants;
        }
    }

    return implode(FILTERS_HASH_SEPARATOR, $res);
}

/**
 * Filters: parses selected filters list
 * @param string $features_hash selected filters list
 * @return array parsed filters list
 */
function fn_parse_filters_hash($features_hash = '')
{
    $result = array();

    if (!empty($features_hash)) {
        $values = explode(FILTERS_HASH_SEPARATOR, $features_hash);
        foreach ($values as $value) {
            $variants = explode(FILTERS_HASH_FEATURE_SEPARATOR, $value);
            $filter_id = array_shift($variants);
            $result[$filter_id] = $variants;
        }
    }

    return $result;
}

/**
 * Filters: splits selected filter/feature variants by type
 *
 * @param array   $items          filters or features list
 * @param array   $selected_items selected filter or feature variants
 * @param boolean $key_is_feature use filter_id or feature_id as array key
 *
 * @return array selected filter/feature variants, split by type
 */
function fn_split_selected_feature_variants($items, $selected_items, $key_is_feature = true)
{
    $variant_features = array();
    $value_features = array();
    $valueint_features = array();
    $key = $key_is_feature ? 'feature_id' : 'filter_id';

    foreach ($items as $item) {
        $id = !empty($item['filter_id']) ? $item['filter_id'] : $item['feature_id'];

        if (!empty($item['feature_id']) && isset($selected_items[$id])) {
            if (in_array($item['feature_type'], array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::EXTENDED))) {
                $variant_features[$item[$key]] = $selected_items[$id];

            } elseif (in_array($item['feature_type'], array(ProductFeatures::SINGLE_CHECKBOX, ProductFeatures::TEXT_FIELD))) {
                if (!empty($selected_items[$id][0])) {
                    $value_features[$item[$key]] = $selected_items[$id][0];
                }
            } elseif (in_array($item['feature_type'], array(ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::NUMBER_FIELD, ProductFeatures::DATE))) {

                $min = 0;
                $max = 0;
                if (isset($selected_items[$id][0])) {
                    if ($item['feature_type'] == ProductFeatures::DATE) {
                        $selected_items[$id][0] = fn_parse_date($selected_items[$id][0]);
                    } elseif (isset($item['round_to'])) {
                        $selected_items[$id][0] = Math::floorToPrecision($selected_items[$id][0], $item['round_to']);
                    }
                    $min = $selected_items[$id][0];
                }
                if (isset($selected_items[$id][1])) {
                    if ($item['feature_type'] == ProductFeatures::DATE) {
                        $selected_items[$id][1] = fn_parse_date($selected_items[$id][1]);
                    } elseif (isset($item['round_to'])) {
                        $selected_items[$id][1] = Math::ceilToPrecision($selected_items[$id][1], $item['round_to']);
                    }
                    $max = $selected_items[$id][1];
                }

                if (!empty($min) || !empty($max)) {
                    $valueint_features[$item[$key]] = array($min, $max);
                }
            }
        }
    }

    return array($variant_features, $value_features, $valueint_features);
}

/**
 * Filters: generates conditions to search products by selected filter/feature variant
 * @param array $items filters or features list
 * @param array $selected_items selected filter or feature variants
 * @param string $join "join" conditions
 * @param string $condition "where" conditions
 * @param string $lang_code language code
 * @param array $params additional params
 * @return array "join" and "where" conditions
 */
function fn_generate_feature_conditions($items, $selected_items, $join, $condition, $lang_code, $params = array())
{
    list($variant_features, $value_features, $valueint_features) = fn_split_selected_feature_variants($items, $selected_items);

    // find selected variants for features with variants
    if (!empty($variant_features)) {

        $conditions = array();

        foreach ($variant_features as $fid => $variants) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values as var_val_$fid ON var_val_$fid.product_id = products.product_id AND var_val_$fid.lang_code = ?s AND var_val_$fid.feature_id = ?i", $lang_code, $fid);
            $conditions[$fid] = db_quote("var_val_$fid.variant_id IN (?n)", $variants);
        }

        // This is used to get all available filter variants for current conditions (magic becomes here :))
        if (!empty($params['split_filters']) && sizeof($variant_features) > 1) {

            // This condition gets available variants for all not selected filters
            $combined_conditions = array(
                '(' . implode(' AND ', $conditions) . db_quote(' AND ?:product_features_values.feature_id NOT IN (?n))', array_keys($conditions))
            );

            foreach ($variant_features as $fid => $variants) {
                $tmp = $conditions;
                unset($tmp[$fid]);
                // This condition gets available variants for certain filter with ID == $fid
                $combined_conditions[] = '(' . implode(' AND ', $tmp) . db_quote(' AND ?:product_features_values.feature_id = ?i)', $fid);
            }
            $condition .= ' AND (' . implode(' OR ', $combined_conditions) . ')';
        } else {
            if (!empty($params['variant_filter']) && sizeof($variant_features) == 1) {
                $feature_ids = array_keys($variant_features);
                $fid = reset($feature_ids);
                $condition .= ' AND (' . implode(' AND ', $conditions) . db_quote(' OR ?:product_features_values.feature_id = ?i', $fid) . ')';
            } else {
                $condition .= ' AND (' . implode(' AND ', $conditions) . ')';
            }
        }
    }

    // find selected variants for features with custom values
    if (!empty($valueint_features)) {
        foreach ($valueint_features as $fid => $ranges) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values as var_val_$fid ON var_val_$fid.product_id = products.product_id AND var_val_$fid.lang_code = ?s AND var_val_$fid.feature_id = ?i", $lang_code, $fid);
            $condition .= db_quote(" AND (var_val_$fid.value_int >= ?d AND var_val_$fid.value_int <= ?d AND var_val_$fid.value = '')", $ranges[0], $ranges[1]);
        }
    }

    // find selected variants for checkbox and text features
    if (!empty($value_features)) {
        foreach ($value_features as $fid => $value) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values as ch_features_$fid ON ch_features_$fid.product_id = products.product_id AND ch_features_$fid.lang_code = ?s AND ch_features_$fid.feature_id = ?i", $lang_code, $fid);
            $condition .= db_quote(" AND ch_features_$fid.value = ?s", $value);
        }
    }

    return array($join, $condition);
}

/**
 * Filters: generates search params to search products by product fields
 * @param array $params request params
 * @param array $filters filters list
 * @param array $selected_filters selected filter variants
 * @return array search params
 */
function fn_generate_filter_field_params($params, $filters, $selected_filters)
{
    $filter_fields = fn_get_product_filter_fields();

    foreach ($filters as $filter) {
        if (!empty($filter['field_type'])) {
            $structure = $filter_fields[$filter['field_type']];

            if ($structure['condition_type'] == 'F') {
                if (!empty($selected_filters[$filter['filter_id']])) {
                    $params['filter_params'][$structure['db_field']] = $selected_filters[$filter['filter_id']];
                }

            } elseif ($structure['condition_type'] == 'C') {
                if (!empty($selected_filters[$filter['filter_id']][0])) {
                    foreach ($structure['map'] as $_param => $_value) {
                        $params[$_param] = $_value;
                    }
                }
            } elseif ($structure['condition_type'] == 'D') {

                $min = 0;
                $max = 0;
                $extra = '';
                if (isset($selected_filters[$filter['filter_id']][0])) {
                    if (isset($filter['round_to'])) {
                        $min = Math::floorToPrecision($selected_filters[$filter['filter_id']][0], $filter['round_to']);
                    } else {
                        $min = intval($selected_filters[$filter['filter_id']][0]);
                    }
                }
                if (isset($selected_filters[$filter['filter_id']][1])) {
                    if (isset($filter['round_to'])) {
                        $max = Math::floorToPrecision($selected_filters[$filter['filter_id']][1], $filter['round_to']);
                    } else {
                        $max = intval($selected_filters[$filter['filter_id']][1]);
                    }
                }
                if (isset($selected_filters[$filter['filter_id']][2])) {
                    $extra = $selected_filters[$filter['filter_id']][2];
                }

                if (!empty($structure['convert'])) {
                    list($min, $max) = $structure['convert']($min, $max, $extra);
                }

                $params[$structure['db_field'] . '_from'] = $min;
                $params[$structure['db_field'] . '_to'] = $max;
            }
        }
    }

    return $params;
}

/**
 * Filters: gets all available filter variants
 * @param array $params request params
 * @param array $filters filters list
 * @param array $selected_filters selected filter variants
 * @param string $area current working area
 * @param string $lang_code language code
 * @return array available filter variants, filter range values, product field variants and product field range values
 */
function fn_get_current_filters($params, $filters, $selected_filters, $area = AREA, $lang_code = CART_LANGUAGE)
{
    $condition = $where = $join = '';
    $variant_values = array();
    $variant_descriptions = array();
    $range_values = array();
    $field_variant_values = array();
    $field_range_values = array();

    $filter_ids = $feature_ids = array();
    $standard_fields = array();

    $params['variant_filter'] = false;
    foreach ($filters as $filter) {
        $filter_ids[] = $filter['filter_id'];

        if (!empty($filter['feature_id'])) {
            $feature_ids[] = $filter['feature_id'];
        } elseif (!empty($filter['field_type'])) {
            $standard_fields[$filter['filter_id']] = $filter;
        }

        if (!empty($selected_filters[$filter['filter_id']]) && in_array($filter['feature_type'], array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::EXTENDED, ProductFeatures::MULTIPLE_CHECKBOX))) {
            $params['variant_filter'] = true;
        }
    }

    $_params = $params;
    $_params['features_hash'] = '';
    $_params['get_conditions'] = true;
    $_params['custom_extend'] = array('categories');
    if (!empty($params['category_id'])) {
        $_params['cid'] = $params['category_id'];
        $_params['subcats'] = '';
        if (Registry::get('settings.General.show_products_from_subcategories') == 'Y') {
            $_params['subcats'] = 'Y';
        }
    }

    list(, $join, $where) = fn_get_products($_params, 0, $lang_code);

    if (!empty($_params['split_filters'])) {
        list($variant_features, $value_features, $valueint_features) = fn_split_selected_feature_variants($filters, $selected_filters, false);
    } else {
        $variant_features = $value_features = $valueint_features = array();
    }

    if (!empty($feature_ids)) {

        $selected_filters_variants = $selected_filters;
        if (!empty($_params['split_filters'])) {
            $_params['features_hash'] = '';
            $other_filters = array_diff_key($selected_filters, $variant_features);
            if (!empty($other_filters)) {
                $_params['features_hash'] = fn_generate_filter_hash($other_filters);
            }
            $selected_filters_variants = $variant_features;
        }

        list(, $join, $where) = fn_get_products($_params, 0, $lang_code);
        list($join, $where) = fn_generate_feature_conditions($filters, $selected_filters_variants, $join, $where, $lang_code, $params);

        // Get all available variants
        $variant_values = db_get_hash_single_array(
            "SELECT ?:product_filters.filter_id, ?:product_features_values.variant_id FROM ?:product_features_values " .
            "LEFT JOIN ?:products as products ON products.product_id = ?:product_features_values.product_id " .
            "LEFT JOIN ?:product_filters ON ?:product_filters.feature_id = ?:product_features_values.feature_id " .
            "LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_features_values.feature_id ?p " .
            "WHERE " .
                "?:product_filters.filter_id IN (?n) AND " .
                "?:product_features_values.feature_id IN (?n) AND " .
                "?:product_features_values.lang_code = ?s ?p AND " .
                "?:product_features.feature_type IN (?s, ?s, ?s) " .
            "GROUP BY ?:product_features_values.variant_id", array('variant_id', 'filter_id'),
        $join, $filter_ids, $feature_ids, $lang_code, $where, ProductFeatures::TEXT_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::EXTENDED);

        // Get descriptions and position
        if (!empty($variant_values)) {
            $variant_descriptions = db_get_hash_array(
                "SELECT ?:product_feature_variants.variant_id, ?:product_feature_variants.position, ?:product_feature_variant_descriptions.variant FROM ?:product_feature_variants " .
                "LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s " .
                "WHERE ?:product_feature_variants.variant_id IN (?n) " .
                "ORDER BY ?:product_feature_variants.position ASC, ?:product_feature_variant_descriptions.variant ASC",
                'variant_id',
                $lang_code,
                array_keys($variant_values)
            );
        }

        // swap array
        $converted = array();
        foreach ($variant_descriptions as $variant_id => $variant_data) {
            $converted[$variant_values[$variant_id]]['variants'][$variant_id] = array(
                'variant_id' => $variant_id,
                'variant' => $variant_data['variant'],
                'position' => $variant_data['position'],
            );
        }

        if (!empty($_params['split_filters'])) {
            $_params['features_hash'] = '';
            $other_filters = array_diff_key($selected_filters, $value_features);
            if (!empty($other_filters)) {
                $_params['features_hash'] = fn_generate_filter_hash($other_filters);
            }
        }

        list(, $join, $where) = fn_get_products($_params, 0, $lang_code);

        // Get checkbox feature variants
        $checkbox_values = db_get_fields(
            "SELECT ?:product_filters.filter_id FROM ?:product_features_values " .
            "LEFT JOIN ?:products as products ON products.product_id = ?:product_features_values.product_id " .
            "LEFT JOIN ?:product_filters ON ?:product_filters.feature_id = ?:product_features_values.feature_id " .
            "LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_features_values.feature_id ?p " .
            "WHERE " .
                "?:product_filters.filter_id IN (?n) AND " .
                "?:product_features_values.feature_id IN (?n) AND " .
                "?:product_features_values.value = 'Y' AND " .
                "?:product_features_values.lang_code = ?s ?p AND " .
                "?:product_features.feature_type = ?s " .
            "GROUP BY ?:product_features_values.feature_id",
        $join, $filter_ids, $feature_ids, $lang_code, $where, ProductFeatures::SINGLE_CHECKBOX);

        if (!empty($checkbox_values)) {
            foreach ($checkbox_values as $filter_id) {
                $converted[$filter_id]['variants']['Y'] = array(
                    'variant_id' => 'Y',
                    'variant' => __('yes')
                );
            }
        }

        $variant_values = $converted;

        if (!empty($_params['split_filters'])) {
            $_params['features_hash'] = '';
            $other_filters = array_diff_key($selected_filters, $valueint_features);
            if (!empty($other_filters)) {
                $_params['features_hash'] = fn_generate_filter_hash($other_filters);
            }
        }

        list(, $join, $where) = fn_get_products($_params, 0, $lang_code);

        // Get range limits
        $range_values = db_get_hash_array(
            "SELECT ?:product_filters.filter_id, MIN(?:product_features_values.value_int) as min, MAX(?:product_features_values.value_int) as max FROM ?:product_features_values " .
            "LEFT JOIN ?:products as products ON products.product_id = ?:product_features_values.product_id " .
            "LEFT JOIN ?:product_filters ON ?:product_filters.feature_id = ?:product_features_values.feature_id " .
            "LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_features_values.feature_id ?p " .
            "WHERE " .
                "?:product_filters.filter_id IN (?n) AND " .
                "?:product_features_values.feature_id IN (?n) AND " .
                "?:product_features_values.lang_code = ?s ?p AND " .
                "?:product_features.feature_type IN (?s, ?s, ?s) " .
            "GROUP BY ?:product_features_values.feature_id", 'filter_id',
        $join, $filter_ids, $feature_ids, $lang_code, $where, ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::NUMBER_FIELD, ProductFeatures::DATE);
    }

    // Get range limits for standard fields
    if (!empty($standard_fields)) {
        $_params['features_hash'] = '';
        if (!empty($_params['split_filters'])) {
            $_params['features_hash'] = fn_generate_filter_hash(fn_array_merge($variant_features, $value_features, $valueint_features));
        }

        list(, $join, $where) = fn_get_products($_params, 0, $lang_code);
        $fields = fn_get_product_filter_fields();

        foreach ($standard_fields as $filter_id => $filter) {
            $structure = $fields[$filter['field_type']];
            $fields_join = $fields_where = $table_alias = '';

            if ($structure['table'] == 'products') {
                $table_alias = ' as products ';
                $db_field = "products.$structure[db_field]";
            } else {
                $db_field = "?:$structure[table].$structure[db_field]";
                $fields_join .= " LEFT JOIN ?:products as products ON products.product_id = ?:$structure[table].product_id";
            }

            if (!empty($structure['conditions']) && is_callable($structure['conditions'])) {
                list($db_field, $fields_join, $fields_where) = $structure['conditions']($db_field, $fields_join, $fields_where);
            }

            // Checkboxes (in stock, etc)
            if ($structure['condition_type'] == 'C') {
                $field_variant_values[$filter_id] = array(
                    'variants' => array(
                        'Y' => array(
                            'variant_id' => 'Y',
                            'variant' => __($structure['description'])
                        )
                    )
                );

            // Dinamic ranges (price, etc)
            } elseif ($structure['condition_type'] == 'D') {

                $range = db_get_row("SELECT MIN($db_field) as min, MAX($db_field) as max FROM ?:$structure[table] $table_alias ?p WHERE products.status IN ('A') ?p", $fields_join . $join, $where . $fields_where);

                if (!fn_is_empty($range)) {
                    $range['field_type'] = $filter['field_type'];
                    $field_range_values[$filter_id] = $range;
                }

            // Variants (vendors, etc)
            } elseif ($structure['condition_type'] == 'F') {

                $result = $field_variant_values[$filter_id]['variants'] = db_get_hash_array(
                    "SELECT $db_field as variant_id, $structure[variant_name_field] as variant"
                    . " FROM ?:$structure[table] $table_alias ?p"
                    . " WHERE 1 ?p"
                    . " GROUP BY $db_field"
                    . " ORDER BY $structure[variant_name_field] ASC",
                    'variant_id', $fields_join . $join, $fields_where . $where
                );

                if (fn_is_empty($result)) {
                    unset($field_variant_values[$filter_id]);
                }
            }

            foreach (array('prefix', 'suffix') as $key) {
                if (!empty($structure[$key])) {
                    if (!empty($field_variant_values[$filter_id])) {
                        $field_variant_values[$filter_id][$key] = $structure[$key];
                    } elseif (!empty($field_range_values[$filter_id])) {
                        $field_range_values[$filter_id][$key] = $structure[$key];
                    }
                }
            }
        }
    }

    return array($variant_values, $range_values, $field_variant_values, $field_range_values);
}

/**
 * Filters: corrects min/max and left/right values for range filter
 *
 * @param array $range_values     range filter values
 * @param array $filters          filters list
 * @param array $selected_filters selected filter variants
 *
 * @return array corrected values
 */
function fn_filter_process_ranges($range_values, $filters, $selected_filters)
{
    if (!empty($range_values)) {
        $fields = fn_get_product_filter_fields();

        foreach ($range_values as $filter_id => $values) {
            if (!empty($values)) {

                if (!empty($values['field_type'])) { // standard field
                    $structure = $fields[$values['field_type']];
                    if (!empty($structure['convert'])) {
                        list($values['min'], $values['max']) = $structure['convert']($values['min'], $values['max']);
                    }
                    $values['extra'] = !empty($structure['extra']) ? $structure['extra'] : '';
                }

                $values['slider'] = true;
                $values['min'] = Math::floorToPrecision($values['min'], $filters[$filter_id]['round_to']);
                $values['max'] = Math::ceilToPrecision($values['max'], $filters[$filter_id]['round_to']);

                if (!empty($selected_filters[$filter_id])) {
                    $slider_vals = $selected_filters[$filter_id];

                    // convert to base values
                    if (!empty($values['field_type']) && !empty($structure['convert'])) {
                        list($slider_vals[0], $slider_vals[1]) = $structure['convert']($slider_vals[0], $slider_vals[1], $slider_vals[2]);
                    }
                    // zeke: TODO - do not convert twice
                    // convert back to current values
                    if (!empty($values['field_type']) && !empty($structure['convert'])) {
                        list($slider_vals[0], $slider_vals[1]) = $structure['convert']($slider_vals[0], $slider_vals[1]);
                    }

                    $values['left'] = $slider_vals[0];
                    $values['right'] = $slider_vals[1];

                    if ($values['left'] < $values['min']) {
                        $values['left'] = $values['min'];
                    }
                    if ($values['left'] > $values['max']) {
                        $values['left'] = $values['max'];
                    }
                    if ($values['right'] > $values['max']) {
                        $values['right'] = $values['max'];
                    }
                    if ($values['right'] < $values['min']) {
                        $values['right'] = $values['min'];
                    }
                    if ($values['right'] < $values['left']) {
                        $tmp = $values['right'];
                        $values['right'] = $values['left'];
                        $values['left'] = $tmp;
                    }

                    $values['left'] = Math::floorToPrecision($values['left'], $filters[$filter_id]['round_to']);
                    $values['right'] = Math::ceilToPrecision($values['right'], $filters[$filter_id]['round_to']);
                }

                $range_values[$filter_id] = $values;
            }
        }
    }

    return $range_values;
}

/**
 * Filters: gets list of product fields available for filtering
 * @return array filter product fields list
 */
function fn_get_product_filter_fields()
{
    $filters = array (
        // price filter
        'P' => array (
            'db_field' => 'price',
            'table' => 'product_prices',
            'description' => 'price',
            'condition_type' => 'D',
            'slider' => true,
            'convert' => function($min, $max, $extra = '') {

                if (!empty($extra) && $extra != CART_PRIMARY_CURRENCY && Registry::get('currencies.' . $extra)) {
                    $currency = Registry::get('currencies.' . $extra);

                    $min = round(floatval($min) * floatval($currency['coefficient']), $currency['decimals']);
                    $max = round(floatval($max) * floatval($currency['coefficient']), $currency['decimals']);
                } elseif (empty($extra) && CART_PRIMARY_CURRENCY != CART_SECONDARY_CURRENCY) {
                    $currency = Registry::get('currencies.' . CART_SECONDARY_CURRENCY);

                    $min = round(floatval($min) / floatval($currency['coefficient']), $currency['decimals']);
                    $max = round(floatval($max) / floatval($currency['coefficient']), $currency['decimals']);
                }

                return array($min, $max);
            },
            'conditions' => function($db_field, $join, $condition) {

                $join .= db_quote("
                    LEFT JOIN ?:product_prices as prices_2 ON ?:product_prices.product_id = prices_2.product_id AND ?:product_prices.price > prices_2.price AND prices_2.lower_limit = 1 AND prices_2.usergroup_id IN (?n)",
                    array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids'])
                );

                $condition .= db_quote("
                    AND ?:product_prices.lower_limit = 1 AND ?:product_prices.usergroup_id IN (?n) AND prices_2.price IS NULL",
                    array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids'])
                );

                if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
                    $db_field = "IF(shared_prices.product_id IS NOT NULL, shared_prices.price, ?:product_prices.price)";
                    $join .= db_quote(" LEFT JOIN ?:ult_product_prices AS shared_prices ON shared_prices.product_id = products.product_id"
                        . " AND shared_prices.lower_limit = 1"
                        . " AND shared_prices.usergroup_id IN (?n)"
                        . " AND shared_prices.company_id = ?i",
                        array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids']),
                        Registry::get('runtime.company_id')
                    );
                }

                return array($db_field, $join, $condition);
            },
            'extra' => CART_SECONDARY_CURRENCY,
            'prefix' => (Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.after') == 'Y' ? '' : Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.symbol')),
            'suffix' => (Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.after') != 'Y' ? '' : Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.symbol'))
        ),
        // amount filter
        'A' => array (
            'db_field' => 'amount',
            'table' => 'products',
            'description' => 'in_stock',
            'condition_type' => 'C',
            'map' => array(
                'amount_from' => 1,
            )
        ),
        // filter by free shipping
        'F' => array (
            'db_field' => 'free_shipping',
            'table' => 'products',
            'description' => 'free_shipping',
            'condition_type' => 'C',
            'map' => array(
                'free_shipping' => 'Y',
            )
        )
    );

    /**
     * Changes product filter fields data
     *
     * @param array $filters Product filter fields
     */
    fn_set_hook('get_product_filter_fields', $filters);

    return $filters;
}

/**
 * Filters: displays notifications when products were not found using current filters combination
 */
function fn_filters_not_found_notification()
{
    Tygh::$app['view']->assign('product_info', __('text_no_products_found'));
    fn_set_notification('I', __('notice'), Tygh::$app['view']->fetch('views/products/components/notification.tpl'));

    Tygh::$app['ajax']->assign('no_products', true);
}

/**
 * Checks whether given filter appears as a numeric slider.
 *
 * @param array $filter_data Filter data returned by fn_get_product_filters() function
 *
 * @return bool Whether given filter accepts ranged numeric values.
 */
function fn_get_filter_is_numeric_slider($filter_data)
{
    $is_ranged = false;

    if (!empty($filter_data['field_type'])) {
        $filter_fields = fn_get_product_filter_fields();
        if (isset($filter_fields[$filter_data['field_type']])) {
            $is_ranged = !empty($filter_fields[$filter_data['field_type']]['slider']);
        }
    } elseif (!empty($filter_data['feature_type'])) {
        $is_ranged = in_array(
            $filter_data['feature_type'],
            array(ProductFeatures::NUMBER_FIELD, ProductFeatures::NUMBER_SELECTBOX)
        );
    }

    return $is_ranged;
}

/**
 * Gets all created combinations for product(s)
 *
 * @param array  $params         Combination search params
 * @param int    $items_per_page Items per page
 * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return array Combinations list and Search params
 */
function fn_get_product_options_inventory($params, $items_per_page = 0, $lang_code = DESCR_SL)
{
    /**
     * Changes params before selecting option combinations
     *
     * @param array  $params         Combination search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_options_inventory_pre', $params, $items_per_page, $lang_code);

    $default_params = array (
        'page' => 1,
        'product_id' => 0,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_options_inventory WHERE product_id = ?i", $params['product_id']);
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $inventory = db_get_array("SELECT * FROM ?:product_options_inventory WHERE product_id = ?i ORDER BY position $limit", $params['product_id']);

    foreach ($inventory as $k => $v) {
        $inventory[$k]['combination'] = fn_get_product_options_by_combination($v['combination']);
        $inventory[$k]['image_pairs'] = fn_get_image_pairs($v['combination_hash'], 'product_option', 'M', true, true, $lang_code);
    }

    /**
     * Modifies option combinations
     *
     * @param array  $params         Combination search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array List of available option combinations
     */
    fn_set_hook('get_product_options_inventory_post', $params, $items_per_page, $lang_code, $inventory);

    return array($inventory, $params);
}

/**
 * Gets combination data by combination hash
 *
 * @param string $combination_hash Combination unique hash
 * @return array Combination data
 */
function fn_get_product_options_combination_data($combination_hash, $lang_code = DESCR_SL)
{
    $combination = db_get_row("SELECT * FROM ?:product_options_inventory WHERE combination_hash = ?i", $combination_hash);

    $combination['combination'] = fn_get_product_options_by_combination($combination['combination']);
    $combination['image_pairs'] = fn_get_image_pairs($combination['combination_hash'], 'product_option', 'M', true, true, $lang_code);

    /**
     * Modifies selected combination data
     *
     * @param string $combination_hash Combination unique hash
     * @param array  $combination_hash Combination data
     */
    fn_set_hook('get_product_options_combination_data_post', $combination_hash, $combination);

    return $combination;
}

/**
 * Updates/Creates options combination
 *
 * @param array $combination_data Combination data
 * @param string $combination_hash Combination hash
 * @return string Combination hash
 */
function fn_update_option_combination($combination_data, $combination_hash = 0)
{
    /**
     * Change parameters for updating options combination
     *
     * @param array  $combination_data Combination data
     * @param string $combination_hash Combination hash
     */
    fn_set_hook('update_option_combination_pre', $combination_data, $combination_hash);

    $inventory_amount = db_get_field('SELECT amount FROM ?:product_options_inventory WHERE combination_hash = ?s', $combination_hash);

    if (empty($combination_hash)) {
        $combination_hash = fn_generate_cart_id($combination_data['product_id'], array('product_options' => $combination_data['combination']));
        $combination = fn_get_options_combination($combination_data['combination']);
        $product_code = fn_get_product_code($combination_data['product_id'], $combination_data['combination']);

        $_data = array(
            'product_id' => $combination_data['product_id'],
            'combination_hash' => $combination_hash,
            'combination' => $combination,
            'product_code' => !empty($product_code) ? $product_code : '',
            'amount' => !empty($combination_data['amount']) ? $combination_data['amount'] : 0,
            'position' => !empty($combination_data['position']) ? $combination_data['position'] : 0,
        );

        db_query("REPLACE INTO ?:product_options_inventory ?e", $_data);

    } else {
        // Forbid to update options in the existing combination. Only qty/code/pos.
        unset($combination_data['combination']);

        db_query("UPDATE ?:product_options_inventory SET ?u WHERE combination_hash = ?s", $combination_data, $combination_hash);
    }

    if (isset($combination_data['amount']) && $combination_data['amount'] > 0 && $inventory_amount <= 0) {
        fn_send_product_notifications($combination_data['product_id']);
    }

    // Updating images
    fn_attach_image_pairs('combinations', 'product_option');

    /**
     * Makes extra actions after updating options combination
     *
     * @param array  $combination_data Combination data
     * @param string $combination_hash Combination hash
     * @param int    $inventory_amount Previous (before update) inventory amount of the combination
     */
    fn_set_hook('update_option_combination_post', $combination_data, $combination_hash, $inventory_amount);

    return $combination_hash;
}

/**
 * Deletes options combination
 *
 * @param string $combination_hash Combination hash
 * @return bool true
 */
function fn_delete_option_combination($combination_hash)
{
    /**
     * Makes additional actions before deleting options combination
     *
     * @param string $combination_hash Combination hash
     */
    fn_set_hook('delete_option_combination_pre', $combination_hash);

    db_query("DELETE FROM ?:product_options_inventory WHERE combination_hash = ?i", $combination_hash);
    fn_delete_image_pairs($combination_hash, 'product_option');

    return true;
}

/**
 * Returns product creation timestamp
 *
 * @param int $product_id Product ID
 * @param bool $day_begin Set timestamp to beginning of the day
 * @return int product creation timestamp
 */
function fn_get_product_timestamp($product_id, $day_begin = false)
{
    if (empty($product_id)) {
        return false;
    }

    $timestamp = db_get_field("SELECT timestamp FROM ?:products WHERE product_id = ?i", $product_id);

    if ($day_begin) {
        $timestamp = mktime(0,0,0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
    }

    return $timestamp;
}

/**
 * Creates category used for trash
 *
 * @param int $company_id Company ID
 * @return int ID of trash category
 */
function fn_create_trash_category($company_id)
{
    $category_data = array(
        'category' => __('trash_category'),
        'description' => __('trash_category_description'),
        'status' => 'D', // disabled
        'is_trash' => 'Y',
        'company_id' => $company_id,
        'timestamp' => time(),
        'selected_views' => '',
        'product_details_view' => 'default',
        'use_custom_templates' => 'N'
    );
    $trash_id = fn_update_category($category_data);
    return $trash_id;
}

/**
 * Returns identifier of category used for trash
 *
 * @param int $company_id Company identifier
 * @return int|boolean Identifier of trash category, false when none exists
 */
function fn_get_trash_category($company_id)
{
    $trash_id = db_get_field(
        "SELECT category_id"
        . " FROM ?:categories"
        . " WHERE is_trash = 'Y'"
        . " AND company_id = ?i", $company_id
    );

    if (!is_numeric($trash_id)) {
        $trash_id = false;
    }

    return $trash_id;
}

/**
 * Checks if category is used for trash
 *
 * @param int $category_id Category ID to check for
 * @return boolean Category is used for trash
 */
function fn_is_trash_category($category_id)
{
    $is_trash = db_get_field(
        "SELECT is_trash"
        . " FROM ?:categories"
        . " WHERE category_id = ?i",
        $category_id
    );
    return $is_trash == 'Y';
}

/**
 * Adds product to trash category
 *
 * @param int $product_id Product ID
 * @param int $trash_category_id Trash category ID
 */
function fn_add_product_to_trash($product_id, $trash_category_id)
{
    $data = array(
        'product_id' => $product_id,
        'category_id' => $trash_category_id,
        'position' => 0,
        'link_type' => 'M'
    );
    db_query("INSERT INTO ?:products_categories ?e", $data);
}

/**
 * Moves products left without categories in their store to trash
 *
 * @param array $category_ids Deleted categories identifiers
 * @return array Deleted products identifiers
 */
function fn_trash_orphaned_products($category_ids)
{
    $orphaned_products = array();
    $trashes = array();
    $category_ids = array_unique($category_ids);

    if ($category_ids) {
        $narrowed_products_list = db_get_fields(
            "SELECT DISTINCT product_id"
            . " FROM ?:products_categories"
            . " WHERE category_id IN (?n)",
            $category_ids
        );

        if (!empty($narrowed_products_list)) {
            $orphaned_products = db_get_hash_single_array(
                "SELECT"
                    . " cp.product_id,"
                    . " p.company_id,"
                    . " c.category_id,"
                    . " GROUP_CONCAT(c.category_id) AS owner_groups"
                . " FROM ?:products p"
                . " LEFT JOIN ?:products_categories cp"
                    . " ON p.product_id = cp.product_id"
                . " LEFT JOIN ?:categories c"
                    . " ON cp.category_id = c.category_id"
                        . " AND p.company_id = c.company_id"
                . " WHERE p.product_id in (?n)"
                . " GROUP BY cp.product_id"
                . " HAVING owner_groups IS NULL",
                array('product_id', 'company_id'),
                $narrowed_products_list
            );

            db_query("DELETE FROM ?:products_categories"
                . " WHERE category_id IN (?n)",
                $category_ids
            );

            if (!empty($orphaned_products)) {
                // Deleting product associations
                db_query("DELETE FROM ?:products_categories"
                    . " WHERE product_id IN (?n)",
                    array_keys($orphaned_products)
                );

                // Moving products to trash
                foreach($orphaned_products as $product_id => $company_id) {
                    if (!isset($trashes[$company_id])) {
                        $trash_category_id = fn_get_trash_category($company_id);
                        if (!$trash_category_id) {
                            $trash_category_id = fn_create_trash_category($company_id);
                        }
                        $trashes[$company_id] = $trash_category_id;
                    }
                    fn_add_product_to_trash($product_id, $trashes[$company_id]);
                }

                fn_update_product_count();
            }
        }
    }

    return array($orphaned_products, $trashes);
}

/**
 * Deletes products from trash category
 *
 * @param int $trash_category_id Trash category identifier
 * @return array Deleted product identifiers
 */
function fn_empty_trash($trash_category_id)
{
    $products_to_delete = db_get_fields(
        "SELECT DISTINCT product_id"
        . " FROM ?:products_categories"
        . " WHERE category_id = ?i",
        $trash_category_id
    );

    if (!empty($products_to_delete)) {
        foreach($products_to_delete as $product_id) {
            fn_delete_product($product_id);
        }
    }

    return $products_to_delete;
}

/**
 * Filtering product data before save
 *
 * @param  array &$request      $_REQUEST
 * @param  array &$product_data Product data
 */
function fn_filter_product_data(&$request, &$product_data)
{
    /**
     * Filtering product data
     *
     * @param array $request      $_REQUEST
     * @param array $product_data $product_data
     */
    fn_set_hook('filter_product_data', $request, $product_data);
}

/**
 * Gets list of category identifiers with parent categories.
 *
 * @param array|int $category_ids List of category identifier
 * @return array
 */
function fn_get_category_ids_with_parent($category_ids)
{
    $result = array();
    $category_ids = (array) $category_ids;

    if (!empty($category_ids)) {
        $result = explode('/', implode('/', db_get_fields("SELECT id_path FROM ?:categories WHERE category_id IN (?n)", $category_ids)));
        $result = array_unique($result);
    }

    return $result;
}

/**
 * Sets the disabled status for filters related with product feature.
 *
 * @param int $product_feature_id Product feature identifier
 * @return boolean
 */
function fn_disable_product_feature_filters($product_feature_id)
{
    $filter_ids = db_get_fields("SELECT filter_id FROM ?:product_filters WHERE feature_id = ?i AND status = 'A'", $product_feature_id);

    if (!empty($filter_ids)) {
        db_query("UPDATE ?:product_filters SET status = 'D' WHERE filter_id IN (?n)", $filter_ids);
        $filter_names_array = db_get_fields("SELECT filter FROM ?:product_filter_descriptions WHERE filter_id IN (?n) AND lang_code = ?s", $filter_ids, DESCR_SL);

        fn_set_notification('W', __('warning'), __('text_product_filters_were_disabled', array(
            '[url]' => fn_url('product_filters.manage'),
            '[filters_list]' => implode(', ', $filter_names_array)
        )));

        return true;
    }

    return false;
}

/**
 * Gets amount of a product in stock.
 *
 * @param int $product_id Product identifier
 *
 * @return int Amount
 */
function fn_get_product_amount($product_id)
{
    $amount = db_get_field(
        'SELECT IF (prod.tracking = ?s, MAX(inv.amount), prod.amount)'
        . ' FROM ?:products AS prod'
        . ' LEFT JOIN ?:product_options_inventory AS inv'
            . ' ON inv.product_id = prod.product_id'
        . ' WHERE prod.product_id = ?i',
        ProductTracking::TRACK_WITH_OPTIONS,
        $product_id
    );

    return (int)$amount;
}
