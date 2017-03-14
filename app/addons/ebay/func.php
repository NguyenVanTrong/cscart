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
use Tygh\Settings;
use Tygh\Navigation\LastView;
use Ebay\Ebay;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once (Registry::get('config.dir.addons') . 'ebay/config.php');

function fn_get_ebay_shippings($site_id, $service_type, $international = false)
{
    $condition = db_quote('site_id = ?i AND FIND_IN_SET(?s, service_type)', $site_id, $service_type);
    $condition .= db_quote(' AND is_international = ?s', ($international == true) ? 'Y' : 'N');

    $shippings = db_get_hash_multi_array("SELECT * FROM ?:ebay_shippings WHERE $condition ORDER BY name ASC", array('category', 'service_id'));

    return $shippings;
}

function fn_get_ebay_categories($site_id, $parent_id = 0, $get_tree = false)
{
    if ($get_tree) {
        $ebay_categories = db_get_hash_array('SELECT * FROM ?:ebay_categories WHERE site_id = ?i AND FIND_IN_SET(?i, id_path) AND leaf = ?s ORDER BY full_name ASC', 'category_id', $site_id, $parent_id, 'Y');
    } else {
        $ebay_categories = db_get_hash_array('SELECT * FROM ?:ebay_categories WHERE site_id = ?i AND  parent_id = ?i ORDER BY name ASC', 'category_id', $site_id, $parent_id);
    }

    return $ebay_categories;
}

/**
 * Get ebay sites
 * @param array $site_ids
 * @return array
 */
function fn_get_ebay_sites(array $site_ids = null)
{
    $condition = '';

    if ($site_ids !== null) {
        if (empty($site_ids)) {
            return array();
        }

        $condition .= db_quote("AND site_id IN (?n)", $site_ids);
    }

    $sites = db_get_hash_single_array("SELECT * FROM ?:ebay_sites WHERE 1 {$condition}", array('site_id', 'site'));

    return $sites;
}

function fn_ebay_ult_check_store_permission($params, &$object_type, &$object_name, &$table, &$key, &$key_id)
{
    $controller = Registry::get('runtime.controller');
    if ($controller == 'ebay' && !empty($params['template_id'])) {
        $key = 'template_id';
        $key_id = $params[$key];
        $table = 'ebay_templates';
        $object_name = '#' . $key_id;
        $object_type = __('ebay');
    }

    return true;
}

function fn_delete_ebay_template($template_id)
{
    $template_company_id = db_get_field("SELECT company_id FROM ?:ebay_templates WHERE template_id = ?i", $template_id);
    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.simple_ultimate') && Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $template_company_id) {
        fn_set_notification('W', __('warning'), __('ebay_cant_remove_template'));

        return false;
    }
    db_query('UPDATE ?:products SET ebay_template_id = ?i WHERE ebay_template_id = ?i', 0, $template_id);
    db_query('DELETE FROM ?:ebay_templates WHERE template_id = ?i', $template_id);
    db_query('DELETE FROM ?:ebay_template_descriptions WHERE template_id = ?i', $template_id);

    return true;
}

function fn_update_ebay_template($data, $template_id = 0, $lang_code = CART_LANGUAGE)
{
    if (empty($data['name'])) {
        return false;
    }

    unset($data['template_id']);
    if (fn_allowed_for('ULTIMATE')) {
        // check that template owner was not changed by store administrator
        if (Registry::get('runtime.company_id') || empty($data['company_id'])) {
            $template_company_id = db_get_field('SELECT company_id FROM ?:ebay_templates WHERE template_id = ?i', $template_id);
            if (!empty($template_company_id)) {
                $data['company_id'] = $template_company_id;
            } else {
                if (Registry::get('runtime.company_id')) {
                    $template_company_id = $data['company_id'] = Registry::get('runtime.company_id');
                } else {
                    $template_company_id = $data['company_id'] = fn_get_default_company_id();
                }
            }
        } else {
            $template_company_id = $data['company_id'];
        }
    } else {
        if (Registry::get('runtime.company_id')) {
            $template_company_id = Registry::get('runtime.company_id');
        } else {
            $template_company_id = $data['company_id'];
        }
    }

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id') && !empty($template_company_id) && Registry::get('runtime.company_id') != $template_company_id) {
        $create = false;
    } else {
        $isUpdate = false;

        if (isset($data['payment_methods']) && is_array($data['payment_methods'])) {
            $data['payment_methods'] = implode(',', $data['payment_methods']);
        }

        if (isset($data['identifiers']) && is_array($data['identifiers'])) {
            $data['identifiers'] = json_encode($data['identifiers']);
        }

        if (empty($data['root_sec_category'])) {
            $data['sec_category'] = '';
        }
        if (!empty($template_id)) {
            $isUpdate = true;

            db_query('UPDATE ?:ebay_templates SET ?u WHERE template_id = ?i', $data, $template_id);

            db_query('UPDATE ?:ebay_template_descriptions SET ?u WHERE template_id = ?i AND lang_code = ?s', $data, $template_id, $lang_code);
            if (isset($_REQUEST['share_objects']) && isset($_REQUEST['share_objects']['ebay_templates']) && isset($_REQUEST['share_objects']['ebay_templates'][$template_id])) {
                $_products = db_get_fields("SELECT product_id FROM ?:products WHERE company_id NOT IN (?n) AND ebay_template_id = ?i", $_REQUEST['share_objects']['ebay_templates'][$template_id], $template_id);
                if (!empty($_products)) {
                    db_query("UPDATE ?:products SET ebay_template_id = 0 WHERE product_id IN (?n)", $_products);
                }
            }
        } else {
            $data['template_id'] = $template_id = db_query("INSERT INTO ?:ebay_templates ?e", $data);

            if (isset($data['name']) && empty($data['name'])) {
                unset($data['name']);
            }

            if (!empty($data['name'])) {

                foreach (fn_get_translation_languages() as $data['lang_code'] => $_v) {
                    db_query("INSERT INTO ?:ebay_template_descriptions ?e", $data);
                }
            }
        }

        if ($data['use_as_default'] == 'Y') {
            db_query('UPDATE ?:ebay_templates SET use_as_default = ?s WHERE company_id = ?i AND NOT template_id = ?i', 'N', $template_company_id, $template_id);
        }

        if ($template_id && array_key_exists('product_ids', $data)) {
            if ($isUpdate) {
                $current_product_ids = \Ebay\Product::getTemplateProductIds($template_id);
            } else {
                $current_product_ids = array();
            }

            $data['product_ids'] = (array) $data['product_ids'];

            $add_product_ids = array_diff($data['product_ids'], $current_product_ids);
            $delete_product_ids = array_diff($current_product_ids, $data['product_ids']);

            if (!empty($add_product_ids)) {
                foreach ($add_product_ids as $product_id) {
                    \Ebay\Product::updateProductTemplateId($product_id, $template_id);
                }
            }

            if (!empty($delete_product_ids)) {
                foreach ($delete_product_ids as $product_id) {
                    \Ebay\Product::updateProductTemplateId($product_id, 0);
                }
            }
        }
    }

    return $template_id;
}

function fn_get_ebay_template($template_id, $lang_code = CART_LANGUAGE)
{
    $avail_cond = '';

    $template_data = db_get_row('SELECT ?:ebay_templates.*, ?:ebay_template_descriptions.name, ?:ebay_sites.site FROM ?:ebay_templates LEFT JOIN ?:ebay_template_descriptions ON ?:ebay_templates.template_id = ?:ebay_template_descriptions.template_id AND ?:ebay_template_descriptions.lang_code = ?s LEFT JOIN ?:ebay_sites ON ?:ebay_templates.site_id = ?:ebay_sites.site_id WHERE ?:ebay_templates.template_id = ?i ?p', $lang_code, $template_id, $avail_cond);
    if (isset($template_data['payment_methods'])) {
        $template_data['payment_methods'] = explode(',', $template_data['payment_methods']);
    }

    if (!empty($template_data['identifiers'])) {
        $template_data['identifiers'] = @json_decode($template_data['identifiers'], true);

        if (!is_array($template_data['identifiers'])) {
            $template_data['identifiers'] = array();
        }
    }

    return $template_data;
}

function fn_get_ebay_templates($params, $items_per_page = 0, $lang_code = CART_LANGUAGE, $get_simple = false)
{
    // Init filter
    $params = LastView::instance()->update('ebay_templates', $params);

    $fields = array(
        'templates.template_id',
        'templates.status',
        'descr.name',
        'templates.company_id'
    );

    // Define sort fields
    $sortings = array (
        'status' => 'templates.status',
        'name' => 'descr.name',
    );

    $condition = ''; //fn_get_company_condition('templates.company_id')
    $join = db_quote('LEFT JOIN ?:ebay_template_descriptions as descr ON templates.template_id = descr.template_id AND descr.lang_code = ?s', $lang_code);

    if (!empty($params['product_id'])) {
        if (fn_allowed_for('ULTIMATE')) {
            if (Registry::get('runtime.simple_ultimate')) {
                $condition = '';
            } else {
                $company_ids = fn_ult_get_shared_product_companies($params['product_id']);
                $tempalte_ids = db_get_fields("SELECT share_object_id FROM ?:ult_objects_sharing WHERE share_object_type = 'ebay_templates' AND share_company_id IN (?n)", $company_ids);
                $condition = db_quote(' AND templates.template_id IN (?n)', $tempalte_ids);
            }
        } elseif (fn_allowed_for('MULTIVENDOR')) {
            if (Registry::get('runtime.company_id')) {
                $condition = fn_get_company_condition('templates.company_id');
            } else {
                $company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $params['product_id']);
                $condition = db_quote(" AND templates.company_id = ?i", $company_id);
            }
        }
    } else {
        if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.simple_ultimate') && Registry::get('runtime.company_id')) {
            $join .= db_quote(" INNER JOIN ?:ult_objects_sharing ON (?:ult_objects_sharing.share_object_id = templates.template_id AND ?:ult_objects_sharing.share_company_id = ?i AND ?:ult_objects_sharing.share_object_type = 'ebay_templates')", Registry::get('runtime.company_id'));
        }
    }

    $limit = '';
    $group_by = 'templates.template_id';

    // -- SORTINGS --
    if (empty($params['sort_by']) || empty($sortings[$params['sort_by']])) {
        $params['sort_by'] = 'name';
    }

    if (empty($params['sort_order'])) {
        $params['sort_order'] = 'asc';
    }

    $sorting = db_sort($params, $sortings);

    if (!empty($params['limit'])) {
        $limit = db_quote(" LIMIT 0, ?i", $params['limit']);

    } elseif (!empty($params['items_per_page'])) {
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    Registry::set('runtime.skip_sharing_selection', true);
    $templates = db_get_array("SELECT SQL_CALC_FOUND_ROWS " . implode(', ', $fields) . " FROM ?:ebay_templates as templates $join WHERE 1 $condition GROUP BY $group_by $sorting $limit");
    Registry::set('runtime.skip_sharing_selection', false);

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = !empty($total)? $total : db_get_found_rows();
    } else {
        $params['total_items'] = count($templates);
    }

    if ($get_simple == true) {
        $_templates = array();
        foreach ($templates as $template) {
            $_templates[$template['template_id']] = $template['name'];
        }

        return $_templates;
    }

    return array($templates, $params);
}

function fn_add_ebay_logs()
{
    $setting = Settings::instance()->getSettingDataByName('log_type_ebay_requests');

    if (!$setting) {
        $setting = array(
            'name' => 'log_type_ebay_requests',
            'section_id' => 12, // Logging
            'section_tab_id' => 0,
            'type' => 'N',
            'position' => 10,
            'is_global' => 'N',
            'edition_type' => 'ROOT,VENDOR',
            'value' => '#M#all'
        );

        foreach (fn_get_translation_languages() as $lang_code => $_lang) {
            $descriptions[] = array(
                'object_type' => Settings::SETTING_DESCRIPTION,
                'lang_code' => $lang_code,
                'value' => __('ebay_requests')
            );
        }

        $setting_id = Settings::instance()->update($setting, null, $descriptions, true);
        $variant_id = Settings::instance()->updateVariant(array(
            'object_id'  => $setting_id,
            'name'       => 'all',
            'position'   => 5,
        ));
        foreach (fn_get_translation_languages() as $lang_code => $_lang) {
            $description = array(
                'object_id' => $variant_id,
                'object_type' => Settings::VARIANT_DESCRIPTION,
                'lang_code' => $lang_code,
                'value' => __('all')
            );
            Settings::instance()->updateDescription($description);
        }
    }

    return true;
}

function fn_ebay_save_log($type, $action, $data, $user_id, &$content, $event_type)
{
    if ($type == 'ebay_requests') {
        $errors = array();

        if (!empty($data['errors'])) {
            foreach ($data['errors'] as $k => $v) {
                $errors[] = __('error_code') . '(' . $v['ErrorCode'] . '): ' . $v['LongMessage'];
            }
        }

        $content = array (
            'method' => $data['method'] . ' (' . fn_explain_ebay_method($data['method']) . ')',
            'status' => $data['status'],
            'errors' => implode("\n\n", $errors)
        );
    }

    return true;
}

function fn_explain_ebay_method($method)
{
    $msg = array(
        'GetOrders' => __('ebay_method_get_orders'),
        'AddItems' => __('ebay_method_add_items'),
        'GetCategoryFeatures' => __('ebay_method_get_category_features'),
        'GetEbayDetails' => __('ebay_method_get_ebay_details'),
        'GetCategories' => __('ebay_method_get_categories'),
        'GetCategoryVersion' => __('ebay_method_get_category_version')
    );

    return $msg[$method];
}

function fn_ebay_calculate_item_hash($product = array())
{
    $hash = '';
    if (!empty($product['price'])) {
        if ($product['override'] == "Y") {
            $title = substr(strip_tags($product['ebay_title']), 0, 80);
            $description = !empty($product['ebay_description']) ? $product['ebay_description'] : $product['full_description'];
        } else {
            $title = substr(strip_tags($product['product']), 0, 80);
            $description = $product['full_description'];
        }
        $hash_data = array(
            'price' => fn_format_price($product['price']),
            'title' => $title,
            'description' => $description,
        );
        if (!empty($product['product_features'])) {
            $hash_data['product_features'] = serialize($product['product_features']);
        }
        $hash = fn_crc32(implode('_', $hash_data));
    }

    return $hash;
}

function fn_export_ebay_products($template, $product_ids, $auth)
{
    $parts = floor(count($product_ids) / 5) + 1;

    fn_set_progress('parts', $parts);

    $data = array();
    $i = 1;
    $j = 0;
    $success = true;

    foreach ($product_ids as $product_id) {
        fn_echo(' .');

        $data[$product_id] = fn_get_product_data($product_id, $auth, CART_LANGUAGE);
        fn_gather_additional_product_data($data[$product_id], true, true);
        $data[$product_id]['ebay_item_id'] = db_get_field('SELECT ebay_item_id FROM ?:ebay_template_products WHERE product_id = ?i AND template_id = ?i', $product_id, $template['template_id']);

        if ($data[$product_id]['ebay_item_id']) {
            fn_set_progress('echo', '<br />' . __('exporting_images_to_ebay'));
            $images_data = Ebay::instance()->UploadImages(array($data[$product_id]));
            list($transaction_id, $result, $error_code) = Ebay::instance()->ReviseItem($data[$product_id], $template, $images_data);
            if (!empty($result)) {
                if (!$error_code) {
                    $_data = array(
                        'ebay_item_id' => $data[$product_id]['ebay_item_id'],
                        'template_id' => $template['template_id'],
                        'product_id' => $product_id,
                        'product_hash' => fn_ebay_calculate_item_hash($data[$product_id])
                    );
                    db_query('REPLACE INTO ?:ebay_template_products ?e', $_data);
                } elseif ($error_code == 291) {
                    //listing time is over, we should relist item.
                    list($transaction_id, $result, $error_code) = Ebay::instance()->RelistItem($data[$product_id], $template, $images_data);
                    if (!$error_code) {
                        //Since the RelistItem return new ItemId we should remove old data.
                        db_query("DELETE FROM ?:ebay_template_products WHERE ebay_item_id = ?i", $data[$product_id]['ebay_item_id']);
                        $_data = array(
                            'ebay_item_id' => (int) $result->ItemID,
                            'template_id' => $template['template_id'],
                            'product_id' => $product_id,
                            'product_hash' => fn_ebay_calculate_item_hash($data[$product_id])
                        );
                        db_query('REPLACE INTO ?:ebay_template_products ?e', $_data);
                    } else {
                        $success = false;
                    }
                } else {
                    $success = false;
                }
            }

            unset($data[$product_id]);
            continue;
        }
    }

    if (!empty($data)) {
        fn_set_progress('echo', '<br />' . __('exporting_images_to_ebay'));
        $images_data = Ebay::instance()->UploadImages($data);
        fn_set_progress('echo', '<br />' . __('exporting_products_to_ebay'));
        $_data = array_chunk($data, 5);
        foreach ($_data as $products_data) {
            list($transaction_id, $result) = Ebay::instance()->AddItems($products_data, $template, $images_data);
            if (!empty($result)) {
                foreach ($result as $item_key => $item) {
                    $_data = array(
                        'ebay_item_id' => $item['ItemID'],
                        'template_id' => $template['template_id'],
                        'product_id' => $products_data[$item_key]['product_id'],
                        'product_hash' => $item['product_hash']
                    );
                    db_query('REPLACE INTO ?:ebay_template_products ?e', $_data);
                }
            } else {
                $success = false;
            }
        }
    }
    if ($success) {
        fn_set_notification('N', __('successful'), __('ebay_success_products_notice'));
    }

    return $success;
}

function fn_ebay_get_product_fields(&$fields)
{
    $fields[] = array(
        'name' => '[data][ebay_template_id]',
        'text' => __('ebay_template')
    );

    $fields[] = array(
        'name' => '[data][ebay_override_price]',
        'text' => __('override_price')
    );

    $fields[] = array(
        'name' => '[data][ebay_price]',
        'text' => __('ebay_price')
    );

    $fields[] = array(
        'name' => '[data][override]',
        'text' => __('ebay_override')
    );

    $fields[] = array(
        'name' => '[data][ebay_title]',
        'text' => __('ebay_title')
    );

    $fields[] = array(
        'name' => '[data][ebay_description]',
        'text' => __('ebay_description')
    );

    $fields[] = array(
        'name' => '[data][package_type]',
        'text' => __('ebay_package_type')
    );
}

function fn_prepare_xml_product_features($features)
{
    $product_features = '';
    if (!empty($features) && is_array($features)) {
        foreach ($features as $k => $feature) {
            $value = '';
            if ($feature['feature_type'] == ProductFeatures::GROUP && !empty($feature['subfeatures'])) {
                $product_features .= fn_prepare_xml_product_features($feature['subfeatures']);
                continue;
            } else {
                if ($feature['feature_type'] == ProductFeatures::SINGLE_CHECKBOX && $feature['value'] == 'Y') {
                    $value = __('yes');
                } elseif ($feature['feature_type'] == ProductFeatures::DATE) {
                    $value = strftime(Settings::instance()->getValue('date_format', 'Appearance'), $feature['value_int']);
                } elseif ($feature['feature_type'] == ProductFeatures::MULTIPLE_CHECKBOX && $feature['variants']) {
                    foreach ($feature['variants'] as $var) {
                        if ($var['selected']) {
                            $variants[] = $var['variant'];
                        }
                    }
                    $value = implode("</Value>\n<Value>", $variants);
                } elseif ($feature['feature_type'] == ProductFeatures::TEXT_SELECTBOX || $feature['feature_type'] == ProductFeatures::EXTENDED) {
                    foreach ($feature['variants'] as $var) {
                        if ($var['selected']) {
                            $value = $var['variant'];
                        }
                    }
                } elseif ($feature['feature_type'] == ProductFeatures::NUMBER_SELECTBOX || $feature['feature_type'] == ProductFeatures::NUMBER_FIELD) {
                    $value = floatval($feature['value_int']);
                } else {
                    $from = array("&", "'", "<", ">", "\"");
                    $to = array("&amp;", "&apos;", "&lt;", "&gt;", "&quot;");
                    $value = str_replace($from, $to, $feature['value']);
                }

                $product_features .= <<<EOT

                    <NameValueList>
                        <Name>$feature[description]</Name>
                        <Value>$value</Value>
                    </NameValueList>

EOT;
            }
        }
    }

    return $product_features;
}

function fn_get_ebay_orders($cart, $customer_auth)
{
    $success_orders = $failed_orders = array();
    setlocale(LC_TIME, 'en_US');

    $params = array(
        'OrderStatus' => 'All'
    );
    $last_transaction = db_get_field('SELECT timestamp FROM ?:ebay_cached_transactions WHERE type = ?s AND status = ?s ORDER BY timestamp DESC', 'orders', 'C'); // Need user_id

    if (!empty($last_transaction)) {
        $params['CreateTimeFrom'] = gmstrftime("%Y-%m-%dT%H:%M:%S", $last_transaction);
        $params['CreateTimeTo'] = gmstrftime("%Y-%m-%dT%H:%M:%S", TIME);
    }

    $data = array(
        'timestamp' => TIME,
        'user_id' => Tygh::$app['session']['auth']['user_id'],
        'session_id' => Tygh::$app['session']->getID(),
        'status' => 'A',
        'type' => 'orders',
        'result' => '',
        'site_id' => 0
    );
    $transaction_id = db_query('INSERT INTO ?:ebay_cached_transactions ?e', $data);

    list(,$ebay_orders) = Ebay::instance()->GetOrders($params);

    $data = array(
        'status' => 'C',
        'result' => count($ebay_orders)
    );
    db_query('UPDATE ?:ebay_cached_transactions SET ?u WHERE transaction_id = ?i', $data, $transaction_id);

    if (!empty($ebay_orders)) {
        foreach ($ebay_orders as $k => $v) {
            $order_status = ($v['OrderStatus'] == 'Completed') ? 'P' : 'O';
            $cart = array();
            fn_clear_cart($cart, true);

            $item_transactions = $v['TransactionArray'];
            $_cart = $products = array();
            if (!is_array($item_transactions)) {
                $item_transactions = $item_transactions->Transaction;
            }
            $i = 1;
            foreach ($item_transactions as $item) {
                $email = (string) $item->Buyer->Email;
                break;
            }

            $shipping_address = $v['ShippingAddress'];
            $customer_name = explode(' ', (string) $shipping_address->Name);
            $firstname = array_shift($customer_name);
            $lastname = implode(' ', $customer_name);

            $_cart = array(
                'user_id' => 0,
                'company_id' => Registry::get('runtime.company_id'),
                'email' => $email,
                'ebay_order_id' => $v['OrderID'],
                'timestamp' => strtotime($v['CreatedTime']),
                'payment_id' => 0,
                'user_data' => array(
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'phone' => (string) $shipping_address->Phone,
                    'country' => (string) $shipping_address->Country,

                    's_firstname' => $firstname,
                    's_lastname' => $lastname,
                    's_address' => (string) $shipping_address->Street1,
                    's_city' => (string) $shipping_address->CityName,
                    's_state' => (string) $shipping_address->StateOrProvince,
                    's_country' => (string) $shipping_address->Country,
                    's_phone' => (string) $shipping_address->Phone,
                    's_zipcode' => (string) $shipping_address->PostalCode,

                    'b_firstname' => $firstname,
                    'b_lastname' => $lastname,
                    'b_address' => (string) $shipping_address->Street1,
                    'b_city' => (string) $shipping_address->CityName,
                    'b_state' => (string) $shipping_address->StateOrProvince,
                    'b_country' => (string) $shipping_address->Country,
                    'b_phone' => (string) $shipping_address->Phone,
                    'b_zipcode' => (string) $shipping_address->PostalCode,
                ),
                'notes' => '',
                'payment_info' => array(),
                'calculate_shipping' => false,
                'shipping_required' => false,
            );
            $cart = fn_array_merge($cart, $_cart);
            foreach ($item_transactions as $item) {
                $_item = (array) $item->Item;
                $product_id = db_get_field('SELECT product_id FROM ?:ebay_template_products WHERE ebay_item_id = ?i', $_item['ItemID']); // Need check company_id
                if (!$product_id) {
                    continue;
                }
                $product = fn_get_product_data($product_id, $cart['user_data']);

                $extra = array (
                    'product_options' => array()
                );

                $options = db_get_array(
                'SELECT ?:product_options.option_id, ?:product_options_descriptions.option_name, ?:product_option_variants_descriptions.variant_id, ?:product_option_variants_descriptions.variant_name
                FROM ?:product_options
                JOIN ?:product_option_variants ON ?:product_option_variants.option_id = ?:product_options.option_id
                JOIN ?:product_options_descriptions ON ?:product_options_descriptions.option_id = ?:product_options.option_id
                JOIN ?:product_option_variants_descriptions ON ?:product_option_variants_descriptions.variant_id = ?:product_option_variants.variant_id
                WHERE product_id =?i', $product_id);
                if (isset($item->Variation)) {
                    $variations_xml = (array) $item->Variation->VariationSpecifics;
                    if (isset($variations_xml['NameValueList']->Name)) {
                        $variations = (array) $variations_xml['NameValueList'];
                    } else {
                        foreach ($variations_xml['NameValueList'] as $variation) {
                            $variations[] = (array) $variation;
                        }
                    }
                    if (isset($variations)) {
                        if (isset($variations['Name'])) {
                            foreach ($options as $option) {
                                if ($variations['Name'] == $option['option_name'] && $variations['Value'] == $option['variant_name']) {
                                    $extra['product_options'][$option['option_id']] = $option['variant_id'];
                                }
                            }
                        } else {
                            foreach ($variations as $variation) {
                                foreach ($options as $option) {
                                    if ($variation['Name'] == $option['option_name'] && $variation['Value'] == $option['variant_name']) {
                                        $extra['product_options'][$option['option_id']] = $option['variant_id'];
                                    }
                                }
                            }
                        }
                        $variations = array();
                    }
                }

                $products[$i] = array(
                    'product_id' => $product_id,
                    'amount' => (int) $item->QuantityPurchased,
                    'price' => (float) $item->TransactionPrice,
                    'base_price' => (float) $item->TransactionPrice,
                    'is_edp' => $product['is_edp'],
                    'edp_shipping' => $product['edp_shipping'],
                    'free_shipping' => $product['free_shipping'],
                    'stored_price' => 'Y',
                    'company_id' => Registry::get('runtime.company_id'),
                    'extra' => $extra
                );
                unset($product);

                $i += 1;
            }
            if (empty($products)) {
                continue;
            }

            $cart['products'] = $products;
            unset($products);

            fn_calculate_cart_content($cart, $customer_auth, 'S', false, 'F', false);

            $cart['shipping_failed'] = false;
            $cart['company_shipping_failed'] = false;
            $cart['shipping_cost'] = $cart['display_shipping_cost'] = (float) $v['ShippingServiceSelected']->ShippingServiceCost;
            $cart['total'] = $v['Total'];
            $cart['subtotal'] = $v['Subtotal'];
            list($order_id, $process_payment) = fn_place_order($cart, $customer_auth);
            if (!empty($order_id)) {
                fn_change_order_status($order_id, $order_status, false);
                $success_orders[] = $order_id;
            } else {
                $failed_orders[] = $cart['ebay_order_id'];
            }
        }
    }

    return array($success_orders, $failed_orders);
}

function fn_get_ebay_registration_notice()
{
    return __('ebay_registration_notice');
}

function fn_ebay_get_orders($params, $fields, $sortings, &$condition, $join, $group)
{

    if (!empty($params['ebay_orders']) && $params['ebay_orders'] == 'Y') {
        $condition .= db_quote(" AND ?:orders.ebay_order_id <> ?s", '');
    }

    return true;
}

function fn_ebay_update_product_post($product_data, $product_id, $lang_code, $create)
{
    if (empty($product_id)) {
        return false;
    }

    $auth = Tygh::$app['session']['auth'];

    $_product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE);
    fn_gather_additional_product_data($_product_data, true, true);

    if ($_product_data['override'] == "Y") {
        $title = substr(strip_tags($_product_data['ebay_title']), 0, 80);
        $description = !empty($_product_data['ebay_description']) ? $_product_data['ebay_description'] : $_product_data['full_description'];
    } else {
        $title = substr(strip_tags($_product_data['product']), 0, 80);
        $description = $_product_data['full_description'];
    }

    $hash_data = array(
        'price' => fn_format_price($_product_data['price']),
        'title' => $title,
        'description' => $description,
        'product_features' => serialize($_product_data['product_features'])
    );

    $product_hash = fn_crc32(implode('_', $hash_data));

    db_query('UPDATE ?:products SET product_hash = ?s WHERE product_id = ?i', $product_hash, $product_id);

    return true;
}

function fn_prepare_xml_product_options($params, $product = array(), $images_data = array())
{
    $variant_values = '';
    $variations_specifics = '';
    $NameValueList = '';
    $variations = '';
    $name = '';
    $price = 0;
    $spec_values = '';
    $pictures = '';

    list($inventory, , $product_options,) = fn_get_product_options_inventory_ebay($params);
    foreach ($product_options as $specific) {
        $pictures .= <<<EOT
        <Pictures>
        <VariationSpecificName>$specific[option_name]</VariationSpecificName>
EOT;
        foreach ($specific['variants'] as $spec) {
            if (!empty($spec['image_pair']['icon']['http_image_path']) && !empty($images_data[md5($spec['image_pair']['icon']['http_image_path'])])) {
                $img_url = $images_data[md5($spec['image_pair']['icon']['http_image_path'])];
            } else {
                $img_url = $images_data[md5('no_image.png')];
            }
            $pictures .= <<<EOT
            <VariationSpecificPictureSet>
                <VariationSpecificValue>$spec[variant_name]</VariationSpecificValue>
                <PictureURL>$img_url</PictureURL>
            </VariationSpecificPictureSet>
EOT;
            $spec_values .= <<<EOT
            <Value>$spec[variant_name]</Value>\n
EOT;
        }
        $pictures .= <<<EOT
        </Pictures>
EOT;
        $NameValueList .= <<<EOT
            <NameValueList>
                <Name>$specific[option_name]</Name>
                $spec_values
            </NameValueList>
EOT;
        $spec_values = '';
    }

    foreach ($inventory as $combination) {
        foreach ($combination['combination'] as $option => $variant) {
        $option_names = $product_options[$option]['option_name'];
        $current_value = $product_options[$option]['variants'][$variant]['variant_name'];
        $variant_values .= <<<EOT
            <NameValueList>
                <Name>$option_names</Name>
                <Value>$current_value</Value>\n
            </NameValueList>

EOT;
        $name .= $option_names . $current_value;

            if ($product_options[$option]['variants'][$variant]['modifier_type'] == 'A') {
                if ($price == 0) {
                    $price = $product['base_price'] + ($product_options[$option]['variants'][$variant]['modifier']);
                } else {
                    $price = $price + ($product_options[$option]['variants'][$variant]['modifier']);
                }
            } else {
                if ($price == 0) {
                    $price = ($product['base_price'] + (($product['base_price'] * $product_options[$option]['variants'][$variant]['modifier'])/100));
                } else {
                    $price = ($price + (($price * $product_options[$option]['variants'][$variant]['modifier'])/100));
                }
            }
        }

        $track_with_options = db_get_field("SELECT tracking FROM ?:products WHERE product_id = ?i", $product['product_id']);
        if ($track_with_options == ProductTracking::TRACK_WITH_OPTIONS) {
            $quant = $combination['amount'];
        } else {
            $quant = $product['amount'];
        }

        $variations .= <<<EOT
        \n<Variation>
            <SKU>$name</SKU>
            <StartPrice>$price</StartPrice>
            <Quantity>$quant</Quantity>
            <VariationSpecifics>
            $variant_values
            </VariationSpecifics>
        </Variation>
EOT;
        $variant_values = '';
        $price = 0;
        $name = '';
    }

    $NameValueList = '<VariationSpecificsSet>' . $NameValueList . '</VariationSpecificsSet>' . $pictures;

    return $NameValueList.$variations;
}

function fn_get_product_options_inventory_ebay($params, $items_per_page = 0, $lang_code = DESCR_SL)
{
    $default_params = array (
        'page' => 1,
        'product_id' => 0,
        'items_per_page' => 0
    );

    $params = array_merge($default_params, $params);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_options_inventory WHERE product_id = ?i", $params['product_id']);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $inventory = db_get_array("SELECT * FROM ?:product_options_inventory WHERE product_id = ?i ORDER BY position $limit", $params['product_id']);

    foreach ($inventory as $k => $v) {
        $inventory[$k]['combination'] = fn_get_product_options_by_combination($v['combination']);
        $inventory[$k]['image_pairs'] = fn_get_image_pairs($v['combination_hash'], 'product_option', 'M', true, true, $lang_code);
    }

    $product_options = fn_get_product_options($params['product_id'], $lang_code, true, true);
    $product_inventory = db_get_field("SELECT tracking FROM ?:products WHERE product_id = ?i", $params['product_id']);

    return array($inventory, $params, $product_options, $product_inventory);
}

function fn_ebay_get_products($params, $fields, $sortings, &$condition, &$join, $sorting, $group_by, $lang_code, $having = array())
{
    if (!empty($params['ebay_update']) && $params['ebay_update'] == 'W') {
        $join .= db_quote(' LEFT JOIN ?:ebay_template_products ON ?:ebay_template_products.product_id = products.product_id');
        $condition .= db_quote(' AND ?:ebay_template_products.product_hash <> products.product_hash');
    }

    if (!empty($params['ebay_update']) && $params['ebay_update'] == 'P') {
        $join .= db_quote(' LEFT JOIN ?:ebay_template_products ebay_products ON ebay_products.product_id = products.product_id');
        $condition .= db_quote(' AND ebay_products.product_hash IS NOT NULL');
    }

    if (!empty($params['ebay_template_id'])) {
        if ($params['ebay_template_id'] == 'any') {
            $condition .= db_quote(' AND products.ebay_template_id != 0');
        } else {
            $condition .= db_quote(' AND products.ebay_template_id = ?i', $params['ebay_template_id']);
        }
    }

    if (isset($params['ebay_status']) && strlen($params['ebay_status']) > 0) {
        if ($params['ebay_status'] == '0') {
            $condition .= db_quote(' AND (products.ebay_status = 0 OR products.ebay_status IS NULL) AND products.ebay_template_id != 0');
        } else {
            $condition .= db_quote(' AND products.ebay_status = ?i', $params['ebay_status']);
        }
    }

    return true;
}

function fn_ebay_load_products_extra_data(&$extra_fields, $products, $product_ids, $params, $lang_code)
{
    if (!in_array('ebay_status', $extra_fields['?:products']['fields'])) {
        $extra_fields['?:products']['fields'][] = 'ebay_status';
    }

    if (!in_array('ebay_template_id', $extra_fields['?:products']['fields'])) {
        $extra_fields['?:products']['fields'][] = 'ebay_template_id';
    }
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
 *      fn_ebay_get_categories()
 *      --------------------------------------
 *      Gets subcategories tree of the category:
 *      fn_ebay_get_categories(array(
 *          'category_id' => 123
 *      ))
 *      --------------------------------------
 *      Gets all first-level nodes of the category
 *      fn_ebay_get_categories(array(
 *          'category_id' => 123,
 *          'visible' => true
 *      ))
 *      --------------------------------------
 *      Gets all visible nodes of the category, start from the root
 *      fn_ebay_get_categories(array(
 *          'category_id' => 0,
 *          'current_category_id' => 234,
 *          'visible' => true
 *      ))
 * @param int $site_id
 * @param string $lang_code 2-letters language code
 * @return array Categories tree
 */
function fn_ebay_get_categories($params = array(), $site_id = 0, $lang_code = CART_LANGUAGE)
{
    $default_params = array (
        'category_id' => 0,
        'visible' => false,
        'current_category_id' => 0,
        'simple' => true,
        'plain' => false,
        'limit' => 0,
        'item_ids' => '',
        'group_by_level' => true,
        'category_delimiter' => ',',
        'max_nesting_level' => null,    // null means no limitation
    );

    $params = array_merge($default_params, $params);
    $sortings = array (
        'name' => '?:ebay_categories.name'
    );

    $fields = array (
        'category_id',
        'parent_id',
        'id_path',
        'name',
        'level',
        'leaf',
        'site_id'
    );

    if (empty($params['current_category_id']) && !empty($params['product_category_id'])) {
        $params['current_category_id'] = $params['product_category_id'];
    }

    $condition = db_quote('AND site_id = ?i', $site_id);

    if (isset($params['parent_category_id'])) {
        // set parent id, that was set in block properties
        $params['category_id'] = $params['parent_category_id'];
    }

    if (empty($params['b_id'])) {
        $parent_categories_ids = array();

        if (!empty($params['current_category_id'])) {
            $cur_id_path = db_get_field("SELECT id_path FROM ?:ebay_categories WHERE category_id = ?i AND site_id = ?i", $params['current_category_id'], $site_id);
            if (!empty($cur_id_path)) {
                $parent_categories_ids = explode(',', $cur_id_path);
            }
        }
        if (!empty($params['category_id']) || empty($parent_categories_ids)) {
            $parent_categories_ids[] = $params['category_id'];
        }
        $parents_condition = db_quote(" AND ?:ebay_categories.parent_id IN (?n)", $parent_categories_ids);
    }

    // if we have company_condtion, skip $parents_condition, it will be processed later by PHP
    if (!empty($parents_condition) && empty($company_condition)) {
        $condition .= $parents_condition;
    }

    if (!empty($params['category_id'])) {
        $from_id_path = db_get_field("SELECT id_path FROM ?:ebay_categories WHERE category_id = ?i AND site_id = ?i", $params['category_id'], $site_id);
        $condition .= db_quote(" AND ?:ebay_categories.id_path LIKE ?l", "$from_id_path,%");
    }

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(' AND ?:ebay_categories.category_id IN (?n)', explode(',', $params['item_ids']));
    }

    if (!empty($params['except_id']) && (empty($params['item_ids']) || !empty($params['item_ids']) && !in_array($params['except_id'], explode(',', $params['item_ids'])))) {
        $condition .= db_quote(' AND ?:ebay_categories.category_id != ?i AND ?:ebay_categories.parent_id != ?i', $params['except_id'], $params['except_id']);
    }

    if (!empty($params['max_nesting_level'])) {
        $condition .= db_quote(" AND ?:ebay_categories.level <= ?i", $params['max_nesting_level']);
    }

    $limit = $join = $group_by = '';

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'name', 'asc');

    $categories = db_get_hash_array('SELECT ' . implode(',', $fields) . " FROM ?:ebay_categories WHERE 1 ?p $group_by $sorting ?p", 'category_id', $condition, $limit);

    if (empty($categories)) {
        return array(array());
    }

    // @TODO remove from here, because active category may not exist in the resulting set. This is the job for controller.
    if (!empty($params['active_category_id']) && !empty($categories[$params['active_category_id']])) {
        $categories[$params['active_category_id']]['active'] = true;
    }

    $categories_list = array();
    if ($params['simple'] == true || $params['group_by_level'] == true) {
        $child_for = array_keys($categories);
        $where_condition = !empty($params['except_id']) ? db_quote(' AND category_id != ?i', $params['except_id']) : '';
        $has_children = db_get_hash_array("SELECT category_id, parent_id FROM ?:ebay_categories WHERE parent_id IN(?n) AND site_id = ?i ?p", 'parent_id', $child_for, $site_id, $where_condition);
    }

    $category_ids = array();
    // Group categories by the level (simple)
    if ($params['simple'] == true) {
        foreach ($categories as $k => $v) {
            $v['level'] = substr_count($v['id_path'], ',');
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
            $v['level'] = substr_count($v['id_path'], ",");
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

    // Rearrangement of subcategories and filling with images
    foreach ($categories_list as $level => $categories_of_level) {
        foreach ($categories_of_level as $category_id => $category_data) {
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

    fn_dropdown_appearance_cut_second_third_levels($categories_list, 'subcategories', $params);

    return array($categories_list, $params);
}

/**
 * Return ebay category data by category id
 * @param int $category_id
 * @param int $site_id
 * @return array
 */
function fn_ebay_get_category_data($category_id, $site_id = 0)
{
    return db_get_row("SELECT * FROM ?:ebay_categories WHERE category_id = ?i AND site_id = ?i", $category_id, $site_id);
}

/**
 * Handler for hook get_category_data post
 * @param $category_id
 * @param $field_list
 * @param $get_main_pair
 * @param $skip_company_condition
 * @param $lang_code
 * @param $category_data
 */
function fn_ebay_get_category_data_post($category_id, $field_list, $get_main_pair, $skip_company_condition, $lang_code, &$category_data)
{
    if (isset($category_data['ebay_category'])) {
        $category_data['ebay_site_id'] = null;
        $category_data['ebay_category_id'] = null;

        if (!empty($category_data['ebay_category'])) {
            $part = explode(':', $category_data['ebay_category']);

            if (count($part) == 2) {
                $category_data['ebay_site_id'] = $part[0];
                $category_data['ebay_category_id'] = $part[1];
            }
        }
    }
}

/**
 * Return array objects need synchronization
 * @param int $site_id
 * @param int|null $category_id
 * @return array
 */
function fn_ebay_get_objects_needed_synchronization($site_id, $category_id = null)
{
    $result = array(
        'Site' => \Ebay\objects\Site::isNeedSynchronization(),
        'SiteDetail' => \Ebay\objects\Site::isNeedDetailSynchronization($site_id),
        'Category' => \Ebay\objects\Category::isNeedSynchronization($site_id),
        'Shipping' => \Ebay\objects\Shipping::isNeedSynchronization($site_id),
    );

    if ($category_id !== null) {
        $result['CategoryFeature'] = \Ebay\objects\CategoryFeature::isNeedSynchronization($site_id, $category_id);
    }

    return array_keys(array_filter($result));
}

/**
 * Get ebay features for create
 *
 * @return array
 */
function fn_ebay_new_product_features()
{
    return array(
        array(
            'description' => __('ebay_feature_group_product_identifiers'),
            'feature_code' => 'ebay_group_identifiers',
            'position' => '',
            'status' => 'A',
            'full_description' => '',
            'feature_type' => ProductFeatures::GROUP,
            'display_on_product' => 'N',
            'display_on_catalog' => 'N',
            'display_on_header' => 'N',
            'categories_path' => '',
        ),
        array(
            'description' => __('ebay_product_identifier_ean'),
            'feature_code' => 'ebay_ean',
            'position' => '',
            'status' => 'A',
            'full_description' => '',
            'feature_type' => ProductFeatures::TEXT_FIELD,
            'display_on_product' => 'N',
            'display_on_catalog' => 'N',
            'display_on_header' => 'N',
            'categories_path' => '',
            'parent' => 'ebay_group_identifiers'
        ),
        array(
            'description' => __('ebay_product_identifier_upc'),
            'feature_code' => 'ebay_upc',
            'position' => '',
            'status' => 'A',
            'full_description' => '',
            'feature_type' => ProductFeatures::TEXT_FIELD,
            'display_on_product' => 'N',
            'display_on_catalog' => 'N',
            'display_on_header' => 'N',
            'categories_path' => '',
            'parent' => 'ebay_group_identifiers'
        ),
        array(
            'description' => __('ebay_product_identifier_isbn'),
            'feature_code' => 'ebay_isbn',
            'position' => '',
            'status' => 'A',
            'full_description' => '',
            'feature_type' => ProductFeatures::TEXT_FIELD,
            'display_on_product' => 'N',
            'display_on_catalog' => 'N',
            'display_on_header' => 'N',
            'categories_path' => '',
            'parent' => 'ebay_group_identifiers'
        ),
        array(
            'description' => __('ebay_product_identifier_brand'),
            'feature_code' => 'ebay_brand',
            'position' => '',
            'status' => 'A',
            'full_description' => '',
            'feature_type' => ProductFeatures::TEXT_FIELD,
            'display_on_product' => 'N',
            'display_on_catalog' => 'N',
            'display_on_header' => 'N',
            'categories_path' => '',
            'parent' => 'ebay_group_identifiers'
        ),
        array(
            'description' => __('ebay_product_identifier_mpn'),
            'feature_code' => 'ebay_mpn',
            'position' => '',
            'status' => 'A',
            'full_description' => '',
            'feature_type' => ProductFeatures::TEXT_FIELD,
            'display_on_product' => 'N',
            'display_on_catalog' => 'N',
            'display_on_header' => 'N',
            'categories_path' => '',
            'parent' => 'ebay_group_identifiers'
        )
    );
}

/**
 * Install ebay addon
 * @internal
 */
function fn_ebay_install()
{
    $company_id = 0;

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = fn_get_runtime_company_id();

        if (empty($company_id)) {
            $company_id = fn_get_default_company_id();
        }
    }

    foreach (fn_ebay_new_product_features() as $item) {
        if (!empty($item['parent'])) {
            $parent_id = db_get_field(
                "SELECT feature_id FROM ?:product_features WHERE feature_code = ?s AND feature_type = ?s",
                $item['parent'],
                ProductFeatures::GROUP
            );

            unset($item['parent']);
            $item['parent_id'] = $parent_id;
        }

        $item['company_id'] = $company_id;
        $feature_id = fn_update_product_feature($item, 0);

        if (fn_allowed_for('ULTIMATE') && $feature_id) {
            fn_share_object_to_all('product_features', $feature_id);
        }
    }
}

/**
 * Uninstall ebay addon
 * @internal
 */
function fn_ebay_uninstall()
{
    \Ebay\objects\Site::clearLastSynchronizationTime();
    \Ebay\objects\Category::clearLastSynchronizationTime();
    \Ebay\objects\Category::clearCategoryVersions();
    \Ebay\objects\Shipping::clearLastSynchronizationTime();

    foreach (fn_ebay_new_product_features() as $item) {
        $feature_id = (int) db_get_field(
            "SELECT feature_id FROM ?:product_features WHERE feature_code = ?s AND feature_type = ?s",
            $item['feature_code'],
            $item['feature_type']
        );

        if ($feature_id) {
            fn_delete_feature($feature_id);
        }
    }
}