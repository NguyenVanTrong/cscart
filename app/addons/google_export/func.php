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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_google_export_add_features()
{
    $lang = DEFAULT_LANGUAGE;
    $new_features = fn_google_export_get_new_features_list();

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = fn_get_default_company_id();
        $parent_feature_id = db_query(
            "INSERT INTO ?:product_features"
            . " (feature_type, categories_path, parent_id, display_on_product, display_on_catalog, status, position, comparison, company_id)"
            . " VALUES"
            . " (?s, '', 0, 0, 0, 'A', 0, 'N', " . $company_id . ")"
        , ProductFeatures::GROUP);
        fn_share_object_to_all('product_features', $parent_feature_id);
    } else {
        $parent_feature_id = db_query(
            "INSERT INTO ?:product_features"
            . " (feature_type, categories_path, parent_id, display_on_product, display_on_catalog, status, position, comparison)"
            . " VALUES"
            . " (?s, '', 0, 0, 0, 'A', 0, 'N')"
        , ProductFeatures::GROUP);
    }
    db_query(
        "INSERT INTO ?:product_features_descriptions"
        . " (feature_id, description, full_description, prefix, suffix, lang_code)"
        . " VALUES"
        . " (?i, 'Google export features', '', '', '', ?s)",
        $parent_feature_id, $lang
    );

    fn_google_export_add_feature($new_features, $parent_feature_id);

    fn_google_export_update_alt_languages('product_features_descriptions', 'feature_id');
    fn_google_export_update_alt_languages('product_feature_variant_descriptions', 'variant_id');
}

function fn_google_export_add_feature($new_features, $parent_feature_id, $show_process = false, $lang = DEFAULT_LANGUAGE)
{
    static $company_id = 0;

    if (!$company_id) {
        $company_id = fn_get_default_company_id();
    }

    foreach ($new_features as $feature_name => $feature_data) {
        foreach ($feature_data as $feature_type => $feature_variants) {
            if (fn_allowed_for('ULTIMATE')) {
                $f_id = db_query(
                    "INSERT INTO ?:product_features"
                    . " (feature_type, categories_path, parent_id, display_on_product, display_on_catalog, status, position, comparison, company_id)"
                    . " VALUES"
                    . " (?s, '', ?i, 0, 0, 'A', 0, 'N', ?i)",
                    $feature_type, $parent_feature_id, $company_id
                );
                fn_share_object_to_all('product_features', $f_id);
            } else {
                $f_id = db_query(
                    "INSERT INTO ?:product_features"
                    . " (feature_type, categories_path, parent_id, display_on_product, display_on_catalog, status, position, comparison)"
                    . " VALUES"
                    . " (?s, '', ?i, 0, 0, 'A', 0, 'N')",
                    $feature_type, $parent_feature_id
                );
            }
            db_query(
                "INSERT INTO ?:product_features_descriptions"
                . " (feature_id, description, full_description, prefix, suffix, lang_code)"
                . " VALUES"
                . " (?i, ?s, '', '', '', ?s)",
                $f_id, $feature_name, $lang
            );
            if ($show_process) {
                fn_echo(' .');
            }
            fn_google_export_add_feature_variants($f_id, $feature_variants, $show_process);
        }
    }
}

function fn_google_export_add_feature_variants($feature_id, $feature_variants, $show_process = false, $lang_code = DEFAULT_LANGUAGE)
{
    if (empty($feature_variants)) {
        return;
    }

    foreach ($feature_variants as $key => $val) {
        if ($show_process && ($key % 100 == 0)) {
            fn_echo(' .');
        }
        $variant_id = db_query("INSERT INTO ?:product_feature_variants (feature_id, position) VALUES (?i, 0)", $feature_id);
        db_query("INSERT INTO ?:product_feature_variant_descriptions (variant_id, variant, lang_code) VALUES (?i, ?s, ?s);", $variant_id, $val, $lang_code);
    }
}

function fn_get_google_categories($lang_code = DEFAULT_LANGUAGE)
{
    $urls = fn_google_export_available_categories();

    if (empty($urls[$lang_code])) {
        return false;
    }
    $url = $urls[$lang_code];

    $content = fn_get_contents($url);
    if ($content) {
        $result = explode("\n", $content);
        $result = array_diff($result, array(''));

        return array_slice($result, 1);
    }

    return false;
}

function fn_google_export_remove_features()
{
    $features = fn_google_export_get_new_features_list();
    $parent_feature_id = db_get_field(
        "SELECT ?:product_features_descriptions.feature_id"
        . " FROM ?:product_features_descriptions"
        . " LEFT JOIN ?:product_features"
            . " ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id"
        . " WHERE ?:product_features_descriptions.description = 'Google export features'"
            . " AND ?:product_features_descriptions.lang_code = ?s"
            . " AND ?:product_features.feature_type = ?s"
            . " AND ?:product_features.parent_id = 0",
        DEFAULT_LANGUAGE,
        ProductFeatures::GROUP
    );

    foreach ($features as $feature_name => $feature_data) {
        $f_id = db_get_field(
            "SELECT ?:product_features_descriptions.feature_id"
            . " FROM ?:product_features_descriptions"
            . " LEFT JOIN ?:product_features"
                . " ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id"
            . " WHERE ?:product_features_descriptions.description = ?s"
                . " AND ?:product_features_descriptions.lang_code = ?s"
                . " AND ?:product_features.parent_id = ?i",
            $feature_name,
            DEFAULT_LANGUAGE,
            $parent_feature_id
        );

        if (!empty($f_id)) {
            fn_delete_feature($f_id);
        }
    }
    fn_delete_feature($parent_feature_id);
    fn_google_export_remove_additional_google_categories();
}

function fn_google_export_get_new_features_list()
{
    return array (
        'GTIN' => array (
            'T' => array()
        ),
        'MPN' => array (
            'T' => array()
        ),
        'Brand' => array (
            'T' => array()
        ),
        'Availability' => array (
            'S' => array (
                'in stock',
                'available for order',
                'out of stock',
                'preorder'
            )
        ),
        'Condition' => array (
            'S' => array (
                'new',
                'used',
                'refurbished'
            )
        ),
        'Google product category (US)' => array (
            'S' => fn_get_google_categories()
        ),
        'Age group' => array (
            'S' => array (
                'newborn',
                'infant',
                'toddler',
                'kids',
                'adult'
            )
        ),
        'Gender' => array (
            'S' => array (
                'male',
                'female',
                'unisex'
            )
        ),
        'Size type' => array (
            'S' => array (
                'regular',
                'petite',
                'plus',
                'big and tall',
                'maternity'
            )
        ),
        'Size system' => array (
            'S' => array (
                'US',
                'UK',
                'EU',
                'DE',
                'FR',
                'JP',
                'CN (China)',
                'IT',
                'BR',
                'MEX',
                'AU'
            )
        )
    );
}

function fn_get_google_options()
{
    $select_options = array();

    list($all_options, $params) = fn_get_product_global_options();
    if (!empty($all_options)) {
        foreach ($all_options as $_option) {
            $select_options[$_option['option_id']] = $_option['option_name'];
        }
    }

    return $select_options;
}

function fn_google_export_get_new_options_list()
{
    $options_fields = array();
    $data_fields = fn_get_schema('exim_data_feeds', 'google_export_options');

    foreach ($data_fields as $name_field => $field) {
        $options_fields[] = $name_field;
    }

    return $options_fields;
}

function fn_google_export_add_feed()
{
    $layout_data = db_get_row(
        "SELECT cols, options, active"
        . " FROM ?:exim_layouts"
        . " WHERE name = 'general_data_feeds' AND pattern_id = 'data_feeds'"
    );
    $layout_data['name'] = 'google_export';
    $layout_data['pattern_id'] = 'data_feeds';

    $layout_id = db_get_field(
        "SELECT layout_id"
        . " FROM ?:exim_layouts"
        . " WHERE name = 'general_data_feeds' AND pattern_id = 'data_feeds'"
    );

    if (!empty($layout_data)) {
        $layout_id = db_query("INSERT INTO ?:exim_layouts ?e", $layout_data);
    }

    $fields = array (
        array (
            'position' => 0,
            'export_field_name' => 'id',
            'field' => 'Product id',
            'avail' => 'Y'
        ),
        array (
            'position' => 0,
            'export_field_name' => 'title',
            'field' => 'Product name',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'link',
            'field' => 'Product URL',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'description',
            'field' => 'Google description',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'condition',
            'field' => 'Condition',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'price',
            'field' => 'Google price',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'availability',
            'field' => 'Availability',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'image_link',
            'field' => 'Image URL',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'gtin',
            'field' => 'GTIN',
            'avail' => 'Y',
            'db_field' => 'product_code'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'brand',
            'field' => 'Brand',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'mpn',
            'field' => 'MPN',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'google_product_category',
            'field' => 'Google product category (US)',
            'avail' => 'Y'
        ),
        Array (
            'position' => 0,
            'export_field_name' => 'product_type',
            'field' => 'Category',
            'avail' => 'Y'
        )
    );

    $features_fields = fn_google_export_get_new_features_list();
    $parent_feature_id = db_get_field(
        "SELECT ?:product_features_descriptions.feature_id"
        . " FROM ?:product_features_descriptions"
        . " LEFT JOIN ?:product_features"
            . " ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id"
        . " WHERE ?:product_features_descriptions.description = 'Google export features'"
            . " AND ?:product_features_descriptions.lang_code = ?s"
            . " AND ?:product_features.feature_type = ?s"
            . " AND ?:product_features.parent_id = 0",
        DEFAULT_LANGUAGE,
        ProductFeatures::GROUP
    );

    if (!empty($parent_feature_id)) {
        foreach ($fields as $k_field => $field) {
            $feature_id = db_get_field(
                "SELECT ?:product_features_descriptions.feature_id"
                . " FROM ?:product_features_descriptions"
                . " LEFT JOIN ?:product_features"
                    . " ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id"
                . " WHERE ?:product_features_descriptions.description = ?s"
                    . " AND ?:product_features_descriptions.lang_code = ?s"
                    . " AND ?:product_features.parent_id = ?i",
                $field['field'],
                DEFAULT_LANGUAGE,
                $parent_feature_id
            );

            if (!empty($feature_id) && !empty($features_fields[$field['field']])) {
                $fields[$k_field]['field'] = $feature_id;
            }
        }
    }

    $export_options = array (
        'lang_code' => array(DEFAULT_LANGUAGE),
        'category_delimiter' => ' > ',
        'features_delimiter' => '///',
        'price_dec_sign_delimiter' => '.'
    );

    $data = array (
        'categories' => '',
        'products' => '',
        'fields' => serialize($fields),
        'export_location' => '',
        'export_by_cron' => 'N',
        'ftp_url' => '',
        'ftp_user' => '',
        'ftp_pass' => '',
        'file_name' => 'google_base.csv',
        'enclosure' => '',
        'csv_delimiter' => 'T',
        'exclude_disabled_products' => 'N',
        'export_options' => serialize($export_options),
        'save_dir' => '',
        'status' => 'A',
        'layout_id' => $layout_id
    );
    $data_feed_id = db_query("INSERT INTO ?:data_feeds ?e", $data);

    foreach (fn_get_translation_languages() as $language) {
        db_query(
            "INSERT INTO ?:data_feed_descriptions (datafeed_id, datafeed_name, lang_code) VALUES (?i, 'Google base', ?s);",
            $data_feed_id, $language['lang_code']
        );
    }
}

function fn_google_export_remove_feed()
{
    $data_feed_id = db_get_field(
        "SELECT datafeed_id"
        . " FROM ?:data_feed_descriptions"
        . " WHERE datafeed_name = 'Google base' AND lang_code = ?s",
        DESCR_SL
    );

    if (!empty($data_feed_id)) {
        db_query('DELETE FROM ?:data_feeds WHERE datafeed_id = ?i', $data_feed_id);
        db_query('DELETE FROM ?:data_feed_descriptions WHERE datafeed_id = ?i', $data_feed_id);
    }

    $layout_data = db_get_row("SELECT layout_id FROM ?:exim_layouts WHERE pattern_id = 'data_feeds' AND name = 'google_export'");

    if (!empty($layout_data)) {
        db_query("DELETE FROM ?:exim_layouts WHERE layout_id = ?i", $layout_data['layout_id']);
    }
}

function fn_google_export_update_alt_languages($table, $keys, $show_process = false)
{
    $langs = fn_get_translation_languages();

    if (empty($langs)) {
        $langs = db_get_fields("SELECT lang_code FROM ?:languages");
    } else {
        $langs = array_keys($langs);
    }

    if (!is_array($keys)) {
        $keys = array($keys);
    }

    $i = 0;
    $step = 50;
    while ($items = db_get_array("SELECT * FROM ?:$table WHERE lang_code = ?s LIMIT $i, $step", DEFAULT_LANGUAGE)) {
        if ($show_process) {
            fn_echo(' .');
        }
        $i += $step;
        foreach ($items as $v) {
            foreach ($langs as $lang) {
                $condition = array();
                foreach ($keys as $key) {
                    $condition[] = "$key = '" . $v[$key] . "'";
                }
                $condition = implode(' AND ', $condition);
                $exists = db_get_field("SELECT COUNT(*) FROM ?:$table WHERE $condition AND lang_code = ?s", $lang);
                if (empty($exists)) {
                    $v['lang_code'] = $lang;
                    db_query("REPLACE INTO ?:$table ?e", $v);
                }
            }
        }
    }
}

function fn_settings_actions_addons_google_export_additional_langs($new_value, $old_value)
{
    if ($new_value != $old_value) {
        if ($new_value == 'Y') {
            fn_google_export_add_additional_google_categories();
        } else {
            fn_google_export_remove_additional_google_categories();
        }
    }
}

function fn_google_export_add_additional_google_categories()
{
    $available_langs = array_keys(fn_google_export_available_categories());
    fn_echo(__('google_export_start_import'));
    foreach ($available_langs as $lang) {
        $new_feature = array (
            "Google product category ($lang)" => array (
                'S' => fn_get_google_categories($lang)
            )
        );
        $parent_feature_id = db_get_field("SELECT feature_id FROM ?:product_features_descriptions WHERE description = 'Google export features' AND lang_code = ?s", DEFAULT_LANGUAGE);
        fn_google_export_add_feature($new_feature, $parent_feature_id, true);
        fn_google_export_update_alt_languages('product_features_descriptions', 'feature_id', true);
        fn_google_export_update_alt_languages('product_feature_variant_descriptions', 'variant_id', true);
    }
}

function fn_google_export_remove_additional_google_categories()
{
    $available_langs = array_keys(fn_google_export_available_categories());

    foreach ($available_langs as $lang) {
        $feature_id = db_get_field(
            "SELECT feature_id"
            . " FROM ?:product_features_descriptions"
            . " WHERE description = 'Google product category ($lang)'"
            . " AND lang_code = ?s",
            DEFAULT_LANGUAGE
        );

        if (!empty($feature_id)) {
            fn_delete_feature($feature_id);
        }
    }
}

function fn_google_export_generate_info()
{
    return __('google_export_general_info');
}

function fn_google_export_available_categories()
{
    return array(
        'ru' => 'http://www.google.com/basepages/producttype/taxonomy.ru-RU.txt',
        'en' => 'http://www.google.com/basepages/producttype/taxonomy.en-US.txt',
        'FR' => 'http://www.google.com/basepages/producttype/taxonomy.fr-FR.txt',
        'DE' => 'http://www.google.com/basepages/producttype/taxonomy.de-DE.txt',
        'IT' => 'http://www.google.com/basepages/producttype/taxonomy.it-IT.txt',
        'NL' => 'http://www.google.com/basepages/producttype/taxonomy.nl-NL.txt',
        'ES' => 'http://www.google.com/basepages/producttype/taxonomy.es-ES.txt',
        'GB' => 'http://www.google.com/basepages/producttype/taxonomy.en-GB.txt'
    );
}

function fn_export_get_options_product_google_export($data, &$result, &$export_fields, $multi_lang)
{
    $f_option = false;
    $data_products = array();
    $export_options = fn_google_export_get_new_options_list();
    $export_params = array('Age group', 'Gender', 'Size type', 'Size system');

    $feature_fields = array();
    foreach ($export_fields as $export_field) {
        $feature_description = db_get_field(
            'SELECT feature_descr.description'
            . ' FROM ?:product_features_descriptions AS feature_descr'
            . ' WHERE feature_descr.feature_id = ?i AND lang_code = ?s',
            $export_field,
            DESCR_SL
        );

        if (!empty($feature_description)) {
            $feature_fields[$feature_description] = $export_field;
        }
    }

    $_id = 1;
    foreach ($result as $k_result => &$d_product) {
        foreach ($multi_lang as $lang_code) {
            $options = array();

            $product_id = 0;
            if (!empty($d_product[$lang_code]['Product id'])) {
                $product_id = $d_product[$lang_code]['Product id'];

            } elseif (!empty($data[$k_result][$lang_code]['product_id'])) {
                $product_id = $data[$k_result][$lang_code]['product_id'];
            }

            foreach ($export_options as $export_option) {
                if (!empty($d_product[$lang_code][$export_option]) && is_array($d_product[$lang_code][$export_option])) {
                    $options[$export_option] = $d_product[$lang_code][$export_option];
                    $d_product[$lang_code][$export_option] = '';
                }

                if (isset($d_product[$lang_code][$export_option])) {
                    $f_option = true;
                }
            }

            foreach ($export_params as $export_param) {
                if (isset($d_product[$lang_code][$export_param])) {
                    $f_option = true;
                }
            }

            $combinations = array();
            $count_combination = 0;
            $count_products = 0;

            if (!empty($options)) {
                $_options = $options;
                $key_option = key($options);
                $options = array_shift($_options);
                $combination = array();
                foreach ($options as $name_option => $option) {
                    $combination[$key_option] = $option;
                    $combination['combinations'][$key_option] = $name_option;
                    if (!empty($_options)) {
                        fn_google_export_generate_product_options($combinations, $combination, $count_combination, $_options, $export_options);
                    } else {
                        $combinations[$count_combination] = $combination;
                        $count_combination++;
                    }
                }

                foreach ($combinations as $combination) {
                    $d_combination = $d_product[$lang_code];
                    $product_options = array();
                    foreach ($combination['combinations'] as $_combination) {
                        $_options = explode("_", $_combination);
                        $option_id = array_shift($_options);
                        $variant_id = reset($_options);
                        $product_options['product_options'][$option_id] = $variant_id;
                    }

                    $cart_id = fn_generate_cart_id($product_id, $product_options);

                    $data_product = db_get_row(
                        'SELECT combination_hash, product_code'
                        . ' FROM ?:product_options_inventory'
                        . ' WHERE product_id = ?i AND combination_hash = ?s',
                        $product_id,
                        $cart_id
                    );

                    if (!empty($data_product)) {
                        $count_products++;

                        if (!empty($d_product[$lang_code]['Google price'])) {
                            $combination['Google price'] = str_replace(' ' . CART_PRIMARY_CURRENCY, '', $d_product[$lang_code]['Google price']);
                            $price_combination = fn_apply_options_modifiers($product_options['product_options'], $combination['Google price'], 'P');
                            $combination['Google price'] = $price_combination . ' ' . CART_PRIMARY_CURRENCY;
                        }

                        if (!empty($d_product[$lang_code]['Google price (with tax included)'])) {
                            $combination['Google price (with tax included)'] = str_replace(' ' . CART_PRIMARY_CURRENCY, '', $d_product[$lang_code]['Google price (with tax included)']);
                            $price_combination = fn_apply_options_modifiers($product_options['product_options'], $combination['Google price (with tax included)'], 'P');
                            $combination['Google price (with tax included)'] = $price_combination . ' ' . CART_PRIMARY_CURRENCY;
                        }

                        if (!empty($data_product['product_code'])) {
                            if (!empty($d_product[$lang_code]['GTIN'])) {
                                $combination['GTIN'] = $data_product['product_code'];

                            } elseif (!empty($feature_fields['GTIN']) && !empty($d_product[$lang_code][$feature_fields['GTIN']])) {
                                $combination[$feature_fields['GTIN']] = $data_product['product_code'];
                            }
                        }

                        $image_url = fn_exim_get_image_url($cart_id, 'product_option', 'M', true, false, $lang_code);

                        if (!empty($image_url)) {
                            if (!empty($d_product[$lang_code]['Image URL'])) {
                                $combination['Image URL'] = $image_url;
                            }
                        }

                        $d_image_url = fn_exim_get_detailed_image_url($cart_id, 'product_option', 'M', $lang_code);

                        if (!empty($d_image_url)) {
                            if (!empty($d_product[$lang_code]['Detailed image URL'])) {
                                $combination['Detailed image URL'] = $d_image_url;
                            }
                        }

                        $d_combination['item_group_id'] = $product_id;
                        $d_combination['Product id'] = $cart_id;
                        
                        foreach ($combination as $k_combination => $_combination) {
                            if (isset($d_combination[$k_combination])) {
                                $d_combination[$k_combination] = $_combination;
                            }
                        }

                        $data_products[][$lang_code] = $d_combination;

                        $_id++;
                    }
                }

            } else {
                $d_product[$lang_code]['item_group_id'] = $product_id;
                $data_products[][$lang_code] = $d_product[$lang_code];
            }

            if (!empty($options) && (!$count_combination || !$count_products)) {
                $d_product[$lang_code]['item_group_id'] = $d_product[$lang_code]['Product id'];
                $data_products[][$lang_code] = $d_product[$lang_code];
            }
        }
    }

    if (!empty($data_products)) {
        $result = $data_products;
        if (!in_array("item_group_id", $export_fields) && $f_option) {
            $export_fields[] = "item_group_id";
        }
    }
}

function fn_google_export_generate_product_options(&$combinations, &$combination, &$c_id, $_options, $export_options)
{
    foreach ($export_options as $export_option) {
        if (!empty($_options[$export_option])) {
            while ($_options[$export_option]) {
                $combination_id = key($_options[$export_option]);
                $n_option = array_shift($_options[$export_option]);

                $d_options = $_options;
                unset($d_options[$export_option]);

                if (!empty($d_options) && !empty($_options[$export_option])) {
                    $combination[$export_option] = $n_option;
                    $combination['combinations'][$export_option] = $combination_id;
                    fn_google_export_generate_product_options($combinations, $combination, $c_id, $d_options, $export_options);
                } else {
                    $combination[$export_option] = $n_option;
                    $combination['combinations'][$export_option] = $combination_id;
                    $combinations[$c_id] = $combination;
                    $c_id++;
                }
            }
        }
    }
}

function fn_google_get_product_options($product_id, $field, $lang_code, $p_lang_code = "")
{
    if (!empty($p_lang_code)) {
        $lang_code = $p_lang_code;
    }

    $f_options = db_get_row(
        'SELECT opt_des.option_id, opt_des.option_name, opt.google_export_name_option, opt.product_id'
        . ' FROM ?:product_options_descriptions as opt_des'
        . ' LEFT JOIN ?:product_options as opt'
            . ' ON opt_des.option_id = opt.option_id'
        . ' WHERE lang_code = ?s'
            . ' AND google_export_name_option = ?s'
            . ' AND product_id = ?i',
        $lang_code,
        $field,
        $product_id
    );

    if (empty($f_options)) {
        $f_options = db_get_row(
            'SELECT opt_des.option_id, opt_des.option_name, opt.google_export_name_option, opt.product_id'
            . ' FROM ?:product_options_descriptions as opt_des'
            . ' LEFT JOIN ?:product_options as opt'
                . ' ON opt_des.option_id = opt.option_id'
            . ' WHERE lang_code = ?s'
                . ' AND google_export_name_option = ?s'
                . ' AND product_id = 0',
            $lang_code,
            $field
        );

        if (!empty($f_options['option_id'])) {
            $option_id = db_get_field(
                'SELECT option_id'
                . ' FROM ?:product_global_option_links'
                . ' WHERE option_id = ?i AND product_id = ?i',
                $f_options['option_id'],
                $product_id
            );

            if (empty($option_id)) {
                $f_options = array();
            }
        }
    }

    $result = false;
    if (!empty($f_options)) {
        $option_variants = db_get_array(
            'SELECT var_descr.variant_name, option_var.option_id, option_var.variant_id'
            . ' FROM ?:product_option_variants_descriptions AS var_descr'
            . ' RIGHT JOIN ?:product_option_variants AS option_var'
                . ' ON (option_var.variant_id = var_descr.variant_id)'
            . ' WHERE option_var.option_id = ?i'
                . ' AND var_descr.lang_code = ?s'
            . ' GROUP BY var_descr.variant_id',
            $f_options['option_id'],
            $lang_code
        );

        foreach ($option_variants as $variant) {
            $c_id = $variant['option_id'] . '_' . $variant['variant_id'];
            $result[$c_id] = $variant['variant_name'];
        }
    }

    return $result;
}
