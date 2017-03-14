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
use Tygh\Enum\ProductTracking;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Update sales stats for the product
//
function fn_bestsellers_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses)
{

    $product_ids = db_get_fields("SELECT product_id FROM ?:order_details WHERE order_id = ?i GROUP BY product_id", $order_info['order_id']);

    if ($order_statuses[$status_to]['params']['inventory'] == 'D' && $order_statuses[$status_from]['params']['inventory'] == 'I') {
        $increase = true;
    } elseif ($order_statuses[$status_to]['params']['inventory'] == 'I' && $order_statuses[$status_from]['params']['inventory'] == 'D') {
        $increase = false;
    } else {
        return true;
    }

    foreach ($product_ids as $product_id) {
        $cids = db_get_fields("SELECT category_id FROM ?:products_categories WHERE product_id = ?i", $product_id);
        if (!empty($cids)) {
            foreach ($cids as $cid) {
                $c_amount = db_get_field("SELECT amount FROM ?:product_sales WHERE category_id = ?i AND product_id = ?i", $cid, $product_id);
                $c_amount = ($increase == true) ? ($c_amount + 1) : ($c_amount - 1);
                db_query("REPLACE INTO ?:product_sales (category_id, product_id, amount) VALUES (?i, ?i, ?i)", $cid, $product_id, $c_amount);
            }
        }
    }

    return db_query("DELETE FROM ?:product_sales WHERE amount = 0");
}

function fn_bestsellers_delete_product_post(&$product_id)
{
    db_query("DELETE FROM ?:product_sales WHERE product_id = ?i", $product_id);

    return true;
}

function fn_bestsellers_get_products_before_select(&$params, $join, $condition, $u_condition, $inventory_join_cond, $sortings, $total, $items_per_page, $lang_code, $having)
{
    if (!empty($params['bestsellers'])) {
        $params['extend'][] = 'categories';
    } elseif (empty($params['sort_by']) || empty($sortings[$params['sort_by']])) {
        $default_sorting_params = fn_get_default_products_sorting();

        if ((!empty($params['sort_by']) && $params['sort_by'] == 'bestsellers') || ($default_sorting_params['sort_by'] == 'bestsellers' && empty($params['sort_by'])) || isset($params['sales_amount_from']) || isset($params['sales_amount_to'])) {
            $params['extend'][] = 'categories';
            $params['extend'][] = 'sales';

        } elseif ((!empty($params['sort_by']) && $params['sort_by'] == 'on_sale') || ($default_sorting_params['sort_by'] == 'on_sale' && empty($params['sort_by']))) {
            $params['extend'][] = 'on_sale';
        }
    }

    if (!empty($params['similar'])) {

        $product = Tygh::$app['view']->getTemplateVars('product');

        if (!empty($params['main_product_id'])) {
            $params['exclude_pid'] = $params['main_product_id'];
        }

        if (!empty($params['similar_category']) && $params['similar_category'] == 'Y') {
            $params['cid'] = $product['main_category'];

            if (!empty($params['similar_subcats']) && $params['similar_subcats'] == 'Y') {
                $params['subcats'] = 'Y';
            }
        }

        if (!empty($product['price'])) {

            if (!empty($params['percent_range'])) {
                $range = $product['price'] / 100 * $params['percent_range'];

                $params['price_from'] = $product['price'] - $range;
                $params['price_to'] = $product['price'] + $range;
            }

        }
    }
}

function fn_bestsellers_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, &$lang_code, &$having)
{
    if (!empty($params['bestsellers'])) {
        $fields[] = 'SUM(?:product_sales.amount) as sales_amount';
        $sortings['sales_amount'] = 'sales_amount';
        $join .= ' INNER JOIN ?:product_sales ON ?:product_sales.product_id = products.product_id AND ?:product_sales.category_id = products_categories.category_id ';
        $group_by = '?:product_sales.product_id';
        if (!empty($params['category_id'])) {
            $condition .= db_quote(" AND ?:product_sales.category_id = ?i", $params['category_id']);
        }

    } elseif (!empty($params['on_sale'])) {
        $fields[] = '100 - ((prices.price * 100) / list_price) AS sales_discount';

        if (empty($params['on_sale_from'])) {
            $having[] = db_quote('sales_discount > 0');

        } else {
            $_having = db_quote('sales_discount >= ?i', $params['on_sale_from']);

            if (!empty($params['on_sale_to'])) {
                $_having .= db_quote(' AND sales_discount <= ?i', $params['on_sale_to']);
            }

            $having[] = $_having;
        }

    }

    $sortings['bestsellers'] = '?:product_sales.amount';
    $sortings['on_sale'] = 'sales_discount';

    if (empty($params['on_sale']) && !empty($params['sort_by']) && $params['sort_by'] == 'on_sale') {
        $params['extend'][] = 'on_sale';
    }

    if (isset($params['sales_amount_from']) && fn_is_numeric($params['sales_amount_from'])) {
        $condition .= db_quote(' AND ?:product_sales.amount >= ?i', trim($params['sales_amount_from']));
    }

    if (isset($params['sales_amount_to']) && fn_is_numeric($params['sales_amount_to'])) {
        $condition .= db_quote(' AND ?:product_sales.amount <= ?i', trim($params['sales_amount_to']));
    }

    if ((in_array('sales', $params['extend']) && empty($params['bestsellers']))) {
        $join .= ' LEFT JOIN ?:product_sales ON ?:product_sales.product_id = products.product_id AND ?:product_sales.category_id = products_categories.category_id ';
    } elseif ((in_array('on_sale', $params['extend']) && empty($params['on_sale']))) {
        $fields[] = '100 - ((prices.price * 100) / list_price) AS sales_discount';
    }

    // in stock conditions are applied if out of stock products are not cut-off in ::fn_get_products
    if (
        !empty($params['similar_in_stock'])
        && $params['similar_in_stock'] == 'Y'
        && !(
            Registry::get('settings.General.inventory_tracking') == 'Y'
            && Registry::get('settings.General.show_out_of_stock_products') == 'N'
        )
    ) {
        $condition .= db_quote(
            " AND (IF(products.tracking = ?s, inventory_b.amount >= 1, products.amount >= 1) OR (products.tracking = 'D'))",
            ProductTracking::TRACK_WITH_OPTIONS
        );

        $join .= " LEFT JOIN ?:product_options_inventory as inventory_b ON inventory_b.product_id = products.product_id AND inventory_b.amount >= 1";
    }

    return true;
}

function fn_bestsellers_products_sorting(&$sorting)
{
    $sorting['bestsellers'] = array('description' => __('bestselling'), 'default_order' => 'desc');
    $sorting['on_sale'] = array('description' => __('on_sale'), 'default_order' => 'desc');
}

function fn_bestsellers_update_product_post(&$product_data, &$product_id)
{
    if (!isset($product_data['sales_amount'])) {
        return false;
    }

    db_query("DELETE FROM ?:product_sales WHERE product_id = ?i", $product_id);
    $cids = db_get_fields("SELECT category_id FROM ?:products_categories WHERE product_id = ?i", $product_id);
    if (!empty($cids)) {
        foreach ($cids as $category_id) {
            $_data = array (
                'category_id' => $category_id,
                'product_id' => $product_id,
                'amount' => $product_data['sales_amount']
            );

            db_query("REPLACE INTO ?:product_sales ?e", $_data);
        }
    }

    return true;
}

function fn_bestsellers_get_product_data(&$product_id, &$field_list, &$join, &$auth)
{
    $product_category = db_get_field("SELECT category_id FROM ?:products_categories WHERE product_id = ?i AND link_type = 'M'", $product_id);

    $field_list .= ", ?:product_sales.amount as sales_amount";
    $join .= db_quote(" LEFT JOIN ?:product_sales ON ?:product_sales.product_id = ?:products.product_id AND ?:product_sales.category_id = ?i", $product_category);
}

/**
 * Delete all records from the product_sales table
 *
 * @param integer $category_id Category ID
 * @return boolean Always true
 */
function fn_bestsellers_delete_category_after(&$category_id)
{
    db_query("DELETE FROM ?:product_sales WHERE category_id = ?i", $category_id);

    return true;
}
