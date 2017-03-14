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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_payment_dependencies_get_shipping_info_post(&$shipping_id, &$lang_code, &$shipping)
{
    if (!empty($shipping['disable_payment_ids'])) {
        $shipping['disable_payment_ids'] = explode(',', $shipping['disable_payment_ids']);
    } else {
        $shipping['disable_payment_ids'] = array();
    }
}

function fn_payment_dependencies_update_shipping(&$shipping_data, &$shipping_id, &$lang_code)
{
    if (isset($shipping_data['enable_payment_ids'])) {
        if (empty($shipping_data['enable_payment_ids']) || !is_array($shipping_data['enable_payment_ids'])) {
            $shipping_data['enable_payment_ids'] = array();
        }
        $disable_payment_ids = array_diff(array_keys(fn_get_payments()), $shipping_data['enable_payment_ids']);
        $shipping_data['disable_payment_ids'] = implode(',', $disable_payment_ids);
    }
}

function fn_payment_dependencies_prepare_checkout_payment_methods(&$cart, &$auth, &$payment_groups)
{
    if (!empty($cart['shipping'])) {
        $disable_payment_ids = array();
        foreach ($cart['shipping'] as $shipping) {
            if ($shipping['disable_payment_ids']) {
                $disable_payment_ids = array_merge($disable_payment_ids, $shipping['disable_payment_ids']);
            }
        }
        $disable_payment_ids = array_unique($disable_payment_ids);
        if ($disable_payment_ids) {
            foreach ($payment_groups as $g_key => $group) {
                foreach ($group as $p_key => $payment) {
                    if (in_array($payment['payment_id'], $disable_payment_ids)) {
                        unset($payment_groups[$g_key][$p_key]);
                    }
                }
                if (empty($payment_groups[$g_key])) {
                    unset($payment_groups[$g_key]);
                }
            }
        }
    }
}

function fn_payment_dependencies_shippings_get_shippings_list_conditions(&$group, &$shippings, &$fields, &$join, &$condition, &$order_by)
{
    $fields[] = '?:shippings.disable_payment_ids';
}

function fn_payment_dependencies_shippings_get_shippings_list_post(&$group, &$lang, &$area, &$shippings_info)
{
    foreach ($shippings_info as &$shipping) {
        $shipping['disable_payment_ids'] = explode(',', $shipping['disable_payment_ids']);
    }
}

function fn_payment_dependencies_checkout_select_default_payment_method(&$cart, &$payment_methods, &$completed_steps)
{
    $available_payment_ids = array();
    foreach ($payment_methods as $group) {
        foreach ($group as $method) {
            $available_payment_ids[] = $method['payment_id'];
        }
    }
    
    // Change default payment if it doesn't exists
    if (floatval($cart['total']) != 0 && !in_array($cart['payment_id'], $available_payment_ids)) {
        $cart['payment_id'] = reset($available_payment_ids);
        $cart['payment_method_data'] = fn_get_payment_method_data($cart['payment_id']);
    }
}
