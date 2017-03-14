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

use Tygh\Enum\ProductFeatures;
use Tygh\Registry;
use Tygh\Storage;

/**
 * Changes company_id for options in the table product options
 *
 * @param integer  $product_id      Product identifier
 * @param string   $company_name    Company name
 * @param array    $processed_data  Quantity of the loaded objects. Objects:
 *                                  'E' - quantity existent products, 'N' - quantity new products,
 *                                  'S' - quantity skipped products, 'C' - quantity vendors
 *
 * @return integer $company_id Company identifier
 */
function fn_exim_set_product_company($product_id, $company_name, &$processed_data)
{
    $company_id = fn_exim_set_company('products', 'product_id', $product_id, $company_name, $processed_data);

    if ($company_id) {
        // Assign company_id to all product options
        $options_ids = db_get_fields('SELECT option_id FROM ?:product_options WHERE product_id = ?i', $product_id);
        if ($options_ids) {
            db_query("UPDATE ?:product_options SET company_id = ?s WHERE option_id IN (?n)", $company_id, $options_ids);
        }
    }

    return $company_id;
}

/**
 * Creates categories tree by path
 *
 * @param integer $product_id Product ID
 * @param string $link_type M - main category, A - additional
 * @param string $categories_data categories path
 * @param string $category_delimiter Delimiter in categories path
 * @param string $store_name Store name (is used for saving category company_id)
 * @return boolean True if any categories were updated.
 */
function fn_exim_set_product_categories($product_id, $link_type, $categories_data, $category_delimiter, $store_name = '')
{
    if (fn_is_empty($categories_data)) {
        return false;
    }

    $set_delimiter = ';';
    if (fn_allowed_for('ULTIMATE')) {
        $store_delimiter = ':';
        $paths_store = array();
    }

    $paths = array();
    $updated_categories = array();

    foreach ($categories_data as $lang => $data) {
        $_paths = str_getcsv($data, $set_delimiter, "'");
        array_walk($_paths, 'fn_trim_helper');

        foreach ($_paths as $k => $cat_path) {
            if (fn_allowed_for('ULTIMATE') && strpos($cat_path, $store_delimiter)) {
                $cat_path = str_getcsv($cat_path, $store_delimiter, '|');

                if (count($cat_path) > 1) {
                    $paths_store[$k] = reset($cat_path);
                    $cat_path = $cat_path[1];
                } else {
                    $cat_path = reset($cat_path);
                }
            }

            $category = (strpos($cat_path, $category_delimiter) !== false) ? explode($category_delimiter, $cat_path) : array($cat_path);
            foreach ($category as $key_cat => $cat) {
                $paths[$k][$key_cat][$lang] = $cat;
            }
        }
    }

    if (!fn_is_empty($paths)) {
        $category_condition = '';
        $joins = '';
        $select = '?:products_categories.*';
        if (fn_allowed_for('ULTIMATE')) {
            $joins = ' JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id ';
            $category_condition = fn_get_company_condition('?:categories.company_id');
            $select .= ', ?:categories.category_id, ?:categories.company_id';
        }

        $cat_ids = array();
        $old_data = db_get_hash_array("SELECT $select FROM ?:products_categories $joins WHERE product_id = ?i AND link_type = ?s $category_condition", 'category_id', $product_id, $link_type);
        foreach ($old_data as $k => $v) {
            if ($v['link_type'] == $link_type) {
                $updated_categories[] = $k;
            }
            $cat_ids[] = $v['category_id'];
        }
        if (!empty($cat_ids)) {
            db_query("DELETE FROM ?:products_categories WHERE product_id = ?i AND category_id IN (?n)", $product_id, $cat_ids);
        }
    }

    $company_id = 0;
    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id')) {
            $company_id = Registry::get('runtime.company_id');
        } else {
            $company_id = fn_get_company_id_by_name($store_name);

            if (!$company_id) {
                $company_data = array('company' => $store_name, 'email' => '');
                $company_id = fn_update_company($company_data, 0);
            }
        }
    }

    foreach ($paths as $key_path => $categories) {

        if (!empty($categories)) {
            $parent_id = '0';

            foreach ($categories as $cat) {
                $category_condition = '';
                if (fn_allowed_for('ULTIMATE')) {
                    if (!empty($paths_store[$key_path]) && !Registry::get('runtime.company_id')) {
                        $path_company_id = fn_get_company_id_by_name($paths_store[$key_path]);
                        $category_condition = fn_get_company_condition('?:categories.company_id', true, $path_company_id);
                    } else {
                        $category_condition = fn_get_company_condition('?:categories.company_id', true, $company_id);
                    }
                }

                reset($cat);
                $main_lang = key($cat);
                $main_cat = array_shift($cat);

                $category_id = db_get_field("SELECT ?:categories.category_id FROM ?:category_descriptions INNER JOIN ?:categories ON ?:categories.category_id = ?:category_descriptions.category_id $category_condition WHERE ?:category_descriptions.category = ?s AND lang_code = ?s AND parent_id = ?i", $main_cat, $main_lang, $parent_id);

                if (!empty($category_id)) {
                    $parent_id = $category_id;
                } else {

                    $category_data = array(
                        'parent_id' => $parent_id,
                        'category' =>  $main_cat,
                        'timestamp' => TIME,
                    );

                    if (fn_allowed_for('ULTIMATE')) {
                        $category_data['company_id'] = !empty($path_company_id) ? $path_company_id : $company_id;
                    }

                    $category_id = fn_update_category($category_data);

                    foreach ($cat as $lang => $cat_data) {
                        $category_data = array(
                            'parent_id' => $parent_id,
                            'category' => $cat_data,
                            'timestamp' => TIME,
                        );

                        if (fn_allowed_for('ULTIMATE')) {
                            $category_data['company_id'] = $company_id;
                        }

                        fn_update_category($category_data, $category_id, $lang);
                    }

                    $parent_id = $category_id;
                }

            }

            $data = array(
                'product_id' => $product_id,
                'category_id' => $category_id,
                'link_type' => $link_type,
            );

            if (!empty($old_data) && !empty($old_data[$category_id])) {
                $data = fn_array_merge($old_data[$category_id], $data);
            }

            db_query("REPLACE INTO ?:products_categories ?e", $data);

            $updated_categories[] = $category_id;
        }
    }

    if (!empty($updated_categories)) {
        fn_update_product_count($updated_categories);

        return true;
    }

    return false;
}

/**
 * Export product categories
 *
 * @param int $product_id product ID
 * @param string $link_type M - main category, A - additional
 * @param string $category_delimiter path delimiter
 * @param string $lang_code 2 letters language code
 * @return string
 */
function fn_exim_get_product_categories($product_id, $link_type, $category_delimiter, $lang_code = '')
{
    $set_delimiter = '; ';
    $conditions = '';
    if (fn_allowed_for('ULTIMATE')) {
        $store_delimiter = ':';
        $conditions = fn_get_company_condition('?:categories.company_id');
    }

    $joins = ' JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id ';

    $category_ids = db_get_fields("SELECT ?:products_categories.category_id FROM ?:products_categories $joins WHERE product_id = ?i AND link_type = ?s $conditions", $product_id, $link_type);

    $result = array();
    foreach ($category_ids as $c_id) {
        if (fn_allowed_for('ULTIMATE')) {
            if ($link_type == 'A' && !Registry::get('runtime.company_id')) {
                $company_id = fn_get_company_id('categories', 'category_id', $c_id);
                $company_name = fn_exim_wrap_value(fn_get_company_name($company_id), '|', $store_delimiter);
                $result[] = $company_name . $store_delimiter . fn_exim_wrap_value(fn_get_category_path($c_id, $lang_code, $category_delimiter), '|', $store_delimiter);
            } else {
                $result[] = fn_exim_wrap_value(fn_get_category_path($c_id, $lang_code, $category_delimiter), '|', $store_delimiter);
            }
        } else {
            $result[] = fn_get_category_path($c_id, $lang_code, $category_delimiter);
        }
    }

    return implode($set_delimiter, fn_exim_wrap_value($result, "'", $set_delimiter));
}

//
// Export product taxes
// Parameters:
// @product_id - product ID
// @lang_code - language code

function fn_exim_get_taxes($product_taxes, $lang_code = '')
{
    $taxes = db_get_fields("SELECT tax FROM ?:tax_descriptions WHERE FIND_IN_SET(tax_id, ?s) AND lang_code = ?s", $product_taxes, $lang_code);

    return implode(', ', fn_exim_wrap_value($taxes, "'", ','));
}

//
// Import product taxes
// Parameters:
// @product_id - product ID
// @data - comma delimited list of taxes

function fn_exim_set_taxes($product_id, $data)
{
    if (empty($data)) {
        db_query("UPDATE ?:products SET tax_ids = '' WHERE product_id = ?i", $product_id);

        return true;
    }

    $multi_lang = array_keys($data);
    $main_lang = reset($multi_lang);
    $taxes = str_getcsv($data[$main_lang], ',', "'");
    array_walk($taxes, 'fn_trim_helper');

    $tax_ids = db_get_fields("SELECT tax_id FROM ?:tax_descriptions WHERE tax IN (?a) AND lang_code = ?s", $taxes, $main_lang);

    $_data = array(
        'tax_ids' => fn_create_set($tax_ids)
    );

    db_query('UPDATE ?:products SET ?u WHERE product_id = ?i', $_data, $product_id);

    return true;
}

//
// Export product features
// Parameters:
// @product_id - product ID
// @lang_code - language code
function fn_exim_get_product_features($product_id, $features_delimiter, $lang_code = CART_LANGUAGE)
{
    static $features;

    if (!isset($features[$lang_code])) {
        list($features[$lang_code]) = fn_get_product_features(array('plain' => true), 0, $lang_code);
    }

    $category_ids = db_get_fields('SELECT category_id FROM ?:products_categories WHERE product_id = ?i', $product_id);

    $product = array(
        'product_id' => $product_id,
        'category_ids' => $category_ids
    );

    $product_features = fn_get_product_features_list($product, 'EXIM', $lang_code);
    $product_features = fn_sort_array_by_key($product_features, 'feature_id');

    $pair_delimiter = ':';
    $set_delimiter = ';';

    $result = array();

    if (!empty($product_features)) {
        foreach ($product_features as $f) {
            $parent = '';
            if (!empty($f['parent_id'])) {
                $parent = '(' . str_replace($set_delimiter, '\\\\' . $set_delimiter, $features[$lang_code][$f['parent_id']]['description']) . ') ';
            }
            $f['value_int'] = (empty($f['value_int']))? 0 : floatval($f['value_int']);
            if (!empty($f['value']) || !empty($f['value_int'])) {
                $result[] = $parent . "{$f['description']}{$pair_delimiter} {$f['feature_type']}[" . (!empty($f['value']) ? str_replace($set_delimiter, '\\\\' . $set_delimiter, $f['value']) : $f['value_int']) . ']';
            } else {
                $_params = array(
                    'feature_id' => $f['feature_id'],
                    'product_id' => $product_id,
                    'feature_type' => $f['feature_type'],
                    'selected_only' => true
                );

                list($variants) = fn_get_product_feature_variants($_params, 0, $lang_code);

                if ($variants) {
                    $values = array();
                    foreach ($variants as $v) {
                        $values[] = str_replace($set_delimiter, '\\\\' . $set_delimiter, $v['variant']);
                    }

                    $feature_description = str_replace($set_delimiter, '\\\\' . $set_delimiter, $f['description']);
                    $result[] = $parent . "{$feature_description}{$pair_delimiter} {$f['feature_type']}[" . implode($features_delimiter, $values) . ']';
                }
            }
        }
    }

    return !empty($result) ? implode($set_delimiter . ' ', $result) : '';
}

/**
 * Import product features
 *
 * @param int       $product_id         Product identifier
 * @param array     $data               Array of delimited lists of product features and their values
 * @param string    $features_delimiter Delimiter symbol
 * @param string    $lang_code          Language code
 * @param string    $store_name         Store name
 *
 * @return boolean Always true
 */
function fn_exim_set_product_features($product_id, $data, $features_delimiter, $lang_code, $store_name = '')
{
    reset($data);
    $main_lang = key($data);
    $company_id = 0;
    $runtime_company_id = fn_get_runtime_company_id();

    if (fn_allowed_for('ULTIMATE')) {
        if ($runtime_company_id) {
            $company_id = $runtime_company_id;
        } else {
            $company_id = fn_get_company_id_by_name($store_name);
        }
    }

    $features = fn_exim_parse_features($data, $features_delimiter);

    foreach ($features as $key => &$feature) {
        if (!empty($feature['group_name'])) {
            $feature_group = array(
                'name' => $feature['group_name'],
                'names' => $feature['group_names'],
                'type' => ProductFeatures::GROUP,
                'parent_id' => 0
            );

            $feature_group = fn_exim_save_product_feature($feature_group, $company_id, $main_lang);

            if ($feature_group === false) {
                unset($features[$key]);
                continue;
            }

            $feature['parent_id'] = $feature_group['feature_id'];
        } else {
            $feature['parent_id'] = 0;
        }

        $feature = fn_exim_save_product_feature($feature, $company_id, $main_lang);

        if ($feature === false) {
            unset($features[$key]);
            continue;
        }
    }
    unset($feature);

    if (!empty($features)) {
        fn_exim_save_product_features_values($product_id, $features, $main_lang);
    }

    return true;
}

function fn_exim_product_feature_variants($feature, $feature_id, $variants, $lang_code)
{
    $feature_type = $feature['type'];
    if (strpos(ProductFeatures::getSelectable(), $feature_type) !== false) { // variant IDs

        $vars = array();
        foreach ($feature['variants'] as $variant) {
            $vars[] = $variant['name'];
        }

        $existent_variants = db_get_hash_single_array(
            'SELECT pfvd.variant_id, variant FROM ?:product_feature_variant_descriptions AS pfvd ' .
            'LEFT JOIN ?:product_feature_variants AS pfv ON pfv.variant_id = pfvd.variant_id ' .
            'WHERE feature_id = ?i AND variant IN (?a) AND lang_code = ?s',
            array('variant_id', 'variant'), $feature_id, $vars, $lang_code
        );

        foreach ($feature['variants'] as $variant_data) {
            if (!in_array($variant_data['name'], $existent_variants)) {
                if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
                    fn_set_notification('W', __('warning'), __('exim_vendor_cant_create_feature'));
                    continue;
                }
                $variant_id = fn_add_feature_variant($feature_id, array('variant' => $variant_data['name']));
                $existent_variants[$variant_id] = $variant_data['name'];
            }
        }

        if ($feature_type == ProductFeatures::MULTIPLE_CHECKBOX) {

            foreach ($feature['variants'] as $variant_data) {
                if (in_array($variant_data['name'], $existent_variants)) {
                    $variant_id = array_search($variant_data['name'], $existent_variants);
                    $variants[$feature_id][$variant_id] = $variant_id;
                }

            }
        } else {
            $variant_data = reset($feature['variants']);

            if (in_array($variant_data['name'], $existent_variants)) {
                $variant_id = array_search($variant_data['name'], $existent_variants);
                $variants[$feature_id] = $variant_id;
            }
        }

    } else {
        $variant_data = reset($feature['variants']);
        $variants[$feature_id] = $variant_data['name'];
    }

    return $variants;
}

/**
 * If feature group exists return it id, else create such groups for all available langs
 *
 * @param string $group Group name
 * @param int $company_id Company identifier
 * @param string $lang_code 2-letter language code
 *
 * @return integer ID of group
 */
function fn_exim_check_feature_group($group, $company_id, $lang_code)
{
    $group_id = fn_exim_find_feature_group_id($group, $lang_code, $company_id);

    if (fn_allowed_for('ULTIMATE') && empty($group_id) && !empty($company_id)) {
        $group_id = fn_exim_find_feature_group_id($group, $lang_code);
    }

    if (empty($group_id)) {
        $group_data = array(
            'feature_id' => 0,
            'description' => $group,
            'lang_code' => $lang_code,
            'feature_type' => ProductFeatures::GROUP,
            'company_id' => $company_id,
            'status' => 'A'
        );

        $group_id = fn_update_product_feature($group_data, 0, $lang_code);
    }

    if (fn_allowed_for('ULTIMATE')) {
        fn_exim_update_share_feature($group_id, $company_id);
    }

    return $group_id;
}

function fn_exim_get_option_attrs()
{
    $export_fields = array(
        'allowed_extensions',
        'inventory',
        'max_file_size',
        'missing_variants_handling',
        'multiupload',
        'required',
        'status'
    );

    return $export_fields;
}

//
// Export product options
// Parameters:
// @product_id - product ID
// @lang_code - language code
function fn_exim_get_product_options($product_id, $lang_code = '', $features_delimiter = '///')
{
    $pair_delimiter = ':';
    $set_delimiter = '; ';
    $vars_delimiter = ',';

    $export_fields = fn_exim_get_option_attrs();

    $result = array();
    $options = fn_get_product_options($product_id, $lang_code);

    if (!empty($options)) {
        foreach ($options as $o) {
            $glob_opt = db_get_field("SELECT option_id FROM ?:product_global_option_links WHERE option_id = ?i AND product_id = ?i", $o['option_id'], $product_id);

            $prefix = '';
            if (!empty($o['company_id'])) {
                $company_name = fn_get_company_name($o['company_id']);
                $prefix = '(' . $company_name . ') ';
            }

            $str = $prefix . "$o[option_name]$pair_delimiter $o[option_type]" . (empty($glob_opt) ? '' : 'G');

            $variants = array();

            if (!empty($o['variants'])) {
                foreach ($o['variants'] as $v) {
                    $variant = $v['variant_name'];

                    if (floatval($v['modifier']) != 0) {
                        $variant .= $features_delimiter . 'modifier=' . $v['modifier'];
                        $variant .= $features_delimiter . 'modifier_type=' . $v['modifier_type'];
                    }

                    if (floatval($v['weight_modifier']) != 0) {
                        $variant .= $features_delimiter . 'weight_modifier=' . $v['weight_modifier'];
                        $variant .= $features_delimiter . 'weight_modifier_type=' . $v['weight_modifier_type'];
                    }

                    if (!empty($v['image_pair']['image_id'])) {
                        $variant .= $features_delimiter . 'image=' . fn_export_image($v['image_pair']['image_id'], 'variant_image', '', false);
                    }

                    fn_set_hook('exim_product_variant', $v, $variant);

                    $variants[] = $variant;
                }
                $str .= '[' .implode($vars_delimiter, str_replace($vars_delimiter, '\\\\' . $vars_delimiter, $variants)). ']';
                fn_set_hook('exim_product_option', $o, $str);
            }
            foreach ($export_fields as $field) {
                if (!empty($o[$field])) {
                    $str .= $features_delimiter . $field . '=' . str_replace(array('[', ']'), array('', ''), $o[$field]);
                }
            }
            $result[] = $str;
        }
    }

    return !empty($result) ? implode($set_delimiter, $result) : '';
}

/**
 * Import product options
 *
 * @param int $product_id Product ID
 * @param array $data Delimited list of product options and their values
 * @param string $lang_code Language code
 * @param string $features_delimiter Feature delimiter chars
 * @return bool
 */
function fn_exim_set_product_options($product_id, $data, $lang_code, $features_delimiter)
{
    reset($data);
    $main_lang = key($data);
    $main_options = array();

    foreach ($data as $lang_code => $options) {
        //for compatibility with the old format
        $options = preg_replace('{\{\d*\}}', '', $options);

        if (!fn_is_empty($options)) {
            reset($main_options);

            $options = fn_exim_parse_data($options, ',', $features_delimiter, true);
            $updated_ids = array(); // store updated ids, delete other (if exist)

            foreach ($options as $option_key => $option) {
                unset($_REQUEST['file_variant_image_image_icon'], $_REQUEST['variant_image_image_data']);
                $global_option = (isset($option['global'])) ? $option['global'] : false;

                if (!empty($option['group_name'])) {
                    $company_id = fn_get_company_id_by_name($option['group_name']);
                }

                if ($lang_code == $main_lang) {
                    $condition = "d.option_name = ?s AND d.lang_code = ?s";

                    if (!empty($company_id)) {
                        $condition .= " AND o.company_id = {$company_id}";
                    }

                    $sql = <<<SQL
SELECT o.option_id FROM ?:product_options_descriptions as d
  INNER JOIN ?:product_options as o ON o.option_id = d.option_id AND o.product_id = ?i
  WHERE {$condition}
  LIMIT 1
SQL;

                    $option_id = db_get_field($sql, ($global_option) ? 0 : $product_id, $option['name'], $lang_code);
                } else {
                    $option_id = key($main_options);

                    if (empty($option_id)) {
                        continue 2;
                    }

                    next($main_options);
                }

                $variant_ids = array();
                $option['variants'] = isset($option['variants']) ? $option['variants'] : array();

                if ($lang_code == $main_lang) {
                    foreach ($option['variants'] as $variant_pos => $variant) {
                        $variant_ids[$variant_pos] = db_get_field(
                            "SELECT d.variant_id FROM ?:product_option_variants_descriptions as d "
                            . "INNER JOIN ?:product_option_variants as o ON o.variant_id = d.variant_id AND o.option_id = ?i "
                            . "WHERE d.variant_name = ?s AND d.lang_code = ?s LIMIT 1",
                            $option_id, $variant['name'], $lang_code
                        );
                    }
                } else {
                    $var_ids = $main_options[$option_id];
                    reset($var_ids);

                    foreach ($option['variants'] as $variant_pos => $variant) {
                        $variant_id = current($var_ids);

                        if ($variant_id === false) {
                            continue 3;
                        }

                        next($var_ids);
                        $variant_ids[$variant_pos] = $variant_id;
                    }
                }

                $option_data = fn_exim_build_option_data($option, $option_id, $variant_ids, $lang_code);

                if (empty($option_data)) {
                    continue;
                }

                $option_data['company_id'] = (!empty($company_id)) ? $company_id : 0;

                // Prepare variant images
                if (!empty($option_data['variants'])) {
                    foreach ($option_data['variants'] as $key => $variant) {
                        if (!empty($variant['image'])) {
                            $variant_id = (!empty($variant['variant_id'])) ? $variant['variant_id'] : 0;
                            $image_pairs = fn_get_image_pairs($variant_id, 'variant_image', 'V', true, true, $lang_code);
                            $pair_id = (!empty($image_pairs['pair_id'])) ? $image_pairs['pair_id'] : 0;

                            $_REQUEST['file_variant_image_image_icon'][$key] = $variant['image'];
                            $_REQUEST['type_variant_image_image_icon'][$key] = (strpos($variant['image'], '://') === false) ? 'server' : 'url';
                            $_REQUEST['variant_image_image_data'][$key] = array(
                                'pair_id' => $pair_id,
                                'type' => 'V',
                                'object_id' => 0,
                                'image_alt' => '',
                            );
                        }
                    }
                }

                if (empty($option_id)) {
                    $option_data['product_id'] = !empty($global_option) ? 0 : $product_id;
                    $option_data['position'] = $option_key;

                    $updated_id = fn_update_product_option($option_data, 0, $lang_code);
                } else {
                    $updated_id = fn_update_product_option($option_data, $option_id, $lang_code);
                }

                if ($lang_code == $main_lang) {
                    if ($global_option) {
                        $glob_link = array(
                            'option_id' => $updated_id,
                            'product_id' => $product_id
                        );
                        db_query('REPLACE INTO ?:product_global_option_links ?e', $glob_link);
                    }

                    $main_options[$updated_id] = array();

                    foreach ($option['variants'] as $variant_pos => $variant) {
                        $main_options[$updated_id][] = db_get_field(
                            "SELECT d.variant_id FROM ?:product_option_variants_descriptions as d "
                            . "INNER JOIN ?:product_option_variants as o ON o.variant_id = d.variant_id AND o.option_id = ?i "
                            . "WHERE d.variant_name = ?s AND d.lang_code = ?s LIMIT 1",
                            $updated_id, $variant['name'], $lang_code
                        );
                    }
                }

                $updated_ids[] = $updated_id;
            }

            // Delete all other options
            if ($lang_code == $main_lang && !empty($updated_ids)) {
                $obsolete_ids = db_get_fields("SELECT option_id FROM ?:product_options WHERE option_id NOT IN (?n) AND product_id = ?i", $updated_ids, $product_id);
                if (!empty($obsolete_ids)) {
                    foreach ($obsolete_ids as $o_id) {
                        fn_delete_product_option($o_id, $product_id);
                    }
                }
            }
        }
    }

    return true;
}

function fn_exim_parse_data($data, $variants_delimiter = ',', $features_delimiter = '///', $is_option = false)
{
    $pair_delimiter = ':';
    $set_delimiter = '; ';
    $_data = array();
    $o_position = 0;
    //$options = explode($set_delimiter, $data);
    $options = preg_split('/(?<!\\\)\\' . $set_delimiter . '/', $data);
    foreach ($options as $option) {
        $_pair = $pair = array();
        $o_position += 10;
        //Fix for situation when frature variant contain $pair_delimiter: 'test: T[http://www.example.com/]; Brand: E[Adidas]'
        $_pair = explode($pair_delimiter, $option);
        $pair[0] = array_shift($_pair);
        $pair[1] = implode($pair_delimiter, $_pair);

        preg_match('/\(([^\)]*)\)\s*(.+)/', $pair[0], $group);
        if (!empty($group[2])) {
            $pair[0] = $group[2];
        }
        $variants = array();
        if (is_array($pair)) {
            array_walk($pair, 'fn_trim_helper');
            $_data[$o_position]['type'] = substr($pair[1], 0, 1);
            if (substr($pair[1], 1, 1) == 'G') {
                $_data[$o_position]['global'] = true;
            }

            $_data[$o_position]['name'] = $pair[0];

            if (!empty($group[1])) {
                $_data[$o_position]['group_name'] = $group[1];
            }

            if (($pos = strpos($pair[1], '[')) !== false) { // option has variants
                $variants = substr($pair[1], $pos + 1, strrpos($pair[1], ']') - $pos - 1);
                if ($is_option) {
                    $_variants = preg_split('/(?<!\\\)\\' . $variants_delimiter . '/', $variants);
                    if (empty($_variants)) {
                        $variants = (array)$variants;
                    } else {
                        foreach ($_variants as $k => $variant) {
                            $_variants[$k] = preg_replace('/\\\{1}(' . $variants_delimiter . ')/', '$1', $variant);
                        }
                        $variants = $_variants;
                    }
                } else {
                    $variants = explode($variants_delimiter, $variants);
                    foreach ($variants as $k => $variant) {
                        $variants[$k] = preg_replace('/\\\{1}(;)/', '$1', $variant);
                    }
                }
            }

            $attributes = preg_replace('/\[.*\]/', '', $pair[1]);
            $attributes = explode($features_delimiter, $attributes);

            foreach ($attributes as $attr) {
                if (strpos($attr, '=') !== false) {
                    list($name, $value) = explode('=', $attr);
                    $_data[$o_position][$name] = $value;
                }
            }

            $position = 0;

            foreach ($variants as $variant) {
                $position += 10;
                $variant = explode($features_delimiter, $variant);
                $_data[$o_position]['variants'][$position]['name'] = trim(array_shift($variant));

                if (!empty($variant)) {
                    foreach ($variant as $variant_attr) {
                        list($attr_name, $attr_value) = explode('=', $variant_attr);
                        $_data[$o_position]['variants'][$position][$attr_name] = $attr_value;
                    }
                }
            }
        }
    }

    return $_data;
}

function fn_exim_build_option_data($option, $option_id, $variant_ids, $lang_code)
{
    $variants = array();

    if (empty($option['name'])) {
        return array();
    }

    // if unknown option type
    if (empty($option['type']) ||  strpos('SRCITF', $option['type']) === false) {
        if (!empty($option['variants'])) {
            $option['type'] = 'S'; // if option has variants, set as select box
        } else {
            $option['type'] = 'I'; // if does not have variants, set as text
        }
    }

    if ($option['type'] == 'C') {
        $variant = array();

        if (!empty($option_id)) { // check if variant exist
            $variant = db_get_row("SELECT * FROM ?:product_option_variants WHERE option_id = ?i AND position = 1", $option_id);
        }

        if (empty($variant)) {
            $variant = array(
                'position' => 1,
            );
        }

        if (!empty($option['variants'])) {
            $variant = end($option['variants']);
            $variant['position'] = 1;
            unset($variant['name']);
        }

        $variants = array($variant);
    } else {

        foreach ($option['variants'] as $variant_pos => $variant) {
            $variants[$variant_pos] = array(
                'variant_name' => $variant['name'],
                'position' => $variant_pos,
            );

            unset($variant['name']);
            $variants[$variant_pos] = array_merge($variants[$variant_pos], $variant);

            if (!empty($variant_ids[$variant_pos])) {
                $variants[$variant_pos]['variant_id'] = $variant_ids[$variant_pos];
            }
        }
    }

    $export_fields = fn_exim_get_option_attrs();

    $option_data = array(
        'option_name' => $option['name'],
        'option_type' => $option['type'],
        'variants' => $variants,
    );

    foreach ($export_fields as $field) {
        if (!empty($option[$field])) {
            $option_data[$field] = $option[$field];
        }
    }

    return $option_data;
}

//
// Export product files
// @product_id 0- product ID
// @path - path to store files
//
function fn_exim_export_file($product_id, $path)
{
    $path = fn_get_files_dir_path() . fn_normalize_path($path);

    $files = db_get_array("SELECT file_path, preview_path, pfolder.folder_id FROM ?:product_files as pfiles"
                          ." LEFT JOIN ?:product_file_folders as pfolder ON pfolder.folder_id = pfiles.folder_id"
                          ." WHERE pfiles.product_id = ?i", $product_id);

    if (!empty($files)) {
        // If backup path is set, check if it exists and copy files there
        if (!empty($path)) {
            if (!fn_mkdir($path)) {
                fn_set_notification('E', __('error'), __('text_cannot_create_directory', array(
                    '[directory]' => fn_get_rel_dir($path)
                )));

                return '';
            }
        }

        $_data = array();
        foreach ($files as $file) {
            Storage::instance('downloads')->export($product_id . '/' . $file['file_path'], $path . '/' . $file['file_path']);

            if (!empty($file['preview_path'])) {
                Storage::instance('downloads')->export($product_id . '/' . $file['preview_path'], $path . '/' . $file['preview_path']);
            }

            $file_data = $file['file_path'];

            if (!empty($file['folder_id'])) {
                $file_data = $file['folder_id'].'/'.$file_data;
            }

            if (!empty($file['preview_path'])) {
                $file_data = $file_data.'#'.$file['preview_path'];
            }

            $_data[] = $file_data;

        }

        return implode(', ', $_data);
    }

    return '';
}

//
// Import product files
// @product_id 0- product ID
// @filename - file name
// @path - path to search files in
// @delete_files - flag - delete all product files before import
function fn_exim_import_file($product_id, $filename, $path, $delete_files = 'N')
{
    $path = fn_get_files_dir_path() . fn_normalize_path($path);

    // Clean up the directory above if flag is set
    if ($delete_files == 'Y') {
        fn_delete_product_file_folders(0, $product_id);
        fn_delete_product_files(0, $product_id);
    }

    // Check if we have several files
    $files = fn_explode(',', $filename);
    $folders = array();

    // Create folders
    foreach ($files as $file) {
        if (strpos($file, '/') !== false) {
            list($folder) = fn_explode('/', $file);

            if (!isset($folders[$folder])) {
                $folder_data = array(
                    'product_id' => $product_id,
                    'folder_name' => $folder,
                );
                $folders[$folder] = fn_update_product_file_folder($folder_data, 0);
            }
        }
    }

    // Copy files
    foreach ($files as $file) {

        if (strpos($file, '/') !== false) {
            list($folder_name, $file) = fn_explode('/', $file);
        } else {
            $folder_name = '';
        }

        if (strpos($file, '#') !== false) {
            list($f, $pr) = fn_explode('#', $file);
        } else {
            $f = $file;
            $pr = '';
        }

        $file = fn_find_file($path, $f);

        if (!empty($file)) {

            $uploads = array(
                'file_base_file' => array($file),
                'type_base_file' => array('server')
            );

            if (!empty($pr)) {

                $preview = fn_find_file($path, $pr);
                if (!empty($preview)) {
                    $uploads['file_file_preview'] = array($preview);
                    $uploads['type_file_preview'] = array('server');
                }
            } else {
                $uploads['file_file_preview'] = "";
                $uploads['type_file_preview'] = "";
            }

            $_REQUEST = fn_array_merge($_REQUEST, $uploads); // not good to add data to $_REQUEST

            $file_data = array(
                'product_id' => $product_id,
            );

            if (!empty($folder_name)) {
               $file_data['folder_id'] = $folders[$folder_name];
            }

            if (fn_update_product_file($file_data, 0) == false) {
                return false;
            }
        }
    }

    return true;
}

//
// Inserts ID of element to the csv file
// @item_id - id of an item
//
//function fn_exim_post_item_id($item_id)
//{
//    return '{' . $item_id . '}';
//}

//
// Gets element ID and updates the element name
// @element - element
//
function fn_exim_get_item_id(&$element)
{
    $item_id = '';
    if ($item_id = substr($element, 0, strpos($element, '}'))) {
        $element = substr($element, strpos($element, '}') + 1);
        $item_id = substr($item_id, 1);
    }

    return $item_id;
}

// Import preprocessor
function fn_exim_reset_inventory($reset_inventory)
{
    // Reset inventory to zero before import
    if ($reset_inventory == 'Y') {
        if (Registry::get('runtime.company_id')) {
            $i = 0;
            $step = 1000;
            while ($product_ids = db_get_fields("SELECT product_id FROM ?:products WHERE company_id = ?i LIMIT $i, $step", Registry::get('runtime.company_id'))) {
                $i += $step;
                db_query("UPDATE ?:products SET amount = 0 WHERE product_id IN (?n)", $product_ids);
                db_query("UPDATE ?:product_options_inventory SET amount = 0 WHERE product_id IN (?n)", $product_ids);
            }
        } else {
            db_query("UPDATE ?:products SET amount = 0");
            db_query("UPDATE ?:product_options_inventory SET amount = 0");
        }
    }

    return true;
}

if (!fn_allowed_for('ULTIMATE:FREE')) {
/**
 * Assign localizations to the product
 *
 * @param string $localization_ids - comma delimited list of localization IDs
 * @param string $lang_code - language code
 * @return string  - comma delimited list of localization names
 */
function fn_exim_get_localizations($localization_ids, $lang_code = '')
{
    $locs = db_get_fields("SELECT localization FROM ?:localization_descriptions WHERE FIND_IN_SET(localization_id, ?s) AND lang_code = ?s", $localization_ids, $lang_code);

    return implode(', ', $locs);
}

/**
 * Assign localizations to the product
 *
 * @param int $product_id Product ID
 * @param string $data - comma delimited list of localizations
 * @return boolean always true
 */
function fn_exim_set_localizations($product_id, $data)
{
    if (empty($data)) {
        db_query("UPDATE ?:products SET localization = ''");

        return true;
    }

    $multi_lang = array_keys($data);
    $main_lang = reset($multi_lang);

    $loc_ids = db_get_fields("SELECT localization_id FROM ?:localization_descriptions WHERE localization IN (?a) AND lang_code = ?s", fn_explode(',', $data[$main_lang]), $main_lang);

    $_data = array(
        'localization' => fn_create_set($loc_ids)
    );

    db_query('UPDATE ?:products SET ?u WHERE product_id = ?i', $_data, $product_id);

    return true;
}
}

function fn_exim_get_items_in_box($product_id)
{
    $shipping_params = db_get_field('SELECT shipping_params FROM ?:products WHERE product_id = ?i', $product_id);
    if (!empty($shipping_params)) {
        $shipping_params = unserialize($shipping_params);

        return 'min:' . (empty($shipping_params['min_items_in_box']) ? 0 : $shipping_params['min_items_in_box']) . ';max:' . (empty($shipping_params['max_items_in_box']) ? 0 : $shipping_params['max_items_in_box']);
    }

    return 'min:0;max:0';
}

function fn_exim_put_items_in_box($product_id, $data)
{
    if (empty($data)) {
        return false;
    }

    $min = $max = 0;
    $params = explode(';', $data);
    foreach ($params as $param) {
        $elm = explode(':', $param);
        if ($elm[0] == 'min') {
            $min = intval($elm[1]);
        } elseif ($elm[0] == 'max') {
            $max = intval($elm[1]);
        }
    }

    $shipping_params = db_get_field('SELECT shipping_params FROM ?:products WHERE product_id = ?i', $product_id);
    if (!empty($shipping_params)) {
        $shipping_params = unserialize($shipping_params);
    }

    $shipping_params['min_items_in_box'] = $min;
    $shipping_params['max_items_in_box'] = $max;

    db_query('UPDATE ?:products SET shipping_params = ?s WHERE product_id = ?i', serialize($shipping_params), $product_id);

    return true;
}

function fn_exim_get_box_size($product_id)
{
    $shipping_params = db_get_field('SELECT shipping_params FROM ?:products WHERE product_id = ?i', $product_id);

    if (!empty($shipping_params)) {
        $shipping_params = unserialize($shipping_params);

        return 'length:' . (empty($shipping_params['box_length']) ? 0 : $shipping_params['box_length']) . ';width:' . (empty($shipping_params['box_width']) ? 0 : $shipping_params['box_width']) . ';height:' . (empty($shipping_params['box_height']) ? 0 : $shipping_params['box_height']);
    }

    return 'length:0;width:0;height:0';
}

function fn_exim_put_box_size($product_id, $data)
{
    if (empty($data)) {
        return false;
    }

    $length = $width = $height = 0;
    $params = explode(';', $data);
    foreach ($params as $param) {
        $elm = explode(':', $param);
        if ($elm[0] == 'length') {
            $length = intval($elm[1]);
        } elseif ($elm[0] == 'width') {
            $width = intval($elm[1]);
        } elseif ($elm[0] == 'height') {
            $height = intval($elm[1]);
        }
    }

    $shipping_params = db_get_field('SELECT shipping_params FROM ?:products WHERE product_id = ?i', $product_id);
    if (!empty($shipping_params)) {
        $shipping_params = unserialize($shipping_params);
    }

    $shipping_params['box_length'] = $length;
    $shipping_params['box_width'] = $width;
    $shipping_params['box_height'] = $height;

    db_query('UPDATE ?:products SET shipping_params = ?s WHERE product_id = ?i', serialize($shipping_params), $product_id);

    return true;
}

function fn_exim_send_product_notifications($primary_object_ids, $import_data)
{
    if (empty($primary_object_ids) || !is_array($primary_object_ids)) {
        return true;
    }

    $auth = & Tygh::$app['session']['auth'];
    //Send notification for all updated products. Notification will be sent only if product have subscribers.
    foreach ($primary_object_ids as $k => $v) {
        if (!empty($v['product_id'])) {
            $product_amount = db_get_field('SELECT amount FROM ?:products WHERE product_id = ?i', $v['product_id']);
            if ($product_amount > 0) {
                fn_send_product_notifications($v['product_id']);
            }
        }
    }

    return true;
}

function fn_import_unset_product_id(&$object)
{
    unset($object['product_id']);
}

function fn_check_product_code($data)
{
    if (!empty($data)) {
        $cutted_product_codes = "";

        foreach ($data as $key => $product_data) {
            if (!empty($product_data['product_code'])) {
                if (strlen($product_data['product_code']) > 32) {
                    $cutted_product_codes .= substr($product_data['product_code'], 0, 32) . "... ";
                }
            }
        }

        if (!empty($cutted_product_codes)) {
            $msg = __('cutted_product_codes') . '<br>' . $cutted_product_codes . '<br>';
            fn_set_notification('W', __('warning'), $msg);
        }
    }

    return true;
}

/**
 * Updates product price for a storefront. Used on product import.
 *
 * @param integer $product_id Product ID
 * @param float $price Price
 * @param boolean $is_create True if the product has been created
 * @param string $store Comany name
 * @return boolean
 */
function fn_import_product_price($product_id, $price, $is_create, $store ='')
{
    if (fn_allowed_for('ULTIMATE')) {
        if (fn_ult_is_shared_product($product_id) == 'Y') {
            if (!($company_id = Registry::get('runtime.company_id'))) {
                $company_id = fn_get_company_id_by_name($store);
            }
            fn_update_product_prices($product_id, array('price' => $price, 'create' => $is_create), $company_id);
        }
    }
    fn_update_product_prices($product_id, array('price' => $price, 'create' => $is_create));
}

function fn_import_fill_products_alt_keys($pattern, &$alt_keys, &$object, &$skip_get_primary_object_id)
{
    if (Registry::get('runtime.company_id')) {
        $alt_keys['company_id'] = Registry::get('runtime.company_id');

    } elseif (!empty($object['company'])) {
        // field store is set
        $company_id = fn_get_company_id_by_name($object['company']);
        if ($company_id !== null) {
            $alt_keys['company_id'] = $company_id;
        } else {
            $skip_get_primary_object_id = true;
        }
    } else {
        // field store is not set
        $skip_get_primary_object_id = true;
    }
}

/**
 * Process product description. Depend on company id.
 * @param integer $product_id Product id
 * @param string $value Default product description
 * @param string $lang_code Lang code
 * @return string
 */
function fn_export_product_descr($product_id, $value, $lang_code, $field)
{
    if (fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::get('runtime.company_id');
        if ($company_id) {
            $descr = db_get_field("SELECT $field FROM ?:ult_product_descriptions WHERE product_id = ?i AND lang_code = ?s AND company_id = ?i", $product_id, $lang_code, $company_id);
            if (!empty($descr)) {
                return $descr;
            }
        }
    }

    return $value;
}

/**
 * Update product description for necessary store.
 * @param Array $data Product data
 * @param integer $product_id Product id
 * @param string $lang_code Lang code
 */
function fn_import_product_descr($data, $product_id, $field)
{
    $company_id = Registry::get('runtime.company_id');
    $prod_company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $product_id);
    foreach ($data as $lang_code => $_data) {
        db_query(
            "UPDATE ?:product_descriptions SET $field = ?s WHERE product_id = ?i AND lang_code = ?s",
            $_data, $product_id, $lang_code
        );
        if (fn_allowed_for('ULTIMATE')) {
            if (empty($company_id) || (!empty($company_id) && $company_id == $prod_company_id)) {
                $_company_id = $prod_company_id;
            } elseif (!empty($company_id) && $company_id != $prod_company_id) {
                $_company_id = $company_id;
            }
            $ult_descr_prod_id = db_get_field("SELECT product_id FROM ?:ult_product_descriptions WHERE product_id = ?i AND lang_code = ?s AND company_id = ?i", $product_id, $lang_code, $_company_id);
            if (empty($ult_descr_prod_id)) {
                db_query(
                    'REPLACE INTO ?:ult_product_descriptions ('
                    . ' product_id, lang_code, company_id, product, shortname, short_description,'
                    . ' full_description, meta_keywords, meta_description, search_words, page_title,'
                    . ' age_warning_message, promo_text'
                    . ') SELECT'
                    . ' product_id, lang_code, ?i, product, shortname, short_description,'
                    . ' full_description, meta_keywords, meta_description, search_words,'
                    . ' page_title, age_warning_message, promo_text'
                    . ' FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s',
                    $prod_company_id, $product_id, $lang_code
                );
            }
            db_query("UPDATE ?:ult_product_descriptions SET $field = ?s WHERE product_id = ?i AND lang_code = ?s AND company_id = ?i", $_data, $product_id, $lang_code, $_company_id);
        }
    }
}

/**
 * Prepares DB timestamp field to export. Empty timestamp (i.e. 0) will be converted to empty string.
 *
 * @param string $timestamp
 *
 * @return string Exported timestamp value
 */
function fn_exim_get_optional_timestamp($timestamp)
{
    if (empty($timestamp)) {
        return '';
    } else {
        return fn_timestamp_to_date($timestamp);
    }
}

/**
 * Prepares timestamp field to be imported into DB. Empty strings will be converted to 0.
 *
 * @param $timestamp
 *
 * @return int Timestamp value
 */
function fn_exim_put_optional_timestamp($timestamp)
{
    if (empty($timestamp)) {
        return 0;
    } else {
        return fn_date_to_timestamp($timestamp);
    }
}

/**
 * Find product feature group id by name
 *
 * @param string    $name       Feature group name
 * @param string    $lang_code  Language code
 * @param int       $company_id Company identifier
 *
 * @return int
 */
function fn_exim_find_feature_group_id($name, $lang_code, $company_id = null)
{
    return fn_exim_find_feature_id($name, ProductFeatures::GROUP, 0, $lang_code, $company_id);
}

/**
 * Find product feature id by params
 *
 * @param string    $name       Product feature name
 * @param string    $type       Product feature type
 * @param int       $group_id   Product feature group identification
 * @param string    $lang_code  Language code
 * @param int       $company_id Company identifier
 *
 * @return int
 */
function fn_exim_find_feature_id($name, $type, $group_id, $lang_code, $company_id = null)
{
    $feature = fn_exim_find_feature($name, $type, $group_id, $lang_code, $company_id);

    return !empty($feature) ? $feature['feature_id'] : 0;
}

/**
 * Gets product features from import data
 *
 * @param array     $data               List of imported feature
 * @param string    $features_delimiter Delimiter
 *
 * @return array
 */
function fn_exim_parse_features($data, $features_delimiter)
{
    reset($data);
    $features = array();

    foreach ($data as $lang_code => $item) {
        //for compatibility with the old format
        $item = preg_replace('{\{\d*\}}', '', $item);

        if (!empty($item)) {
            $items = fn_exim_parse_data($item, $features_delimiter);

            foreach ($items as $key => $feature) {
                if (empty($feature['variants'])) {
                    continue;
                }

                if (!isset($features[$key])) {
                    $features[$key] = $feature;
                    $features[$key]['names'] = array();
                    $features[$key]['group_names'] = array();
                    $features[$key]['group_name'] = isset($feature['group_name']) ? $feature['group_name'] : null;
                } elseif ($features[$key]['type'] != $feature['type']) {
                    continue;
                }

                $features[$key]['names'][$lang_code] = $feature['name'];
                $features[$key]['group_names'][$lang_code] = isset($feature['group_name']) ? $feature['group_name'] : null;

                foreach ($feature['variants'] as $variant_key => $variant) {
                    $features[$key]['variants'][$variant_key]['names'][$lang_code] = $variant['name'];
                }
            }
        }
    }

    return $features;
}

/**
 * Save product features values
 *
 * @param int       $product_id Product identifier
 * @param array     $features   List of feature data
 * @param string    $lang_code  Language code
 */
function fn_exim_save_product_features_values($product_id, array $features, $lang_code)
{
    $feature_ids = $existent_feature_variants = $variant_names = array();
    $product_features = $product_lang_features = array();

    $current_product_feature_ids = db_get_fields("SELECT feature_id FROM ?:product_features_values WHERE product_id = ?i GROUP BY feature_id", $product_id);
    $default_features_values = array_fill_keys($current_product_feature_ids, '');
    $runtime_company_id = fn_get_runtime_company_id();

    foreach ($features as $key => &$feature) {
        if (empty($feature['feature_id']) || empty($feature['variants']) || empty($feature['type'])) {
            unset($features[$key]);
            continue;
        }

        if ($feature['type'] != ProductFeatures::MULTIPLE_CHECKBOX) {
            $feature['variants'] = array_slice($feature['variants'], 0, 1);
        }

        $feature['is_selectable'] = strpos(ProductFeatures::getSelectable(), $feature['type']) !== false;

        if ($feature['is_selectable']) {
            foreach ($feature['variants'] as $variant) {
                $variant_names[] = $variant['name'];
            }
        }

        $feature_ids[] = $feature['feature_id'];
    }
    unset($feature);

    if (!empty($variant_names)) {
        $existent_feature_variants = db_get_hash_multi_array(
            'SELECT pfvd.variant_id, variant, feature_id FROM ?:product_feature_variant_descriptions AS pfvd ' .
            'LEFT JOIN ?:product_feature_variants AS pfv ON pfv.variant_id = pfvd.variant_id ' .
            'WHERE feature_id IN (?n) AND variant IN (?a) AND lang_code = ?s',
            array('feature_id', 'variant'), $feature_ids, $variant_names, $lang_code
        );
    }

    foreach ($features as $feature) {
        $feature_id = $feature['feature_id'];
        $variants = $feature['variants'];
        $feature_type = $feature['type'];
        $company_id = $feature['company_id'];

        if ($feature['is_selectable']) {
            foreach ($variants as $variant) {
                $variant_name = $variant['name'];
                $can_update = empty($runtime_company_id) || $runtime_company_id == $company_id;

                if (!isset($existent_feature_variants[$feature_id][$variant_name])) {
                    if (!$can_update) {
                        if (fn_allowed_for('MULTIVENDOR')) {
                            fn_set_notification('W', __('warning'), __('exim_vendor_cant_create_feature'));
                        } else {
                            fn_set_notification('W', __('warning'), __('exim_cannot_create_variant_for_shareable_feature'));
                        }

                        continue;
                    }

                    $variant_id = fn_add_feature_variant($feature_id, array('variant' => $variant_name));
                } else {
                    $variant_id = $existent_feature_variants[$feature_id][$variant_name]['variant_id'];
                }

                if ($variant_id) {
                    if ($can_update) {
                        foreach ($variant['names'] as $variant_lang_code => $variant_name) {
                            if ($variant_lang_code == $lang_code) {
                                continue;
                            }

                            db_query(
                                "UPDATE ?:product_feature_variant_descriptions SET ?u WHERE variant_id = ?i AND lang_code = ?s",
                                array('variant' => $variant_name),
                                $variant_id,
                                $variant_lang_code
                            );
                        }
                    }

                    if ($feature['type'] == ProductFeatures::MULTIPLE_CHECKBOX) {
                        $product_features[$feature_id][$variant_id] = $variant_id;
                    } else {
                        $product_features[$feature_id] = $variant_id;
                    }
                }
            }
        } else {
            $variant = reset($variants);
            $product_features[$feature_id] = $variant['name'];

            if ($feature_type == ProductFeatures::TEXT_FIELD) {
                foreach ($variant['names'] as $variant_lang_code => $name) {
                    if ($variant_lang_code != $lang_code) {
                        $product_lang_features[$variant_lang_code][$feature_id] = $name;
                    }
                }
            }
        }
    }

    $product_features = $product_features + $default_features_values;

    if (!empty($product_features)) {
        fn_update_product_features_value($product_id, $product_features, array(), $lang_code);
    }

    if (!empty($product_lang_features)) {
        foreach ($product_lang_features as $feature_lang_code => $features) {
            fn_update_product_features_value($product_id, $features, array(), $feature_lang_code);
        }
    }
}

/**
 * Find product feature by params
 *
 * @param string    $name       Product feature name
 * @param string    $type       Product feature type
 * @param int       $group_id   Product feature group identification
 * @param string    $lang_code  Language code
 * @param int|null  $company_id Company identifier
 *
 * @return array
 */
function fn_exim_find_feature($name, $type, $group_id, $lang_code, $company_id = null)
{
    $current_company_id = Registry::get('runtime.company_id');
    $is_simple_ultimate = Registry::get('runtime.simple_ultimate');

    if (!$is_simple_ultimate && $company_id !== null) {
        Registry::set('runtime.company_id', $company_id);
    }

    $condition = db_quote("WHERE description = ?s AND lang_code = ?s AND feature_type = ?s", $name, $lang_code, $type);
    $condition .= db_quote(" AND parent_id = ?i", $group_id);

    if (fn_allowed_for('ULTIMATE')) {
        $condition .= fn_get_company_condition('?:product_features.company_id');
    }

    $result = db_get_row(
        'SELECT pf.feature_id, pf.feature_code, pf.feature_type, pf.categories_path, pf.parent_id, pf.status, pf.company_id' .
        ' FROM ?:product_features as pf ' .
        ' LEFT JOIN ?:product_features_descriptions ON pf.feature_id = ?:product_features_descriptions.feature_id ' . $condition
    );

    if (!$is_simple_ultimate && $company_id !== null) {
        Registry::set('runtime.company_id', $current_company_id);
    }

    return $result;
}

/**
 * Save product feature
 *
 * @param array     $feature    Product feature data
 * @param int       $company_id Company identifier
 * @param string    $lang_code  Language code
 *
 * @return array|bool
 */
function fn_exim_save_product_feature(array $feature, $company_id, $lang_code)
{
    $allowed_feature_types = ProductFeatures::getAllTypes();
    $runtime_company_id = fn_get_runtime_company_id();

    if (strpos($allowed_feature_types, $feature['type']) === false && $feature['type'] != ProductFeatures::GROUP) {
        fn_set_notification('W', __('warning'), __('exim_error_incorrect_feature_type'));
        return false;
    }

    if (empty($feature['name'])) {
        fn_set_notification('W', __('warning'), __('exim_error_empty_feature_name'));
        return false;
    }

    $data = fn_exim_find_feature($feature['name'], $feature['type'], $feature['parent_id'], $lang_code, $company_id);

    if (fn_allowed_for('ULTIMATE') && empty($data) && !empty($company_id) && $runtime_company_id == 0) {
        $data = fn_exim_find_feature($feature['name'], $feature['type'], $feature['parent_id'], $lang_code, 0);

        if (!empty($data)) {
            fn_exim_update_share_feature($data['feature_id'], $company_id);
        }
    }

    if (empty($data)) {
        if (fn_allowed_for('MULTIVENDOR') && $runtime_company_id) {
            fn_set_notification('W', __('warning'), __('exim_vendor_cant_create_feature'));
            return false;
        }

        $data = array(
            'feature_id' => 0,
            'description' => $feature['name'],
            'feature_type' => $feature['type'],
            'lang_code' => $lang_code,
            'company_id' => $company_id,
            'status' => 'A',
            'parent_id' => $feature['parent_id'],
            'categories_path' => '',
        );

        $feature_id = fn_update_product_feature($data, 0, $lang_code);

        if (fn_allowed_for('ULTIMATE')) {
            fn_exim_update_share_feature($feature_id, $company_id);
        }
    } else {
        $feature_id = $data['feature_id'];
    }

    $feature['feature_id'] = $feature_id;
    $feature['company_id'] = $data['company_id'];

    if ($runtime_company_id == 0 || $feature['company_id'] == $company_id) {
        foreach ($feature['names'] as $name_lang_code => $name) {
            if ($name_lang_code != $lang_code) {
                db_query(
                    "UPDATE ?:product_features_descriptions SET ?u WHERE feature_id = ?i AND lang_code = ?s",
                    array('description' => $name),
                    $feature_id,
                    $name_lang_code
                );
            }
        }
    }

    return $feature;
}